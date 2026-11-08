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

    public function test_the_brand_is_always_the_logo_linking_home(): void
    {
        $brand = '<a href="'.route('home').'" class="brand" aria-label="Briefintable, home" title="Briefintable">';

        $this->get('/briefings')->assertSee($brand, false);
        $this->actingAs($this->writer())->get('/briefings')->assertSee($brand, false);
    }

    public function test_the_brand_is_a_logo_circle_plus_wordmark_named_for_screen_readers(): void
    {
        $html = $this->get('/briefings')->getContent();

        $this->assertSame(1, preg_match('/<a [^>]*class="brand".*?<\/a>/s', $html, $brand), 'Missing brand link.');

        // The "B" circle and the wordmark are both inside the one link...
        $this->assertMatchesRegularExpression('/<span class="logo-mark" aria-hidden="true"><span class="text-2xl">B<\/span><\/span>/', $brand[0]);
        $this->assertMatchesRegularExpression('/<span class="brand-name" aria-hidden="true">[^<]+<\/span>/', $brand[0]);

        // ...which is named by the link itself, so the decorative letter isn't read out as "B".
        $this->assertStringContainsString('aria-label="Briefintable, home"', $brand[0]);
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

        // The desktop button is invisible, but still there so it keeps its space and nothing beside it moves.
        // It is out of the tab order and hidden from screen readers.
        $this->assertSame(1, preg_match_all('/<a [^>]*class="btn btn-primary btn-sm invisible hidden md:inline-flex" aria-hidden="true" tabindex="-1">\s*Write a briefing\s*<\/a>/', $html));
        $this->assertSame(0, preg_match_all('/<a [^>]*class="btn btn-primary btn-sm hidden md:inline-flex"[^>]*>\s*Write a briefing/', $html), 'No visible button.');

        // The phone menu has no button at all here.
        preg_match('/id="mobile-menu".*?<\/header>/s', $html, $phone);
        $this->assertStringNotContainsString('Write a briefing', $phone[0]);

        // The rest of the navbar stays.
        $this->assertStringContainsString('My drafts', $html);
        $this->assertStringContainsString('Account menu', $html);

        // And it is a normal visible button everywhere else.
        $other = $this->actingAs($this->writer())->get('/my-drafts')->getContent();
        $this->assertSame(1, preg_match_all('/<a [^>]*class="btn btn-primary btn-sm hidden md:inline-flex"[^>]*>\s*Write a briefing\s*<\/a>/', $other));
        $this->assertStringNotContainsString('class="btn btn-primary btn-sm invisible', $other);
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

    public function test_the_account_dropdown_has_only_the_email_and_log_out(): void
    {
        $html = $this->actingAs($this->writer())->get('/briefings')->getContent();

        $this->assertSame(1, preg_match('/<div id="account-menu".*?<\/div>/s', $html, $menu), 'Missing account dropdown.');

        // "My drafts" is already a link in the bar, so it is not repeated in the dropdown.
        $this->assertStringNotContainsString('My drafts', $menu[0]);
        $this->assertStringContainsString('Signed in as', $menu[0]);
        $this->assertStringContainsString('nav@example.test', $menu[0]);
        $this->assertSame(1, preg_match_all('/class="menu-item"/', $menu[0]), 'Only one action: Log out.');
        $this->assertStringContainsString('Log out', $menu[0]);

        // It is still reachable from the bar and from the phone menu.
        $this->assertSame(1, preg_match('/aria-label="Main".*?<\/nav>/s', $html, $main));
        $this->assertStringContainsString('My drafts', $main[0]);
        $this->assertSame(1, preg_match('/aria-label="Mobile".*?<\/nav>/s', $html, $phone));
        $this->assertStringContainsString('My drafts', $phone[0]);
    }

    public function test_the_account_button_is_a_user_profile_icon_not_an_initial(): void
    {
        $html = $this->actingAs($this->writer())->get('/briefings')->getContent();

        $this->assertSame(1, preg_match('/<button [^>]*x-ref="accountToggle".*?<\/button>/s', $html, $button), 'Missing account button.');

        // A decorative user icon (head and shoulders) plus a screen-reader label.
        $this->assertMatchesRegularExpression('/<svg [^>]*aria-hidden="true"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" \/><circle cx="12" cy="7" r="4" \/><\/svg>/', $button[0]);
        $this->assertStringContainsString('<span class="sr-only">Account menu</span>', $button[0]);

        // No letter from the email in the button, and the email stays out of it entirely.
        $this->assertStringNotContainsString('rounded-full', $button[0]);
        $this->assertStringNotContainsString('nav@example.test', $button[0]);
        $this->assertSame('Account menu', trim(strip_tags($button[0])));

        // The email is still shown, inside the dropdown.
        $this->assertStringContainsString('nav@example.test', $html);
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
        // The whole desktop navbar (brand, links, button, account menu), normalised so that only the things
        // that never change layout are ignored: the "current page" styling, and whether the button is shown or
        // merely invisible (it keeps its space either way).
        $desktop = function (string $url, UserContract $user) {
            $html = $this->actingAs($user)->get($url)->assertOk()->getContent();

            $this->assertSame(1, preg_match('/<header.*?<!-- Phone menu -->/s', $html, $m), "No navbar on {$url}");

            // Brand on the left; links, button and menus are one group pushed to the right (ml-auto).
            $this->assertStringContainsString('ml-auto flex items-center', $m[0]);
            $this->assertStringNotContainsString('justify-between', $m[0]);
            $this->assertLessThan(strpos($m[0], 'aria-label="Main"'), strpos($m[0], 'class="brand"'), 'The brand comes first, on the left.');
            $this->assertGreaterThan(strpos($m[0], 'class="ml-auto'), strpos($m[0], 'aria-label="Main"'), 'The links are inside the right-hand group.');

            return preg_replace(
                ['/ aria-current="page"/', '/ nav-link-active/', '/btn-sm invisible hidden md:inline-flex" aria-hidden="true" tabindex="-1"/', '/\s+/'],
                ['', '', 'btn-sm hidden md:inline-flex"', ' '],
                $m[0],
            );
        };

        $writer = $this->writer();

        // The list, My drafts and the landing page show the button; the write form hides it. Identical layout.
        $baseline = $desktop('/briefings', $writer);

        foreach (['/my-drafts', '/briefings/create', '/'] as $url) {
            $this->assertSame($baseline, $desktop($url, $writer), "The links moved on {$url}.");
        }

        // Someone who cannot write has no button at all, so their links sit differently from a writer's
        // but are just as steady from page to page.
        $reader = $this->reader();
        $readerBaseline = $desktop('/briefings', $reader);

        foreach (['/my-drafts', '/'] as $url) {
            $this->assertSame($readerBaseline, $desktop($url, $reader), "The links moved on {$url} for a reader.");
        }
    }

    public function test_the_links_are_on_the_right_after_the_brand(): void
    {
        $html = $this->get('/briefings')->getContent();

        $brand = strpos($html, 'class="brand"');
        $rightGroup = strpos($html, 'class="ml-auto flex items-center');
        $links = strpos($html, 'aria-label="Main"');

        $this->assertNotFalse($brand);
        $this->assertLessThan($rightGroup, $brand);
        $this->assertLessThan($links, $rightGroup, 'The links belong to the right-hand group, not next to the logo.');
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

    public function test_the_menus_are_alpine_dropdowns_with_table_medias_transition_classes(): void
    {
        $html = $this->actingAs($this->writer())->get('/briefings')->getContent();

        $this->assertStringNotContainsString('<details', $html);

        // One piece of state for the header: one menu open at a time, closed by an outside click or Escape.
        $this->assertStringContainsString('x-data="{ menu: null }"', $html);
        $this->assertStringContainsString('@click.outside="menu = null"', $html);
        $this->assertStringContainsString('@keydown.escape.window=', $html);

        // Account menu and phone menu both animate: 150ms ease-out in, 100ms ease-in out.
        foreach ([
            'x-transition:enter="transition ease-out duration-150"' => 2,
            'x-transition:enter-end="opacity-100 scale-100"' => 1,
            'x-transition:enter-end="opacity-100 translate-y-0"' => 1,
            'x-transition:leave="transition ease-in duration-100"' => 2,
            'x-transition:leave-end="opacity-0 scale-95"' => 1,
            'x-transition:leave-end="opacity-0 -translate-y-2"' => 1,
        ] as $attribute => $times) {
            $this->assertSame($times, substr_count($html, $attribute), "Expected {$times}x {$attribute}");
        }

        // Closed until Alpine starts, announced correctly to assistive tech, and no flash of open menus.
        $this->assertSame(2, preg_match_all('/<div id="(?:account|mobile)-menu" x-cloak x-show=/', $html));
        $this->assertSame(2, substr_count($html, 'aria-expanded="false" :aria-expanded='));
        $this->assertStringContainsString('origin-top-right', $html);
    }

    public function test_a_guest_only_has_the_phone_menu(): void
    {
        $html = $this->get('/briefings')->getContent();

        $this->assertStringNotContainsString('id="account-menu"', $html);
        $this->assertStringContainsString('id="mobile-menu"', $html);
        $this->assertSame(1, substr_count($html, 'x-transition:enter="transition ease-out duration-150"'));
    }

    public function test_skip_link_and_main_landmark_are_present(): void
    {
        $html = $this->actingAs($this->writer())->get('/briefings')->getContent();

        $this->assertStringContainsString('href="#content"', $html, 'Skip link');
        $this->assertStringContainsString('<main id="content">', $html);
    }

    public function test_alpine_is_wired_up_without_clashing_with_livewire(): void
    {
        $js = file_get_contents(resource_path('js/site.js'));
        $package = json_decode(file_get_contents(base_path('package.json')), true);

        $this->assertArrayHasKey('alpinejs', $package['dependencies'] ?? $package['devDependencies'] ?? []);
        // Livewire ships its own Alpine; starting a second copy would break both.
        $this->assertStringContainsString('if (!window.Alpine)', $js);
        $this->assertStringContainsString("import('alpinejs')", $js);
        $this->assertStringContainsString('Alpine.start()', $js);

        $css = file_get_contents(resource_path('css/site.css'));
        $this->assertStringContainsString('[x-cloak]', $css);
    }

    public function test_pages_built_on_the_blade_layout_have_the_skip_target_too(): void
    {
        $this->actingAs($this->writer())->get('/my-drafts')->assertSee('<main id="content">', false);
        $this->get('/')->assertSee('<main id="content">', false)->assertSee('<footer', false);
    }
}
