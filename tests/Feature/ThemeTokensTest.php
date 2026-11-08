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

        foreach (['.btn', '.btn-primary', '.btn-secondary', '.card', '.field', '.badge', '.alert', '.link'] as $component) {
            $this->assertStringContainsString($component.' {', $css, "Missing component class {$component}.");
        }
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
