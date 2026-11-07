<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Statamic\Contracts\Auth\User as UserContract;
use Statamic\Facades\Collection;
use Statamic\Facades\User;
use Tests\TestCase;

/**
 * After a successful login on the Statamic login page people land on the public
 * briefings list, unless they were bounced to the login page from somewhere else.
 */
class LoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    private const EMAIL = 'login-redirect-test@example.test';

    private const PASSWORD = 'correct-horse-battery';

    private ?UserContract $user = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        Collection::make('briefings')->routes('/briefings/{slug}')->template('briefings/show')->layout('layout')->save();

        // Logging in needs a real stored user, and Statamic keeps users in /users (flat files).
        $this->user = User::make()->email(self::EMAIL)->password(self::PASSWORD);
        $this->user->save();
    }

    protected function tearDown(): void
    {
        $this->user?->delete();

        parent::tearDown();
    }

    private function login(string $password = self::PASSWORD)
    {
        return $this->post(cp_route('login'), ['email' => self::EMAIL, 'password' => $password]);
    }

    public function test_a_successful_login_redirects_to_the_briefings_list(): void
    {
        $this->login()->assertRedirect(route('briefings.index'));

        $this->assertAuthenticated();
    }

    public function test_control_panel_users_also_land_on_the_briefings_list(): void
    {
        $this->user->makeSuper()->save();

        $this->login()->assertRedirect(route('briefings.index'));
    }

    public function test_the_page_a_guest_originally_asked_for_still_wins(): void
    {
        $this->get('/briefings/create')->assertRedirect(cp_route('login'));

        $this->login()->assertRedirect(route('briefings.create'));
    }

    public function test_visiting_the_cp_first_sends_you_back_to_the_cp_after_login(): void
    {
        // Documents why the navbar must not link to /cp: a guest's request for /cp becomes the "intended" page.
        $this->get('/cp')->assertRedirect(cp_route('login'));

        $this->login()->assertRedirect(cp_route('index'));
    }

    public function test_the_navbar_login_link_lands_on_the_briefings_list(): void
    {
        $this->get('/briefings')->assertSee('href="'.route('login').'"', false);

        $this->get(route('login'))->assertRedirect(cp_route('login'));

        $this->login()->assertRedirect(route('briefings.index'));
    }

    public function test_the_login_link_forgets_a_stale_intended_page(): void
    {
        $this->get('/cp')->assertRedirect(cp_route('login'));

        $this->get(route('login'))->assertRedirect(cp_route('login'));

        $this->login()->assertRedirect(route('briefings.index'));
    }

    public function test_a_failed_login_does_not_redirect_to_the_briefings_list(): void
    {
        $this->login('wrong-password')->assertSessionHasErrors();

        $this->assertGuest();
    }
}
