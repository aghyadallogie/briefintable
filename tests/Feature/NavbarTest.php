<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Statamic\Facades\Collection;
use Statamic\Facades\User;
use Tests\TestCase;

class NavbarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        Collection::make('briefings')->routes('/briefings/{slug}')->template('briefings/show')->layout('layout')->save();
    }

    private function member(): \Statamic\Contracts\Auth\User
    {
        return User::make()->id('nav-user')->email('nav@example.test');
    }

    public function test_guests_see_the_app_name_instead_of_an_email(): void
    {
        $this->get('/briefings')
            ->assertOk()
            ->assertSee('Briefintable')
            ->assertDontSee('nav@example.test');
    }

    public function test_logged_in_users_see_their_email_instead_of_the_app_name(): void
    {
        $this->actingAs($this->member())
            ->get('/briefings')
            ->assertOk()
            ->assertSee('nav@example.test')
            ->assertDontSee('Briefintable');
    }

    public function test_the_email_is_escaped(): void
    {
        $this->actingAs(User::make()->id('nav-xss')->email('"><script>alert(1)</script>@example.test'))
            ->get('/briefings')
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_guests_see_login_and_no_logout(): void
    {
        $this->get('/briefings')
            ->assertOk()
            ->assertSee('Login')
            ->assertDontSee('Logout')
            ->assertDontSee('Create New');
    }

    public function test_logged_in_users_see_logout_instead_of_login(): void
    {
        $this->actingAs($this->member())
            ->get('/briefings')
            ->assertOk()
            ->assertSee('Logout')
            ->assertSee('Create New')
            ->assertDontSee('Login');
    }

    public function test_the_logout_link_points_at_statamics_logout_and_returns_to_the_list(): void
    {
        $this->actingAs($this->member())
            ->get('/briefings')
            ->assertSee(e(cp_route('logout', ['redirect' => '/briefings'])), false);
    }

    public function test_following_the_logout_link_logs_out_and_lands_on_the_list(): void
    {
        $this->actingAs($this->member());

        $this->get(cp_route('logout', ['redirect' => '/briefings']))->assertRedirect('/briefings');

        $this->assertGuest();
    }
}
