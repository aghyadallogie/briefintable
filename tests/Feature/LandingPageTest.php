<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Statamic\Contracts\Auth\User as UserContract;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\User;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        Collection::make('briefings')->routes('/briefings/{slug}')->template('briefings/show')->layout('layout')->save();
        Cache::flush();
    }

    private function briefing(string $title, bool $published = true, string $content = 'Body text'): \Statamic\Entries\Entry
    {
        $entry = Entry::make()
            ->collection('briefings')
            ->slug(str($title)->slug().'-'.++$this->sequence)
            ->published($published)
            ->data(['title' => $title, 'content' => $content, 'author' => 'someone']);
        $entry->save();

        return $entry;
    }

    private function contributor(): UserContract
    {
        return User::make()->id('me')->email('me@example.test')->assignRole('contributor');
    }

    public function test_the_landing_page_renders_for_guests(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Clear, reviewed briefings.')
            ->assertSee('<title>Briefintable | Clear, reviewed briefings</title>', false)
            ->assertSee('name="description"', false)
            ->assertSee('href="'.route('briefings.index').'"', false)
            ->assertSee('Read the briefings')
            ->assertSee('href="'.route('login').'"', false)
            ->assertSee('Ready to write one?')
            ->assertSee('How it works');
    }

    public function test_the_brand_link_goes_home(): void
    {
        $this->get('/briefings')->assertSee('href="'.route('home').'"', false);
    }

    public function test_logged_in_users_see_their_own_shortcuts_instead_of_the_login_prompts(): void
    {
        $this->actingAs($this->contributor())
            ->get('/')
            ->assertOk()
            ->assertSee('My drafts')
            ->assertSee('href="'.route('drafts.index').'"', false)
            ->assertSee('Write a briefing')
            ->assertSee('href="'.route('briefings.create').'"', false)
            ->assertDontSee('Ready to write one?');
    }

    public function test_users_who_cannot_write_are_not_offered_the_write_button(): void
    {
        $this->actingAs(User::make()->id('nobody')->email('nobody@example.test'))
            ->get('/')
            ->assertOk()
            ->assertDontSee('Write a briefing');
    }

    public function test_it_shows_the_three_newest_published_briefings_and_hides_drafts(): void
    {
        $this->travelTo(now()->subDays(5));
        $this->briefing('Fourth newest');
        $this->travelTo(now()->addDay());
        $this->briefing('Third newest');
        $this->travelTo(now()->addDay());
        $this->briefing('Second newest');
        $this->travelTo(now()->addDay());
        $this->briefing('Newest');
        $this->briefing('Hidden draft', published: false);
        $this->travelBack();

        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Newest', 'Second newest', 'Third newest'])
            ->assertDontSee('Fourth newest')
            ->assertDontSee('Hidden draft')
            ->assertSee('4 briefings published');
    }

    public function test_it_orders_the_latest_by_publication_date_not_creation_date(): void
    {
        $this->travelTo(now()->subDays(10));
        $draft = $this->briefing('Published last', published: false);

        $this->travelTo(now()->addDays(5));
        $this->briefing('Published first');

        $this->travelTo(now()->addDays(5));
        $draft->published(true)->save();
        $this->travelBack();

        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Published last', 'Published first']);
    }

    public function test_the_latest_cards_rise_in_one_after_another_and_lift_on_hover(): void
    {
        foreach (['One', 'Two', 'Three'] as $title) {
            $this->briefing($title);
        }

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(3, preg_match_all('/<article class="card card-lift rise-in[^"]*" style="--i: (\d+)">/', $html, $m));
        $this->assertSame(['0', '1', '2'], $m[1]);
    }

    public function test_it_has_a_friendly_empty_state_and_singular_count(): void
    {
        $this->get('/')->assertSee('The first briefings are on their way.')->assertSee('0 briefings published');

        $this->briefing('Only one');
        Cache::flush();

        $this->get('/')->assertSee('1 briefing published')->assertDontSee('The first briefings are on their way.');
    }

    public function test_cards_link_to_the_briefing_and_show_a_plain_text_excerpt(): void
    {
        $entry = $this->briefing('Linked one', content: '**Bold** start of the body');

        $this->get('/')
            ->assertSee('href="'.$entry->url().'"', false)
            ->assertSee('Bold start of the body')
            ->assertDontSee('<strong>', false);
    }

    public function test_titles_and_excerpts_are_escaped(): void
    {
        $this->briefing('<script>alert(1)</script>', content: '<img src=x onerror=alert(1)> text');

        $this->get('/')
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('<img src=x', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_publishing_updates_the_page_immediately_but_drafts_do_not_bump_the_cache(): void
    {
        $this->get('/')->assertSee('0 briefings published');
        $before = Cache::get('briefings.list.version', 1);

        $this->briefing('Still a draft', published: false);
        $this->assertSame($before, Cache::get('briefings.list.version', 1));
        $this->get('/')->assertDontSee('Still a draft')->assertSee('0 briefings published');

        $this->briefing('Now live');
        $this->get('/')->assertSee('Now live')->assertSee('1 briefing published');
    }

    public function test_a_cache_hit_makes_no_database_queries_for_the_data(): void
    {
        $this->briefing('Cached one');
        $this->get('/')->assertSee('Cached one');

        DB::enableQueryLog();
        DB::flushQueryLog();

        $this->get('/')->assertSee('Cached one');

        $entryQueries = collect(DB::getQueryLog())->filter(fn ($q) => str_contains($q['query'], 'entries'));
        $this->assertCount(0, $entryQueries, 'The landing data should come from the cache.');
    }
}
