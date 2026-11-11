<?php

namespace Tests\Feature;

use App\Support\BriefingsCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Statamic\Contracts\Auth\User as UserContract;
use Statamic\Eloquent\Entries\EntryModel;
use Statamic\Facades\Blink;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\User;
use Tests\TestCase;

/**
 * "Written by <name> on <date>" on the briefing page and "By <name> | <date>" in the list.
 */
class BylineTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<UserContract> */
    private array $saved = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        Collection::make('briefings')->routes('/briefings/{slug}')->template('briefings/show')->layout('layout')->save();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        // The author field resolves a stored user, and Statamic keeps users in /users (flat files).
        foreach ($this->saved as $user) {
            $user->delete();
        }

        parent::tearDown();
    }

    private function author(string $email, ?string $name = null): UserContract
    {
        $user = User::make()->email($email);

        if ($name !== null) {
            $user->set('name', $name);
        }

        $user->save();

        return $this->saved[] = $user;
    }

    private function briefing(?UserContract $author, bool $published = true, string $slug = 'a-briefing'): \Statamic\Entries\Entry
    {
        $entry = Entry::make()
            ->collection('briefings')
            ->slug($slug)
            ->published($published)
            ->data(array_filter(['title' => 'A briefing', 'content' => 'Body text', 'author' => $author?->id()]));
        $entry->save();

        return $entry;
    }

    public function test_the_briefing_page_shows_who_wrote_it_and_when(): void
    {
        $this->briefing($this->author('byline-named@example.test', 'Dana Writer'));

        $this->get('/briefings/a-briefing')
            ->assertOk()
            ->assertSee('Written by Dana Writer on '.now()->format('F j, Y'));
    }

    public function test_the_date_is_when_it_was_written_not_when_it_was_published(): void
    {
        $this->travelTo(now()->subDays(10));
        $entry = $this->briefing($this->author('byline-named@example.test', 'Dana Writer'), published: false);
        $written = now()->format('F j, Y');
        $this->travelBack();

        $entry->published(true)->save();

        $this->get('/briefings/a-briefing')
            ->assertOk()
            ->assertSee('on '.$written)
            ->assertDontSee('on '.now()->format('F j, Y'));
    }

    public function test_an_author_without_a_name_is_a_generic_contributor_and_the_email_is_never_shown(): void
    {
        $this->briefing($this->author('byline-nameless@example.test'));

        $this->get('/briefings/a-briefing')
            ->assertOk()
            ->assertSee('Written by a contributor on '.now()->format('F j, Y'))
            ->assertDontSee('byline-nameless');
    }

    public function test_a_briefing_with_no_author_says_a_contributor(): void
    {
        $this->briefing(null);

        $this->get('/briefings/a-briefing')->assertOk()->assertSee('Written by a contributor on');
    }

    public function test_the_list_shows_the_same_byline(): void
    {
        $this->briefing($this->author('byline-named@example.test', 'Dana Writer'), slug: 'one');
        $this->briefing($this->author('byline-nameless@example.test'), slug: 'two');

        $this->get('/briefings')
            ->assertOk()
            ->assertSee('By Dana Writer | '.now()->format('M j, Y'))
            ->assertSee('By a contributor | '.now()->format('M j, Y'))
            ->assertDontSee('byline-nameless');
    }

    /**
     * Statamic remembers entries in memory for the length of a request. Real requests start empty,
     * but one test process serves many, so this makes the next request load the entry afresh.
     */
    private function forgetEntriesHeldInMemory(): void
    {
        Blink::flush();
    }

    public function test_the_briefing_page_is_served_from_cache(): void
    {
        $entry = $this->briefing(null);

        $this->get('/briefings/a-briefing')->assertSee('A briefing');

        // Change the row behind Statamic's back (no event fired) - a cached page must not notice.
        $model = EntryModel::query()->findOrFail($entry->id());
        $model->data = array_merge($model->data, ['title' => 'Sneaky edit']);
        $model->save();
        $this->forgetEntriesHeldInMemory();

        $this->get('/briefings/a-briefing')
            ->assertSee('A briefing')
            ->assertDontSee('Sneaky edit');
    }

    public function test_saving_or_publishing_a_briefing_refreshes_its_cached_page(): void
    {
        $entry = $this->briefing(null);
        $this->get('/briefings/a-briefing')->assertSee('A briefing');

        $entry->set('title', 'Edited title')->save();
        $this->forgetEntriesHeldInMemory();

        $this->get('/briefings/a-briefing')
            ->assertSee('Edited title')
            ->assertDontSee('>A briefing<', false);
    }

    public function test_an_unpublished_briefing_page_is_not_served_from_a_stale_cache(): void
    {
        $entry = $this->briefing(null);
        $this->get('/briefings/a-briefing')->assertOk();

        $entry->published(false)->save();
        $this->forgetEntriesHeldInMemory();

        $this->get('/briefings/a-briefing')->assertNotFound();
    }

    public function test_a_drafts_cache_key_changes_with_every_edit_but_a_published_one_does_not(): void
    {
        $this->travelTo(now()->subHour());
        $draft = $this->briefing(null, published: false);
        $before = BriefingsCache::showKey($draft);
        $this->travelBack();

        $draft->set('title', 'Edited')->save();

        $this->assertNotSame($before, BriefingsCache::showKey($draft));
        $this->assertStringContainsString('.draft', BriefingsCache::showKey($draft));

        $draft->published(true)->save();

        $this->assertStringNotContainsString('.draft', BriefingsCache::showKey($draft));
    }

    public function test_author_names_are_escaped(): void
    {
        $this->briefing($this->author('byline-xss@example.test', '<script>alert(1)</script>'));

        $this->get('/briefings/a-briefing')
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;', false);
    }
}
