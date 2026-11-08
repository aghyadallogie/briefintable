<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * The look of the app lives in resources/css/site.css as design tokens. If a view uses a raw
 * palette colour instead, changing the tokens would no longer restyle that spot.
 */
class ThemeTokensTest extends TestCase
{
    /** Unused Statamic starter page, kept as it shipped. */
    private const IGNORED = ['home.antlers.html'];

    private function views(): array
    {
        return collect(File::allFiles(resource_path('views')))
            ->reject(fn ($file) => in_array($file->getFilename(), self::IGNORED, true))
            ->all();
    }

    public function test_views_use_theme_tokens_instead_of_raw_palette_colours(): void
    {
        $palette = 'slate|gray|zinc|neutral|stone|red|orange|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|violet|purple|fuchsia|pink|rose';
        $properties = 'bg|text|border|ring|ring-offset|divide|from|via|to|fill|stroke|outline|accent|placeholder|decoration|shadow|caret';
        $pattern = "/(?<![\w-])(?:[\w-]+:)*(?:(?:{$properties})-(?:{$palette})-\d{2,3}|(?:bg|text|border|ring)-(?:white|black))(?![\w-])/";

        $offenders = [];

        foreach ($this->views() as $file) {
            if (preg_match_all($pattern, $file->getContents(), $matches)) {
                $offenders[$file->getRelativePathname()] = array_values(array_unique($matches[0]));
            }
        }

        $this->assertSame([], $offenders, 'Use a token from resources/css/site.css (bg-primary, text-ink, ...) instead.');
    }

    public function test_the_stylesheet_defines_the_core_tokens_and_components(): void
    {
        $css = File::get(resource_path('css/site.css'));

        foreach (['--font-sans', '--font-heading', '--color-primary', '--color-primary-hover', '--color-page',
            '--color-surface', '--color-ink', '--color-muted', '--color-line', '--radius-card', '--shadow-card'] as $token) {
            $this->assertStringContainsString($token.':', $css, "Missing design token {$token}.");
        }

        foreach (['.btn', '.btn-primary', '.btn-secondary', '.card', '.field', '.badge', '.alert', '.link',
            '.rise-in', '.card-lift', '.card-actions', '.icon-btn', '.icon-btn-danger', '.nav-link', '.nav-link-active', '.menu-toggle', '.menu-panel', '.menu-item', '.skip-link'] as $component) {
            $this->assertStringContainsString($component.' {', $css, "Missing component class {$component}.");
        }
    }

    public function test_motion_is_defined_once_and_respects_reduced_motion(): void
    {
        $css = File::get(resource_path('css/site.css'));

        $this->assertStringContainsString('--animate-rise:', $css);
        $this->assertStringContainsString('--rise-stagger:', $css);
        $this->assertStringContainsString('@keyframes rise', $css);
        $this->assertStringContainsString('--animate-fade:', $css);
        $this->assertStringContainsString('@keyframes fade', $css);
        $this->assertStringContainsString('motion-reduce:animate-fade', $css, 'Reduced motion gets a fade with no movement, not nothing.');
        $this->assertStringNotContainsString('motion-reduce:animate-none', $css);
        $this->assertStringContainsString('motion-reduce:hover:translate-y-0', $css, 'The hover lift must not move for reduced motion.');
    }

    public function test_card_actions_appear_on_hover_or_focus_instantly_with_no_transition(): void
    {
        $css = File::get(resource_path('css/site.css'));

        // Hidden only for fine pointers that can hover, so touch screens always see the controls.
        $this->assertStringContainsString('@media (hover: hover) and (pointer: fine)', $css);
        $this->assertStringContainsString('.card:hover .card-actions', $css);
        $this->assertStringContainsString('.card:focus-within .card-actions', $css, 'Tabbing into a card must reveal its controls.');
        // Hidden with opacity (still focusable), never display:none.
        $this->assertMatchesRegularExpression('/\.card-actions\s*\{\s*opacity:\s*0;/', $css);

        // The change is instant: no transition on the actions or on the icon buttons.
        $this->assertSame(1, preg_match('/\.card-actions \{(.*?)\n        \}/s', $css, $actions), 'Missing .card-actions.');
        $this->assertStringNotContainsString('transition', $actions[1]);
        $this->assertSame(1, preg_match('/\.icon-btn \{(.*?)\n    \}/s', $css, $block), 'Missing .icon-btn.');
        $this->assertStringNotContainsString('transition', $block[1]);
        $this->assertStringNotContainsString('opacity', str_replace('disabled:opacity-60', '', $block[1]));
    }

    public function test_every_component_class_used_in_a_view_is_defined(): void
    {
        $css = File::get(resource_path('css/site.css'));
        $defined = ['btn', 'btn-sm', 'btn-primary', 'btn-secondary', 'btn-inverse', 'card', 'link', 'field',
            'badge', 'badge-success', 'badge-warning', 'alert', 'alert-success', 'alert-danger'];

        foreach ($defined as $class) {
            $this->assertStringContainsString(".{$class} {", $css, "Component .{$class} is not defined in site.css.");
        }

        // Anything that looks like one of our component families must be a defined one.
        foreach ($this->views() as $file) {
            preg_match_all('/class="([^"]*)"/', $file->getContents(), $attributes);

            foreach ($attributes[1] as $classList) {
                foreach (preg_split('/\s+/', $classList) as $class) {
                    if (preg_match('/^(btn|badge|alert)(-[a-z]+)?$/', $class)) {
                        $this->assertContains($class, $defined, "{$file->getRelativePathname()} uses undefined component class {$class}.");
                    }
                }
            }
        }
    }
}
