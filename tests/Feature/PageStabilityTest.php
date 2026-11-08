<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Statamic\Contracts\Auth\User as UserContract;
use Statamic\Facades\Collection;
use Statamic\Facades\User;
use Tests\TestCase;

/**
 * Moving between pages must not make the navbar flicker or shift. Two things caused that:
 *
 *  1. A scrollbar on long pages but not short ones made the centred navbar slide ~15px sideways.
 *  2. The web font loaded with `font-display: swap`, so every page first painted in a fallback serif
 *     and then jumped to Roboto Slab.
 *
 * These tests pin the fixes. They check the source and the rendered markup; they cannot watch a browser paint.
 */
class PageStabilityTest extends TestCase
{
    use RefreshDatabase;

    private const FONT = 'roboto-slab-latin-wght-normal.woff2';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        Collection::make('briefings')->routes('/briefings/{slug}')->template('briefings/show')->layout('layout')->save();
        Cache::flush();
    }

    private function writer(): UserContract
    {
        return User::make()->id('stable-writer')->email('stable@example.test')->assignRole('contributor');
    }

    private function css(): string
    {
        return File::get(resource_path('css/site.css'));
    }

    public function test_room_for_the_scrollbar_is_always_reserved(): void
    {
        // Otherwise a short page is ~15px wider than a long one and the centred navbar slides sideways.
        $this->assertMatchesRegularExpression('/html\s*\{\s*scrollbar-gutter:\s*stable;\s*\}/', $this->css());
    }

    public function test_the_latin_font_uses_font_display_block_and_is_declared_by_us(): void
    {
        $css = $this->css();

        // The package's own stylesheet uses `swap`, which flashes a fallback font first. We declare the faces ourselves.
        $this->assertStringNotContainsString('@import "@fontsource-variable/roboto-slab"', $css);

        $this->assertSame(1, preg_match('/@font-face\s*\{[^}]*'.preg_quote(self::FONT, '/').'[^}]*\}/s', $css, $face), 'Missing the Latin @font-face.');
        $this->assertMatchesRegularExpression('/font-display:\s*block;/', $face[0]);
        $this->assertStringContainsString('"Roboto Slab Variable"', $face[0]);

        // The file it points at really exists in the package.
        $this->assertFileExists(base_path('node_modules/@fontsource-variable/roboto-slab/files/'.self::FONT));
    }

    public function test_every_page_preloads_the_same_font_file_before_the_stylesheet(): void
    {
        $writer = $this->writer();

        foreach (['/', '/briefings', '/my-drafts', '/briefings/create'] as $url) {
            $html = $this->actingAs($writer)->get($url)->assertOk()->getContent();

            $this->assertSame(1, preg_match('/<link rel="preload" href="[^"]*" as="font" type="font\/woff2" crossorigin>/', $html, $preload), "No font preload on {$url}.");

            // Preloaded early in the head, ahead of the page's own assets.
            $this->assertLessThan(strpos($html, '<link rel="icon"'), strpos($html, $preload[0]));
        }
    }

    public function test_antlers_and_blade_pages_share_the_same_head_extras(): void
    {
        $extras = function (string $html) {
            preg_match('/<link rel="preload".*?<meta name="theme-color"[^>]*>/s', $html, $m);

            return preg_replace('/\s+/', ' ', $m[0] ?? '');
        };

        $blade = $extras($this->get('/')->getContent());      // Blade layout
        $antlers = $extras($this->get('/briefings')->getContent()); // Antlers layout

        $this->assertNotSame('', $blade);
        $this->assertSame($blade, $antlers, 'Both layouts must emit identical preload, icon and theme-colour tags.');
    }

    public function test_the_navbar_is_the_same_markup_on_every_page_type(): void
    {
        // The Antlers pages and the Blade pages render the very same navbar component, wrapped the same way.
        $writer = $this->writer();
        $navbar = function (string $url) use ($writer) {
            $html = $this->actingAs($writer)->get($url)->assertOk()->getContent();

            preg_match('/<body[^>]*>\s*(<header.*?<\/header>)\s*<main id="content">/s', $html, $m);
            $this->assertNotEmpty($m, "The navbar must be the first thing in <body> on {$url}.");

            // Only these may differ: the "current page" highlight, the hidden-but-space-keeping button, and the
            // phone menu's own button (absent on the write form). None of them move anything in the bar itself.
            return preg_replace(
                ['/ aria-current="page"/', '/ nav-link-active/', '/ font-semibold text-primary/', '/btn-sm invisible hidden md:inline-flex" aria-hidden="true" tabindex="-1"/', '/<a [^>]*class="btn btn-primary mt-3 w-full">[^<]*<\/a>/', '/\s+/'],
                ['', '', '', 'btn-sm hidden md:inline-flex"', '', ' '],
                $m[1],
            );
        };

        $baseline = $navbar('/briefings'); // Antlers

        foreach (['/', '/my-drafts', '/briefings/create'] as $url) { // Blade and Livewire
            $this->assertSame($baseline, $navbar($url), "The navbar differs on {$url}.");
        }
    }

    public function test_the_body_has_the_same_classes_in_both_layouts(): void
    {
        preg_match('/<body class="([^"]*)"/', $this->get('/')->getContent(), $blade);
        preg_match('/<body class="([^"]*)"/', $this->get('/briefings')->getContent(), $antlers);

        $this->assertNotEmpty($blade);
        $this->assertSame($blade[1], $antlers[1]);
    }
}
