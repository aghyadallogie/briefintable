<?php

namespace Tests\Feature;

use App\Livewire\BriefingForm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Statamic\Contracts\Auth\User as UserContract;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\User;
use Tests\TestCase;

/**
 * Only the author can edit or delete a briefing, and only while it is still a draft.
 * Admins are not exempt here; they manage everything else in the CP.
 */
class EditDraftsTest extends TestCase
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

    private function admin(string $id = 'admin'): UserContract
    {
        return User::make()->id($id)->email($id.'@example.test')->makeSuper();
    }

    private function draft(string $author = 'me', bool $published = false, string $title = 'Original title'): string
    {
        $entry = Entry::make()
            ->collection('briefings')
            ->slug(str($title)->slug().'-'.uniqid())
            ->published($published)
            ->data(['title' => $title, 'content' => 'Original body', 'author' => $author]);
        $entry->save();

        return $entry->id();
    }

    private function entry(string $id)
    {
        return Entry::find($id);
    }

    private function edit(string $id)
    {
        return Livewire::test(BriefingForm::class, ['draft' => $id]);
    }

    public function test_the_owner_sees_the_edit_form_prefilled(): void
    {
        $id = $this->draft();

        $this->actingAs($this->contributor())
            ->get(route('drafts.edit', $id))
            ->assertOk()
            ->assertSee('Edit draft')
            ->assertSee('Save changes')
            ->assertSee('Original title')
            ->assertDontSee('Publish immediately');

        $this->edit($id)->assertSet('title', 'Original title')->assertSet('content', 'Original body');
    }

    public function test_the_owner_can_save_changes_and_keeps_the_slug_and_author(): void
    {
        $id = $this->draft();
        $slug = $this->entry($id)->slug();
        $this->actingAs($this->contributor());

        $this->edit($id)
            ->set('title', 'A completely new title')
            ->set('content', 'New body')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('drafts.index'));

        $entry = $this->entry($id);
        $this->assertSame('A completely new title', $entry->get('title'));
        $this->assertSame('New body', $entry->get('content'));
        $this->assertSame($slug, $entry->slug());
        $this->assertSame('me', $entry->get('author'));
        $this->assertFalse($entry->published());
        $this->assertCount(1, Entry::query()->where('collection', 'briefings')->get());
        $this->assertSame('Draft updated.', session('success'));
    }

    public function test_editing_still_validates(): void
    {
        $id = $this->draft();
        $this->actingAs($this->contributor());

        $this->edit($id)->set('title', '')->call('save')->assertHasErrors(['title' => 'required']);

        $this->assertSame('Original title', $this->entry($id)->get('title'));
    }

    public function test_a_contributor_cannot_publish_while_editing(): void
    {
        $id = $this->draft();
        $this->actingAs($this->contributor());

        $this->edit($id)->set('published', true)->call('save');

        $this->assertFalse($this->entry($id)->published());
    }

    public function test_an_admin_can_publish_their_own_draft_from_the_edit_form(): void
    {
        $id = $this->draft('admin', title: 'Going live');
        $this->actingAs($this->admin());

        $this->edit($id)->set('published', true)->call('save')->assertRedirect(route('drafts.index'));

        $this->assertTrue($this->entry($id)->published());
        $this->assertSame('Briefing published.', session('success'));
        $this->get('/briefings')->assertSee('Going live');
    }

    public function test_someone_elses_draft_is_a_404_for_everyone_including_admins(): void
    {
        $id = $this->draft('me');

        $this->actingAs($this->contributor('other'))->get(route('drafts.edit', $id))->assertNotFound();
        $this->actingAs($this->admin())->get(route('drafts.edit', $id))->assertNotFound();
        $this->actingAs($this->admin())->delete(route('drafts.destroy', $id))->assertNotFound();
    }

    public function test_a_published_briefing_cannot_be_edited_or_deleted_even_by_its_author(): void
    {
        $id = $this->draft('me', published: true);
        $this->actingAs($this->contributor());

        $this->get(route('drafts.edit', $id))->assertNotFound();
        $this->delete(route('drafts.destroy', $id))->assertNotFound();
        $this->assertNotNull($this->entry($id));
    }

    public function test_unknown_ids_are_a_404(): void
    {
        $this->actingAs($this->contributor());

        $this->get(route('drafts.edit', 'does-not-exist'))->assertNotFound();
        $this->delete(route('drafts.destroy', 'does-not-exist'))->assertNotFound();
    }

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $id = $this->draft();

        $this->get(route('drafts.edit', $id))->assertRedirect(cp_route('login'));
        $this->delete(route('drafts.destroy', $id))->assertRedirect(cp_route('login'));
        $this->assertNotNull($this->entry($id));
    }

    public function test_saving_is_rechecked_if_the_draft_was_published_after_the_page_loaded(): void
    {
        $id = $this->draft();
        $this->actingAs($this->contributor());
        $component = $this->edit($id);

        $this->entry($id)->published(true)->save();

        $component->set('title', 'Sneaky edit')->call('save')->assertNotFound();

        $this->assertSame('Original title', $this->entry($id)->get('title'));
    }

    public function test_saving_is_rechecked_if_the_session_user_changed(): void
    {
        $id = $this->draft();
        $this->actingAs($this->contributor());
        $component = $this->edit($id);

        $this->actingAs($this->contributor('other'));

        $component->set('title', 'Not yours')->call('save')->assertNotFound();

        $this->assertSame('Original title', $this->entry($id)->get('title'));
    }

    public function test_the_draft_id_cannot_be_tampered_with_from_the_browser(): void
    {
        $mine = $this->draft('me');
        $theirs = $this->draft('other', title: 'Their draft');
        $this->actingAs($this->contributor());

        $this->expectException(CannotUpdateLockedPropertyException::class);

        $this->edit($mine)->set('draftId', $theirs);
    }

    public function test_the_owner_can_delete_a_draft(): void
    {
        $id = $this->draft();
        $keep = $this->draft('me', title: 'Keep me');
        $this->actingAs($this->contributor());

        $this->delete(route('drafts.destroy', $id))
            ->assertRedirect(route('drafts.index'))
            ->assertSessionHas('success', 'Draft deleted.');

        $this->assertNull($this->entry($id));
        $this->assertNotNull($this->entry($keep));
    }

    public function test_someone_elses_draft_survives_a_delete_attempt(): void
    {
        $id = $this->draft('me');

        $this->actingAs($this->contributor('other'))->delete(route('drafts.destroy', $id))->assertNotFound();

        $this->assertNotNull($this->entry($id));
    }

    public function test_the_drafts_page_links_to_edit_and_delete(): void
    {
        $id = $this->draft();

        $this->actingAs($this->contributor())
            ->get(route('drafts.index'))
            ->assertOk()
            ->assertSee('href="'.route('drafts.edit', $id).'"', false)
            ->assertSee('action="'.route('drafts.destroy', $id).'"', false)
            ->assertSee('name="_method" value="DELETE"', false);
    }

    public function test_the_drafts_page_shows_the_flash_message_after_a_delete(): void
    {
        $id = $this->draft();

        $this->actingAs($this->contributor())
            ->followingRedirects()
            ->delete(route('drafts.destroy', $id))
            ->assertSee('Draft deleted.')
            ->assertSee('You have no drafts.');
    }

    public function test_editing_and_deleting_drafts_leave_the_public_cache_alone(): void
    {
        $id = $this->draft();
        $other = $this->draft('me', title: 'Another');
        $this->actingAs($this->contributor());
        $this->get('/briefings')->assertOk();
        $before = Cache::get('briefings.list.version', 1);

        $this->edit($id)->set('title', 'Changed')->call('save');
        $this->delete(route('drafts.destroy', $other));

        $this->assertSame($before, Cache::get('briefings.list.version', 1));
    }

    public function test_a_user_without_the_create_permission_cannot_use_the_routes(): void
    {
        $id = $this->draft('nobody');
        $nobody = User::make()->id('nobody')->email('nobody@example.test');

        $this->actingAs($nobody)->get(route('drafts.edit', $id))->assertForbidden();
        $this->actingAs($nobody)->delete(route('drafts.destroy', $id))->assertForbidden();
    }
}
