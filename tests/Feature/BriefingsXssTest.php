<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Tests\TestCase;

/**
 * Titles and markdown bodies are user input rendered on public pages (the list is
 * also cached and served to everyone), so none of them may reach the HTML as markup.
 */
class BriefingsXssTest extends TestCase
{
    use RefreshDatabase;

    private const SCRIPT = '<script>alert(1)</script>';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        Collection::make('briefings')
            ->routes('/briefings/{slug}')
            ->template('briefings/show')
            ->layout('layout')
            ->save();

        // Safe: TestCase guarantees this is the in-memory array store.
        Cache::flush();
    }

    private function briefing(string $title, string $content = 'Body'): string
    {
        Entry::make()
            ->collection('briefings')
            ->slug('xss-probe')
            ->published(true)
            ->data(['title' => $title, 'content' => $content])
            ->save();

        return '/briefings/xss-probe';
    }

    public function test_the_title_is_escaped_on_the_list(): void
    {
        $this->briefing('Hello '.self::SCRIPT);

        $this->get('/briefings')
            ->assertOk()
            ->assertDontSee(self::SCRIPT, false)
            ->assertSee('Hello &lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_the_title_is_escaped_on_the_show_page(): void
    {
        $url = $this->briefing('Hello '.self::SCRIPT);

        $this->get($url)
            ->assertOk()
            ->assertDontSee(self::SCRIPT, false)
            ->assertSee('Hello &lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_a_title_cannot_break_out_of_an_attribute(): void
    {
        $this->briefing('"><img src=x onerror=alert(1)>');

        $this->get('/briefings')
            ->assertOk()
            ->assertDontSee('<img src=x', false);
    }

    public function test_raw_html_in_the_markdown_body_is_escaped_on_the_show_page(): void
    {
        $url = $this->briefing('Safe title', "Hi\n\n".self::SCRIPT."\n\n<img src=x onerror=alert(1)>");

        $this->get($url)
            ->assertOk()
            ->assertDontSee(self::SCRIPT, false)
            ->assertDontSee('<img src=x', false);
    }

    public function test_unsafe_link_schemes_are_dropped_from_the_markdown_body(): void
    {
        $url = $this->briefing('Safe title', '[click](javascript:alert(1))');

        $this->get($url)
            ->assertOk()
            ->assertDontSee('href="javascript:', false);
    }

    public function test_the_list_excerpt_cannot_contain_markup_even_with_nested_tags(): void
    {
        // strip_tags alone can leave "<script>" behind when tags are nested like this.
        $this->briefing('Safe title', '<<b>script>alert(1)<</b>/script>');

        $this->get('/briefings')
            ->assertOk()
            ->assertDontSee(self::SCRIPT, false)
            ->assertDontSee('<script', false);
    }

    public function test_normal_markdown_still_renders(): void
    {
        $url = $this->briefing('Safe title', "**bold** and a [link](https://example.com)\n\n- one\n- two");

        $this->get($url)
            ->assertOk()
            ->assertSee('<strong>bold</strong>', false)
            ->assertSee('href="https://example.com"', false)
            ->assertSee('<li>one</li>', false);
    }
}
