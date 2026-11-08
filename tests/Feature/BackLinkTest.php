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
 * The form's Back button returns to the page the user came from, but only if that is
 * somewhere on this site and worth going back to.
 */
class BackLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        Collection::make('briefings')->routes('/briefings/{slug}')->template('briefings/show')->layout('layout')->save();
        Cache::flush();

        $this->actingAs($this->user());
    }

    private function user(): UserContract
    {
        return User::make()->id('me')->email('me@example.test')->assignRole('contributor');
    }

    private function draftId(): string
    {
        $entry = Entry::make()->collection('briefings')->slug('a-draft')->published(false)
            ->data(['title' => 'A draft', 'content' => 'Body', 'author' => 'me']);
        $entry->save();

        return $entry->id();
    }

    private function backLink(string $url)
    {
        return 'href="'.$url.'"';
    }

    public function test_create_goes_back_to_the_page_the_user_came_from(): void
    {
        $this->withHeader('referer', url('/briefings?page=2'))
            ->get(route('briefings.create'))
            ->assertOk()
            ->assertSee($this->backLink(url('/briefings?page=2')), false)
            ->assertSee('Back');
    }

    public function test_create_can_go_back_to_my_drafts_too(): void
    {
        $this->withHeader('referer', route('drafts.index'))
            ->get(route('briefings.create'))
            ->assertSee($this->backLink(route('drafts.index')), false);
    }

    public function test_edit_goes_back_to_where_the_user_came_from(): void
    {
        $this->withHeader('referer', route('drafts.index', ['page' => 2]))
            ->get(route('drafts.edit', $this->draftId()))
            ->assertOk()
            ->assertSee($this->backLink(route('drafts.index', ['page' => 2])), false);
    }

    public function test_without_a_referer_create_falls_back_to_the_briefings_list(): void
    {
        $this->get(route('briefings.create'))->assertSee($this->backLink(url('/briefings')), false);
    }

    public function test_without_a_referer_edit_falls_back_to_my_drafts(): void
    {
        $this->get(route('drafts.edit', $this->draftId()))->assertSee($this->backLink(route('drafts.index')), false);
    }

    public function test_an_off_site_referer_is_ignored(): void
    {
        $this->withHeader('referer', 'https://evil.example/phish')
            ->get(route('briefings.create'))
            ->assertDontSee('evil.example')
            ->assertSee($this->backLink(url('/briefings')), false);
    }

    public function test_a_look_alike_host_is_ignored(): void
    {
        $lookAlike = request()->getSchemeAndHttpHost().'.evil.example/x';

        $this->withHeader('referer', $lookAlike)
            ->get(route('briefings.create'))
            ->assertDontSee('evil.example')
            ->assertSee($this->backLink(url('/briefings')), false);
    }

    public function test_reloading_the_form_does_not_make_back_point_at_itself(): void
    {
        $this->withHeader('referer', route('briefings.create'))
            ->get(route('briefings.create'))
            ->assertSee($this->backLink(url('/briefings')), false);
    }

    public function test_coming_from_the_login_screen_falls_back(): void
    {
        $this->withHeader('referer', cp_route('login'))
            ->get(route('briefings.create'))
            ->assertDontSee('cp/auth/login')
            ->assertSee($this->backLink(url('/briefings')), false);
    }

    public function test_the_back_url_cannot_be_changed_from_the_browser(): void
    {
        $this->expectException(CannotUpdateLockedPropertyException::class);

        Livewire::test(BriefingForm::class)->set('backUrl', 'https://evil.example');
    }
}
