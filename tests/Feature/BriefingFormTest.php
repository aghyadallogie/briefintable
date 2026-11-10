<?php

namespace Tests\Feature;

use App\Http\Controllers\BriefingController;
use App\Livewire\BriefingForm;
use App\Support\BriefingsCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\User;
use Tests\TestCase;

class BriefingFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        Collection::make('briefings')->routes('/briefings/{slug}')->save();
        Cache::flush();

        // Deliberately not saved: avoids writing a user file into /users during tests.
        // A super user may create and publish; see ContributorRoleTest for restricted users.
        $this->actingAs(User::make()->id('author-1')->email('author@example.test')->makeSuper());
    }

    private function briefings()
    {
        return Entry::query()->where('collection', 'briefings')->get();
    }

    public function test_it_renders_the_form(): void
    {
        Livewire::test(BriefingForm::class)
            ->assertOk()
            ->assertSee('Create a Briefing')
            ->assertSee('Publish immediately');
    }

    public function test_it_saves_a_draft_by_default_and_resets_the_form(): void
    {
        Livewire::test(BriefingForm::class)
            ->set('title', 'Quarterly update')
            ->set('content', 'Some **markdown** body')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('title', '')
            ->assertSet('content', '')
            ->assertSet('published', false);

        $entry = $this->briefings()->sole();

        $this->assertFalse($entry->published());
        $this->assertSame('Quarterly update', $entry->get('title'));
        $this->assertSame('Some **markdown** body', $entry->get('content'));
        $this->assertSame('quarterly-update', $entry->slug());
        $this->assertSame('author-1', $entry->get('author'));
    }

    public function test_the_published_checkbox_sets_statamics_native_flag(): void
    {
        Livewire::test(BriefingForm::class)
            ->set('title', 'Go live')
            ->set('content', 'Body')
            ->set('published', true)
            ->call('save')
            ->assertHasNoErrors();

        $entry = $this->briefings()->sole();

        $this->assertTrue($entry->published());
        // The old duplicate field must not come back.
        $this->assertNull($entry->get('publication_state'));
    }

    public function test_it_flashes_a_success_message(): void
    {
        Livewire::test(BriefingForm::class)
            ->set('title', 'Hello')
            ->set('content', 'World')
            ->call('save')
            ->assertSee('Briefing successfully created!');
    }

    public function test_title_and_content_are_required(): void
    {
        Livewire::test(BriefingForm::class)
            ->call('save')
            ->assertHasErrors(['title' => 'required', 'content' => 'required']);

        $this->assertCount(0, $this->briefings());
    }

    public function test_content_is_limited_to_20000_characters(): void
    {
        Livewire::test(BriefingForm::class)
            ->set('title', 'Title')
            ->set('content', str_repeat('a', 20001))
            ->call('save')
            ->assertHasErrors(['content' => 'max']);

        $this->assertCount(0, $this->briefings());

        Livewire::test(BriefingForm::class)
            ->set('title', 'Title')
            ->set('content', str_repeat('a', 20000))
            ->call('save')
            ->assertHasNoErrors();
    }

    public function test_title_is_limited_to_255_characters(): void
    {
        Livewire::test(BriefingForm::class)
            ->set('title', str_repeat('a', 256))
            ->set('content', 'Body')
            ->call('save')
            ->assertHasErrors(['title' => 'max']);

        $this->assertCount(0, $this->briefings());
    }

    public function test_duplicate_titles_get_unique_slugs(): void
    {
        foreach (range(1, 3) as $_) {
            Livewire::test(BriefingForm::class)
                ->set('title', 'Same title')
                ->set('content', 'Body')
                ->call('save')
                ->assertHasNoErrors();
        }

        $this->assertEqualsCanonicalizing(
            ['same-title', 'same-title-2', 'same-title-3'],
            $this->briefings()->map->slug()->all(),
        );
    }

    public function test_a_similar_but_different_slug_does_not_force_a_suffix(): void
    {
        foreach (['Same title', 'Same'] as $title) {
            Livewire::test(BriefingForm::class)
                ->set('title', $title)
                ->set('content', 'Body')
                ->call('save')
                ->assertHasNoErrors();
        }

        $this->assertEqualsCanonicalizing(['same-title', 'same'], $this->briefings()->map->slug()->all());
    }

    public function test_a_title_with_no_sluggable_characters_falls_back_to_briefing(): void
    {
        Livewire::test(BriefingForm::class)
            ->set('title', '!!!')
            ->set('content', 'Body')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('briefing', $this->briefings()->sole()->slug());
    }

    public function test_saving_invalidates_the_public_list_cache(): void
    {
        $before = BriefingsCache::listKey(1, BriefingController::PER_PAGE);

        Livewire::test(BriefingForm::class)
            ->set('title', 'Fresh')
            ->set('content', 'Body')
            ->set('published', true)
            ->call('save');

        $this->assertNotSame($before, BriefingsCache::listKey(1, BriefingController::PER_PAGE));
        $this->get('/briefings')->assertSee('Fresh');
    }
}
