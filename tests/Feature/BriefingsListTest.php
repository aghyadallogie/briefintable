<?php

namespace Tests\Feature;

use App\Http\Controllers\BriefingController;
use App\Support\BriefingsCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Statamic\Eloquent\Entries\EntryModel;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Tests\TestCase;

class BriefingsListTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        Collection::make('briefings')
            ->routes('/briefings/{slug}')
            ->template('briefings/show')
            ->layout('layout')
            ->save();

        Cache::flush();
    }

    private function briefing(string $title, bool $published = true, string $content = 'Body text'): \Statamic\Entries\Entry
    {
        $entry = Entry::make()
            ->collection('briefings')
            ->slug(str($title)->slug().'-'.++$this->sequence)
            ->published($published)
            ->data(['title' => $title, 'content' => $content]);

        $entry->save();

        return $entry;
    }

    public function test_cards_rise_in_one_after_another_and_lift_on_hover(): void
    {
        $this->travelTo(now()->subDays(3));
        $this->briefing('Oldest');
        $this->travelTo(now()->addDay());
        $this->briefing('Middle');
        $this->travelTo(now()->addDay());
        $this->briefing('Newest');
        $this->travelBack();

        $html = $this->get('/briefings')->assertOk()->getContent();

        // Each card carries its position, so the CSS can stagger the entrance.
        $this->assertSame(3, preg_match_all('/<article class="card card-lift rise-in[^"]*" style="--i: (\d+)">/', $html, $m));
        $this->assertSame(['0', '1', '2'], $m[1]);
    }

    public function test_the_page_title_is_briefings(): void
    {
        $this->get('/briefings')->assertOk()->assertSee('<title>Briefings</title>', false);
    }

    public function test_it_shows_an_empty_state_when_nothing_is_published(): void
    {
        $this->briefing('Hidden draft', published: false);

        $this->get('/briefings')
            ->assertOk()
            ->assertSee('No briefings have been published yet.')
            ->assertDontSee('Hidden draft');
    }

    public function test_it_lists_only_published_briefings_newest_first(): void
    {
        $this->travelTo(now()->subDays(2));
        $this->briefing('Oldest');
        $this->travelTo(now()->addDay());
        $this->briefing('Newest');
        $this->briefing('Draft one', published: false);
        $this->travelBack();

        $this->get('/briefings')
            ->assertOk()
            ->assertSeeInOrder(['Newest', 'Oldest'])
            ->assertDontSee('Draft one');
    }

    public function test_it_sorts_by_publication_date_not_creation_date(): void
    {
        // Created first, but kept as a draft.
        $this->travelTo(now()->subDays(10));
        $draft = $this->briefing('Published last', published: false);

        // Created later, but published earlier.
        $this->travelTo(now()->addDays(5));
        $this->briefing('Published first');

        // Publish the older draft now, making it the most recently published.
        $this->travelTo(now()->addDays(5));
        $draft->published(true)->save();
        $this->travelBack();

        $this->get('/briefings')
            ->assertOk()
            ->assertSeeInOrder(['Published last', 'Published first']);
    }

    public function test_excerpt_strips_html_and_is_truncated(): void
    {
        $this->briefing('Long one', content: '**Bold intro** '.str_repeat('word ', 100).'THE-END-MARKER');

        $this->get('/briefings')
            ->assertOk()
            ->assertSee('Bold intro')
            ->assertDontSee('<strong>', escape: false)
            ->assertDontSee('<p>Bold intro', escape: false)
            ->assertDontSee('THE-END-MARKER');
    }

    public function test_it_paginates_and_shows_pager_links(): void
    {
        foreach (range(1, BriefingController::PER_PAGE + 2) as $i) {
            $this->travelTo(now()->addMinute());
            $this->briefing("Briefing {$i}");
        }
        $this->travelBack();

        $first = $this->get('/briefings')->assertOk();
        $this->assertSame(BriefingController::PER_PAGE, substr_count($first->getContent(), '<article'));
        $first->assertSee('Page 1 of 2')->assertSee('Older');

        $second = $this->get('/briefings?page=2')->assertOk();
        $this->assertSame(2, substr_count($second->getContent(), '<article'));
        $second->assertSee('Page 2 of 2')->assertSee('Newer');
    }

    public function test_cached_pager_links_do_not_leak_the_host_that_rendered_them(): void
    {
        foreach (range(1, BriefingController::PER_PAGE + 1) as $i) {
            $this->briefing("Briefing {$i}");
        }

        // First visitor warms the cache from one host (e.g. a script or a different port)...
        $this->get('http://first-host.test:8080/briefings')->assertOk();

        // ...the next visitor, on another host, must get working relative links.
        $this->get('http://second-host.test/briefings')
            ->assertOk()
            ->assertSee('href="?page=2"', escape: false)
            ->assertDontSee('first-host.test');

        $this->get('http://second-host.test/briefings?page=2')
            ->assertOk()
            ->assertSee('href="?page=1"', escape: false)
            ->assertDontSee('first-host.test');
    }

    public function test_out_of_range_pages_404_and_junk_falls_back_to_page_one(): void
    {
        $this->briefing('Only one');

        $this->get('/briefings?page=2')->assertNotFound();
        $this->get('/briefings?page=99999')->assertNotFound();
        $this->get('/briefings?page=abc')->assertOk()->assertSee('Only one');
        $this->get('/briefings?page=0')->assertOk()->assertSee('Only one');
    }

    public function test_the_rendered_list_is_served_from_cache(): void
    {
        $entry = $this->briefing('Original title');

        $this->get('/briefings')->assertSee('Original title');

        // Change the row behind Statamic's back (no event fired) - a cached page must not notice.
        $model = EntryModel::query()->findOrFail($entry->id());
        $model->data = array_merge($model->data, ['title' => 'Sneaky edit']);
        $model->save();

        $this->get('/briefings')
            ->assertSee('Original title')
            ->assertDontSee('Sneaky edit');
    }

    public function test_a_cache_hit_makes_no_database_queries(): void
    {
        $this->briefing('Cached briefing');
        $this->get('/briefings')->assertOk(); // miss: runs the count and renders the list

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->get('/briefings')->assertOk()->assertSee('Cached briefing');

        $this->assertSame([], DB::getQueryLog());
    }

    public function test_the_cached_count_is_refreshed_when_a_public_briefing_is_added(): void
    {
        foreach (range(1, BriefingController::PER_PAGE) as $i) {
            $this->briefing("Briefing {$i}");
        }

        // Exactly one page of briefings: the count (and so the page bounds) is now cached.
        $this->get('/briefings')->assertOk();
        $this->get('/briefings?page=2')->assertNotFound();

        $this->briefing('One more');

        $this->get('/briefings?page=2')->assertOk()->assertSee('Page 2 of 2');
    }

    public function test_saving_a_briefing_invalidates_the_cache(): void
    {
        $entry = $this->briefing('Original title');
        $this->get('/briefings')->assertSee('Original title');

        $entry->set('title', 'Edited title')->save();

        $this->get('/briefings')
            ->assertSee('Edited title')
            ->assertDontSee('Original title');
    }

    public function test_publishing_and_deleting_invalidate_the_cache(): void
    {
        $draft = $this->briefing('Soon public', published: false);
        $this->get('/briefings')->assertDontSee('Soon public');

        $draft->published(true)->save();
        $this->get('/briefings')->assertSee('Soon public');

        $draft->delete();
        $this->get('/briefings')->assertDontSee('Soon public');
    }

    private function listKey(): string
    {
        return BriefingsCache::listKey(1, BriefingController::PER_PAGE);
    }

    public function test_creating_a_draft_does_not_invalidate_the_cache(): void
    {
        $key = $this->listKey();

        $this->briefing('Just a draft', published: false);

        $this->assertSame($key, $this->listKey());
    }

    public function test_editing_a_draft_does_not_invalidate_the_cache(): void
    {
        $id = $this->briefing('Draft', published: false)->id();
        $key = $this->listKey();

        // Re-load it like the CP would, so Statamic's "original" state comes from the database.
        Entry::find($id)->set('title', 'Draft, edited')->save();

        $this->assertSame($key, $this->listKey());
    }

    public function test_deleting_a_draft_does_not_invalidate_the_cache(): void
    {
        $id = $this->briefing('Draft', published: false)->id();
        $key = $this->listKey();

        Entry::find($id)->delete();

        $this->assertSame($key, $this->listKey());
    }

    public function test_creating_a_published_briefing_invalidates_the_cache(): void
    {
        $key = $this->listKey();

        $this->briefing('Public from day one');

        $this->assertNotSame($key, $this->listKey());
    }

    public function test_publishing_a_draft_invalidates_the_cache(): void
    {
        $id = $this->briefing('Draft', published: false)->id();
        $key = $this->listKey();

        Entry::find($id)->published(true)->save();

        $this->assertNotSame($key, $this->listKey());
    }

    public function test_unpublishing_a_briefing_invalidates_the_cache_and_hides_it(): void
    {
        $id = $this->briefing('Going private')->id();
        $this->get('/briefings')->assertSee('Going private');
        $key = $this->listKey();

        // The saved entry is a draft after this, but it was public before - the list must still refresh.
        Entry::find($id)->published(false)->save();

        $this->assertNotSame($key, $this->listKey());
        $this->get('/briefings')->assertDontSee('Going private');
    }

    public function test_deleting_a_published_briefing_invalidates_the_cache(): void
    {
        $id = $this->briefing('Public')->id();
        $key = $this->listKey();

        Entry::find($id)->delete();

        $this->assertNotSame($key, $this->listKey());
    }

    public function test_changes_in_other_collections_do_not_invalidate_the_cache(): void
    {
        Collection::make('pages')->save();
        $key = BriefingsCache::listKey(1, BriefingController::PER_PAGE);

        Entry::make()->collection('pages')->slug('about')->data(['title' => 'About'])->save();

        $this->assertSame($key, BriefingsCache::listKey(1, BriefingController::PER_PAGE));
    }

    public function test_the_create_page_requires_login(): void
    {
        $this->get('/briefings/create')->assertRedirect();
    }
}
