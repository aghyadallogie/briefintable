<?php

namespace Tests\Feature;

use App\Livewire\BriefingForm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Statamic\Contracts\Auth\User as UserContract;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Role;
use Statamic\Facades\User;
use Tests\TestCase;

/**
 * Contributors (the role in resources/users/roles.yaml) can submit briefings as drafts only
 * and never see the control panel. The tests read the real roles file and never write to it.
 * Users are left unsaved so nothing lands in /users.
 */
class ContributorRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        Collection::make('briefings')->routes('/briefings/{slug}')->template('briefings/show')->layout('layout')->save();
        Cache::flush();
    }

    private function user(string $id, ?string $role = null, bool $super = false): UserContract
    {
        $user = User::make()->id($id)->email($id.'@example.test');

        if ($role) {
            $user->assignRole($role);
        }

        return $super ? $user->makeSuper() : $user;
    }

    private function contributor(): UserContract
    {
        return $this->user('contrib-1', 'contributor');
    }

    private function submit(array $state = []): Testable
    {
        return Livewire::test(BriefingForm::class)
            ->set('title', 'From a contributor')
            ->set('content', 'Body')
            ->set('published', $state['published'] ?? false)
            ->call('save');
    }

    public function test_the_contributor_role_can_only_create_briefings(): void
    {
        $role = Role::find('contributor');

        $this->assertNotNull($role, 'resources/users/roles.yaml must define the contributor role.');
        $this->assertSame(['create briefings entries'], $role->permissions()->all());
    }

    public function test_the_form_card_rises_in_like_the_briefing_cards_but_does_not_lift_on_hover(): void
    {
        $html = $this->actingAs($this->contributor())->get('/briefings/create')->assertOk()->getContent();

        $this->assertSame(1, preg_match('/<div class="card rise-in[^"]*"/', $html, $card), 'The form card should use the shared entrance.');
        $this->assertStringNotContainsString('card-lift', $card[0], 'A form should not lift on hover.');
    }

    public function test_a_contributor_can_open_the_form_without_a_publish_option(): void
    {
        $this->actingAs($this->contributor())
            ->get('/briefings/create')
            ->assertOk()
            ->assertSee('Create a Briefing')
            ->assertDontSee('Publish immediately')
            ->assertSee('saved as a draft');
    }

    public function test_admins_still_see_the_publish_option(): void
    {
        $this->actingAs($this->user('admin-1', super: true))
            ->get('/briefings/create')
            ->assertOk()
            ->assertSee('Publish immediately');
    }

    public function test_a_contributor_submission_is_saved_as_a_draft(): void
    {
        $this->actingAs($this->contributor());

        $this->submit()->assertHasNoErrors()->assertSee('Submitted for review');

        $entry = Entry::query()->where('collection', 'briefings')->get()->sole();

        $this->assertFalse($entry->published());
        $this->assertSame('contrib-1', $entry->get('author'));
    }

    public function test_a_contributor_cannot_publish_by_forging_the_published_flag(): void
    {
        $this->actingAs($this->contributor());

        $this->submit(['published' => true])->assertHasNoErrors();

        $this->assertFalse(Entry::query()->where('collection', 'briefings')->get()->sole()->published());
        $this->get('/briefings')->assertDontSee('From a contributor');
    }

    public function test_an_admin_can_publish_from_the_form(): void
    {
        $this->actingAs($this->user('admin-1', super: true));

        $this->submit(['published' => true])->assertHasNoErrors();

        $this->assertTrue(Entry::query()->where('collection', 'briefings')->get()->sole()->published());
        $this->get('/briefings')->assertSee('From a contributor');
    }

    public function test_a_contributor_draft_does_not_invalidate_the_public_cache(): void
    {
        $this->actingAs($this->contributor());
        $this->get('/briefings')->assertOk();
        $before = Cache::get('briefings.list.version', 1);

        $this->submit();

        $this->assertSame($before, Cache::get('briefings.list.version', 1));
    }

    public function test_a_user_without_the_permission_gets_a_403_on_the_page(): void
    {
        $this->actingAs($this->user('nobody-1'))
            ->get('/briefings/create')
            ->assertForbidden();
    }

    public function test_a_user_without_the_permission_cannot_call_save(): void
    {
        // The Livewire action is a separate request from the page, so it must check on its own.
        $this->actingAs($this->user('nobody-1'));

        $this->submit()->assertForbidden();

        $this->assertCount(0, Entry::query()->where('collection', 'briefings')->get());
    }

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $this->get('/briefings/create')->assertRedirect(cp_route('login'));
    }

    public function test_a_contributor_cannot_see_the_control_panel(): void
    {
        $this->actingAs($this->contributor());

        $this->assertFalse($this->contributor()->can('access cp'));

        // Statamic's "Unauthorized" screen, not the dashboard.
        $this->get('/cp')->assertRedirect('/cp/auth/unauthorized');

        foreach (['/cp', '/cp/collections/briefings', '/cp/collections/briefings/entries/create/default'] as $url) {
            $response = $this->get($url);

            $this->assertContains($response->status(), [302, 403], "$url should not render for a contributor.");
            $response->assertDontSee('Statamic', false);
        }
    }
}
