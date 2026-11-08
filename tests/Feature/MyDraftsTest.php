<?php

namespace Tests\Feature;

use App\Http\Controllers\BriefingController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Statamic\Contracts\Auth\User as UserContract;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\User;
use Tests\TestCase;

class MyDraftsTest extends TestCase
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

    private function user(string $id = 'me', bool $contributor = true): UserContract
    {
        $user = User::make()->id($id)->email($id.'@example.test');

        return $contributor ? $user->assignRole('contributor') : $user;
    }

    private function briefing(string $title, string $author, bool $published = false, string $content = 'Body'): void
    {
        Entry::make()
            ->collection('briefings')
            ->slug(str($title)->slug().'-'.++$this->sequence)
            ->published($published)
            ->data(['title' => $title, 'content' => $content, 'author' => $author])
            ->save();
    }

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $this->get(route('drafts.index'))->assertRedirect(cp_route('login'));
    }

    public function test_the_page_title_is_my_drafts(): void
    {
        $this->actingAs($this->user())
            ->get(route('drafts.index'))
            ->assertSee('<title>My Drafts</title>', false);
    }

    public function test_it_shows_only_my_own_unpublished_briefings(): void
    {
        $this->briefing('My draft', 'me');
        $this->briefing('My published one', 'me', published: true);
        $this->briefing('Someone elses draft', 'other');

        $this->actingAs($this->user())
            ->get(route('drafts.index'))
            ->assertOk()
            ->assertSee('My Drafts')
            ->assertSee('My draft')
            ->assertDontSee('My published one')
            ->assertDontSee('Someone elses draft');
    }

    public function test_it_lists_newest_first(): void
    {
        $this->travelTo(now()->subDays(2));
        $this->briefing('Oldest draft', 'me');
        $this->travelTo(now()->addDay());
        $this->briefing('Newest draft', 'me');
        $this->travelBack();

        $this->actingAs($this->user())
            ->get(route('drafts.index'))
            ->assertSeeInOrder(['Newest draft', 'Oldest draft']);
    }

    public function test_it_shows_an_empty_state(): void
    {
        $this->briefing('Not mine', 'other');

        $this->actingAs($this->user())
            ->get(route('drafts.index'))
            ->assertOk()
            ->assertSee('You have no drafts.');
    }

    public function test_it_paginates_and_404s_past_the_last_page(): void
    {
        foreach (range(1, BriefingController::PER_PAGE + 1) as $i) {
            $this->briefing("Draft number $i", 'me');
        }

        $this->actingAs($this->user());

        $this->get(route('drafts.index'))->assertOk()->assertSee('Page 1 of 2');
        $this->get(route('drafts.index', ['page' => 2]))->assertOk()->assertSee('Page 2 of 2');
        $this->get(route('drafts.index', ['page' => 3]))->assertNotFound();
    }

    public function test_titles_and_excerpts_are_escaped(): void
    {
        $this->briefing('<script>alert(1)</script>', 'me', content: '<img src=x onerror=alert(1)> **bold**');

        $this->actingAs($this->user())
            ->get(route('drafts.index'))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('<img src=x', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_the_page_is_not_served_from_the_shared_list_cache(): void
    {
        $this->briefing('Secret draft', 'me');

        $this->actingAs($this->user('me'))->get(route('drafts.index'))->assertSee('Secret draft');

        // Another user must not see it, even though "me" just rendered the page.
        $this->actingAs($this->user('other'))->get(route('drafts.index'))->assertDontSee('Secret draft');
    }

    public function test_the_page_has_no_write_link_of_its_own(): void
    {
        // The navbar's "Write a briefing" button already covers it, so the page body has none.
        $html = $this->actingAs($this->user())->get(route('drafts.index'))->getContent();

        preg_match('/<main id="content">(.*)<\/main>/s', $html, $main);

        $this->assertNotEmpty($main, 'Expected the page content inside <main id="content">.');
        $this->assertStringNotContainsString('Write a briefing', $main[1]);
        $this->assertStringNotContainsString(route('briefings.create'), $main[1]);
    }

    public function test_the_navbar_email_links_to_the_drafts_page(): void
    {
        $this->actingAs($this->user())
            ->get('/briefings')
            ->assertSee('href="'.route('drafts.index').'"', false)
            ->assertSee('me@example.test');
    }
}
