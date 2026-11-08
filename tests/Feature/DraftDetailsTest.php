<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Statamic\Contracts\Auth\User as UserContract;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\User;
use Tests\TestCase;

/**
 * Clicking a draft's title on My Drafts opens a read-only details page, like a published briefing.
 */
class DraftDetailsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        Collection::make('briefings')->routes('/briefings/{slug}')->template('briefings/show')->layout('layout')->save();
        Cache::flush();
    }

    private function contributor(string $id = 'me'): UserContract
    {
        return User::make()->id($id)->email($id.'@example.test')->assignRole('contributor');
    }

    private function draft(string $author = 'me', bool $published = false, string $title = 'My draft title', string $content = 'Body **bold** text'): string
    {
        $entry = Entry::make()
            ->collection('briefings')
            ->slug(str($title)->slug().'-'.uniqid())
            ->published($published)
            ->data(['title' => $title, 'content' => $content, 'author' => $author]);
        $entry->save();

        return $entry->id();
    }

    public function test_the_title_in_my_drafts_links_to_the_details_page(): void
    {
        $id = $this->draft();

        $html = $this->actingAs($this->contributor())->get(route('drafts.index'))->getContent();

        $this->assertMatchesRegularExpression(
            '/<h2[^>]*>\s*<a href="'.preg_quote(route('drafts.show', $id), '/').'"[^>]*>My draft title<\/a>/',
            $html,
        );
    }

    public function test_the_owner_sees_the_whole_draft_with_a_back_link(): void
    {
        $id = $this->draft(content: "First paragraph\n\nSecond paragraph with **bold** text");

        $this->actingAs($this->contributor())
            ->get(route('drafts.show', $id))
            ->assertOk()
            ->assertSee('<title>My draft title</title>', false)
            ->assertSee('Back to Drafts')
            ->assertSee('href="'.route('drafts.index').'"', false)
            ->assertSee('My draft title')
            ->assertSee('Draft')
            ->assertSee('Saved '.now()->format('F j, Y'))
            ->assertSee('<p>First paragraph</p>', false)
            ->assertSee('<strong>bold</strong>', false);
    }

    public function test_the_page_is_marked_as_my_drafts_in_the_navbar(): void
    {
        $id = $this->draft();

        $html = $this->actingAs($this->contributor())->get(route('drafts.show', $id))->getContent();

        $this->assertMatchesRegularExpression('/<a[^>]*aria-current="page"[^>]*>\s*My drafts\s*<\/a>/', $html);
    }

    public function test_someone_elses_draft_is_a_404_even_for_admins(): void
    {
        $id = $this->draft('me');

        $this->actingAs($this->contributor('other'))->get(route('drafts.show', $id))->assertNotFound();
        $this->actingAs(User::make()->id('admin')->email('a@example.test')->makeSuper())
            ->get(route('drafts.show', $id))
            ->assertNotFound();
    }

    public function test_unknown_ids_are_a_404(): void
    {
        $this->actingAs($this->contributor())->get(route('drafts.show', 'does-not-exist'))->assertNotFound();
    }

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $this->get(route('drafts.show', $this->draft()))->assertRedirect(cp_route('login'));
    }

    public function test_users_without_the_create_permission_get_a_403(): void
    {
        $id = $this->draft('nobody');

        $this->actingAs(User::make()->id('nobody')->email('nobody@example.test'))
            ->get(route('drafts.show', $id))
            ->assertForbidden();
    }

    public function test_a_draft_that_was_published_since_the_list_loaded_goes_to_the_public_page(): void
    {
        $id = $this->draft('me', published: true, title: 'Now public');
        $entry = Entry::find($id);

        $this->actingAs($this->contributor())
            ->get(route('drafts.show', $id))
            ->assertRedirect($entry->url());
    }

    public function test_the_title_and_markdown_are_escaped(): void
    {
        $id = $this->draft(
            title: '<script>alert(1)</script>',
            content: "<img src=x onerror=alert(1)>\n\n[click](javascript:alert(1))",
        );

        $this->actingAs($this->contributor())
            ->get(route('drafts.show', $id))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('<img src=x', false)
            ->assertDontSee('href="javascript:', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_the_edit_and_delete_icons_on_the_list_are_unchanged(): void
    {
        $id = $this->draft();

        $this->actingAs($this->contributor())
            ->get(route('drafts.index'))
            ->assertSee('href="'.route('drafts.edit', $id).'"', false)
            ->assertSee('action="'.route('drafts.destroy', $id).'"', false);
    }
}
