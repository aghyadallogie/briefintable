<?php

namespace Tests\Feature;

use App\Livewire\BriefingForm;
use App\Support\ReservedSlugs;
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
 * slug "create" could never be opened.
 */
class ReservedSlugsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        Collection::make('briefings')->routes('/briefings/{slug}')->template('briefings/show')->layout('layout')->save();
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
        $this->assertContains('create', ReservedSlugs::briefings());
        $this->assertNotContains('{slug}', ReservedSlugs::briefings());
        $this->assertNotContains('drafts', ReservedSlugs::briefings(), '/my-drafts is not under /briefings.');
        $this->assertTrue(ReservedSlugs::isReserved('create'));
        $this->assertFalse(ReservedSlugs::isReserved('quarterly-update'));
    }

    public function test_a_new_briefings_route_is_reserved_automatically(): void
    {
        Route::get('/briefings/archive', fn () => 'ok');

        $this->assertTrue(ReservedSlugs::isReserved('archive'));
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
