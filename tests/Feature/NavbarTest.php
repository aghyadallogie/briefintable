<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Statamic\Contracts\Auth\User as UserContract;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
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
        Cache::flush();
    }

    private function writer(): UserContract
    {
        return User::make()->id('nav-writer')->email('nav@example.test')->assignRole('contributor');
    }

    private function reader(): UserContract
    {
        // Signed in, but with no role: may not write.
        return User::make()->id('nav-reader')->email('reader@example.test');
    }

    /** The link with this text that is marked as the current page, if any. */
    private function currentLink(string $html, string $label): bool
    {
        return (bool) preg_match('/<a[^>]*aria-current="page"[^>]*>\s*'.preg_quote($label, '/').'\s*<\/a>/', $html);
    }

    public function test_the_brand_is_always_the_app_name_linking_home(): void
    {
        $this->get('/briefings')
            ->assertSee('href="'.route('home').'"', false)
            ->assertSee('Briefintable');

        $this->actingAs($this->writer())
            ->get('/briefings')
            ->assertSee('href="'.route('home').'"', false)
            ->assertSee('Briefintable');
    }

    public function test_guests_see_briefings_and_a_login_button_but_no_account_menu(): void
    {
        $this->get('/briefings')
            ->assertOk()
            ->assertSee('href="'.route('briefings.index').'"', false)
            ->assertSee('Log in')
            ->assertSee('href="'.route('login').'"', false)
            ->assertDontSee('My drafts')
            ->assertDontSee('Write a briefing')
            ->assertDontSee('Account menu')
            ->assertDontSee('Log out');
    }

    public function test_a_writer_sees_my_drafts_the_write_button_and_the_account_menu(): void
    {
        $this->actingAs($this->writer())
            ->get('/briefings')
            ->assertOk()
            ->assertSee('My drafts')
            ->assertSee('href="'.route('drafts.index').'"', false)
            ->assertSee('Write a briefing')
            ->assertSee('href="'.route('briefings.create').'"', false)
            ->assertSee('Account menu')
            ->assertSee('Signed in as')
            ->assertSee('nav@example.test')
            ->assertSee('Log out')
            ->assertDontSee('Log in');
    }

    public function test_the_write_button_is_hidden_on_the_write_form_itself(): void
    {
        $html = $this->actingAs($this->writer())->get('/briefings/create')->assertOk()->getContent();

        // The navbar button (desktop and phone) is gone, but the rest of the navbar stays.
        $this->assertSame(0, preg_match_all('/<a[^>]*btn-primary[^>]*>\s*Write a briefing\s*<\/a>/', $html));
        $this->assertStringContainsString('My drafts', $html);
        $this->assertStringContainsString('Account menu', $html);

        // And it is back everywhere else.
        $this->actingAs($this->writer())->get('/my-drafts')->assertSee('Write a briefing');
    }

    public function test_someone_who_cannot_write_is_not_offered_the_write_button(): void
    {
        $this->actingAs($this->reader())
            ->get('/briefings')
            ->assertOk()
            ->assertSee('My drafts')
            ->assertSee('Account menu')
            ->assertDontSee('Write a briefing');
    }

    public function test_the_account_initial_comes_from_the_email(): void
    {
        $html = $this->actingAs($this->writer())->get('/briefings')->getContent();

        $this->assertMatchesRegularExpression('/rounded-full[^>]*>\s*N\s*<\/span>/', $html);
    }

    public function test_the_logout_link_points_at_statamics_logout_and_returns_to_the_list(): void
    {
        $this->actingAs($this->writer())
            ->get('/briefings')
            ->assertSee(e(cp_route('logout', ['redirect' => '/briefings'])), false);
    }

    public function test_following_the_logout_link_logs_out_and_lands_on_the_list(): void
    {
        $this->actingAs($this->writer());

        $this->get(cp_route('logout', ['redirect' => '/briefings']))->assertRedirect('/briefings');

        $this->assertGuest();
    }

    public function test_the_email_is_escaped(): void
    {
        $this->actingAs(User::make()->id('nav-xss')->email('"><script>alert(1)</script>@example.test'))
            ->get('/briefings')
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;', false);
    }

    public function test_the_current_page_is_marked_and_links_never_disappear(): void
    {
        $user = $this->writer();
        Entry::make()->collection('briefings')->slug('a-briefing')->published(true)
            ->data(['title' => 'A briefing', 'content' => 'Body'])->save();

        $this->actingAs($user);

        // The list and a single briefing both mark "Briefings".
        foreach (['/briefings', '/briefings/a-briefing'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertTrue($this->currentLink($html, 'Briefings'), "Briefings should be current on {$url}");
            $this->assertFalse($this->currentLink($html, 'My drafts'));
            $this->assertStringContainsString('href="'.route('briefings.index').'"', $html);
        }

        $html = $this->get('/my-drafts')->assertOk()->getContent();
        $this->assertTrue($this->currentLink($html, 'My drafts'));
        $this->assertFalse($this->currentLink($html, 'Briefings'));
        $this->assertStringContainsString('href="'.route('briefings.index').'"', $html, 'Briefings stays visible on its own sibling page.');

        // The write form lives under /briefings but is not the "Briefings" page.
        $html = $this->get('/briefings/create')->assertOk()->getContent();
        $this->assertFalse($this->currentLink($html, 'Briefings'));

        // Nothing is current on the landing page.
        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringNotContainsString('aria-current="page"', $html);
    }

    public function test_the_links_markup_is_identical_on_every_page_so_they_never_move(): void
    {
        // Everything up to the end of the desktop links, with only the "current page" styling removed.
        $leftSide = function (string $url, ?UserContract $user) {
            $request = $user ? $this->actingAs($user) : $this;
            $html = $request->get($url)->assertOk()->getContent();

            $this->assertSame(1, preg_match('/<header.*?aria-label="Main".*?<\/nav>/s', $html, $m), "No navbar on {$url}");

            // The right-hand group is pushed over with ml-auto instead of spreading the bar with justify-between.
            $this->assertStringContainsString('ml-auto', $html);
            $this->assertStringNotContainsString('justify-between gap', $m[0]);

            return preg_replace(['/ aria-current="page"/', '/ nav-link-active/', '/\s+/'], ['', '', ' '], $m[0]);
        };

        $writer = $this->writer();

        // With the write button (list, drafts, landing) and without it (the write form itself).
        $baseline = $leftSide('/briefings', $writer);

        foreach (['/my-drafts', '/briefings/create', '/'] as $url) {
            $this->assertSame($baseline, $leftSide($url, $writer), "The links moved on {$url}.");
        }

        // A signed-in user who cannot write has no button at all, and the links still don't move.
        $this->assertSame($baseline, $leftSide('/briefings', $this->reader()));
    }

    public function test_the_phone_menu_has_the_same_links_and_button(): void
    {
        $html = $this->actingAs($this->writer())->get('/briefings')->getContent();

        $this->assertSame(1, preg_match('/aria-label="Mobile".*?<\/nav>/s', $html, $phone), 'Phone navigation is missing.');
        $this->assertStringContainsString('Briefings', $phone[0]);
        $this->assertStringContainsString('My drafts', $phone[0]);

        // Desktop row + phone panel each render the primary button.
        $this->assertSame(2, preg_match_all('/>\s*Write a briefing\s*<\/a>/', $html));
    }

    public function test_menus_are_native_details_elements_for_the_script_to_enhance(): void
    {
        $html = $this->actingAs($this->writer())->get('/briefings')->getContent();

        $this->assertSame(2, substr_count($html, '<details data-menu'), 'Account menu and phone menu.');
        $this->assertStringContainsString('href="#content"', $html, 'Skip link');
        $this->assertStringContainsString('<main id="content">', $html);
    }

    public function test_pages_built_on_the_blade_layout_have_the_skip_target_too(): void
    {
        $this->actingAs($this->writer())->get('/my-drafts')->assertSee('<main id="content">', false);
        $this->get('/')->assertSee('<main id="content">', false)->assertSee('<footer', false);
    }
}
