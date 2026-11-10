<?php

namespace Tests\Feature;

use App\Livewire\BriefingForm;
use App\Support\BriefingSlugs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\User;
use Tests\TestCase;

/**
 * /briefings/create is matched before Statamic's /briefings/{slug}, so a briefing with the
 * slug "create" could never be opened. BriefingSlugs reserves such words and builds unique slugs.
 */
class BriefingSlugsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        Collection::make('briefings')->routes('/briefings/{slug}')->template('briefings/show')->layout('layout')->save();
        Collection::make('news')->routes('/news/{slug}')->save();
        Cache::flush();
    }

    private function slugs(): array
    {
        return Entry::query()->where('collection', 'briefings')->get()->map->slug()->all();
    }

    private function submit(string $title): void
    {
        Livewire::test(BriefingForm::class)->set('title', $title)->set('content', 'Body')->set('published', true)->call('save');
    }

    public function test_reserved_slugs_come_from_the_registered_briefings_routes(): void
    {
        $this->assertContains('create', BriefingSlugs::reserved());
        $this->assertNotContains('{slug}', BriefingSlugs::reserved());
        $this->assertNotContains('drafts', BriefingSlugs::reserved(), '/my-drafts is not under /briefings.');
        $this->assertTrue(BriefingSlugs::isReserved('create'));
        $this->assertFalse(BriefingSlugs::isReserved('quarterly-update'));
    }

    public function test_a_new_briefings_route_is_reserved_automatically(): void
    {
        Route::get('/briefings/archive', fn () => 'ok');

        $this->assertTrue(BriefingSlugs::isReserved('archive'));
    }

    private function withSlug(string $slug, string $collection = 'briefings'): void
    {
        Entry::make()->collection($collection)->slug($slug)->data(['title' => $slug])->save();
    }

    public function test_unique_is_the_titles_slug_when_nothing_uses_it(): void
    {
        $this->assertSame('hello-world', BriefingSlugs::unique('Hello World!'));
    }

    public function test_unique_adds_the_next_free_number(): void
    {
        $this->withSlug('hello');
        $this->assertSame('hello-2', BriefingSlugs::unique('Hello'));

        $this->withSlug('hello-2');
        $this->withSlug('hello-3');
        $this->assertSame('hello-4', BriefingSlugs::unique('Hello'));
    }

    public function test_unique_only_looks_at_briefings_and_matches_whole_slugs(): void
    {
        $this->withSlug('hello', collection: 'news');
        $this->withSlug('hello-there');

        // Another collection's slug and a longer slug that merely starts the same way do not block it.
        $this->assertSame('hello', BriefingSlugs::unique('Hello'));
    }

    public function test_unique_skips_the_words_our_own_routes_use(): void
    {
        $this->assertSame('create-2', BriefingSlugs::unique('Create'));
    }

    public function test_unique_falls_back_to_briefing_when_the_title_has_no_slug(): void
    {
        $this->assertSame('briefing', BriefingSlugs::unique('!!!'));

        $this->withSlug('briefing');
        $this->assertSame('briefing-2', BriefingSlugs::unique('???'));
    }

    public function test_the_form_never_uses_a_reserved_slug(): void
    {
        $this->actingAs(User::make()->id('admin-1')->email('a@example.test')->makeSuper());

        $this->submit('Create');
        $this->submit('Create');

        $this->assertEqualsCanonicalizing(['create-2', 'create-3'], $this->slugs());
    }

    public function test_a_briefing_titled_create_can_still_be_opened(): void
    {
        $this->actingAs(User::make()->id('admin-1')->email('a@example.test')->makeSuper());

        $this->submit('Create');

        $this->get('/briefings/create-2')->assertOk()->assertSee('Create');
    }

    public function test_the_cp_blueprint_rejects_a_reserved_slug(): void
    {
        $blueprint = Collection::findByHandle('briefings')->entryBlueprint('briefing');

        $valid = $blueprint->fields()->addValues(['title' => 'x', 'slug' => 'create-2'])->validate();
        $this->assertSame('create-2', $valid['slug']);

        try {
            $blueprint->fields()->addValues(['title' => 'x', 'slug' => 'create'])->validate();
            $this->fail('The reserved slug "create" should have been rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('slug', $e->errors());
        }
    }
}
