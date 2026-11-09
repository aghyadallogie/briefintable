<?php

namespace Tests\Feature;

use App\Support\Briefings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Tests\TestCase;

/**
 * App\Support\Briefings holds the rules that were once repeated across files: what "published" means and
 * in what order briefings are shown. These tests pin those rules and that the pages follow them.
 */
class BriefingsQueryTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        Collection::make('briefings')->routes('/briefings/{slug}')->template('briefings/show')->layout('layout')->save();
        Collection::make('news')->routes('/news/{slug}')->save();
        Cache::flush();
    }

    private function entry(string $title, bool $published = true, ?int $publishedAt = null, string $author = 'someone', string $collection = 'briefings'): void
    {
        Entry::make()
            ->collection($collection)
            ->slug(str($title)->slug().'-'.++$this->sequence)
            ->published($published)
            ->data(array_filter(['title' => $title, 'content' => 'Body', 'author' => $author, 'published_at' => $publishedAt]))
            ->save();
    }

    private function titles($entries): array
    {
        return collect($entries)->map(fn ($entry) => $entry->get('title'))->values()->all();
    }

    public function test_published_excludes_drafts_and_other_collections(): void
    {
        $this->entry('Live');
        $this->entry('Draft', published: false);
        $this->entry('Elsewhere', collection: 'news');

        $this->assertSame(['Live'], $this->titles(Briefings::published()->get()));
    }

    public function test_newest_published_orders_by_publication_then_by_creation(): void
    {
        // Written first but published last, so it must come first even though it is the oldest entry.
        $this->travelTo(now()->subDays(3));
        $this->entry('Written first, published last', publishedAt: 3000);
        $this->travelTo(now()->addDay());
        $this->entry('Published first', publishedAt: 1000);
        $this->travelTo(now()->addDay());
        // Same publication time as the next one: the one written later wins the tie.
        $this->entry('Tie, written earlier', publishedAt: 2000);
        $this->travelTo(now()->addHour());
        $this->entry('Tie, written later', publishedAt: 2000);
        $this->travelBack();

        $this->assertSame(
            ['Written first, published last', 'Tie, written later', 'Tie, written earlier', 'Published first'],
            $this->titles(Briefings::newestPublished()->get()),
        );
    }

    public function test_the_antlers_sort_string_is_built_from_the_same_order(): void
    {
        $this->assertSame('published_at:desc|created_at:desc', Briefings::antlersSort());
    }

    public function test_drafts_of_returns_only_that_authors_unpublished_briefings_newest_first(): void
    {
        $this->travelTo(now()->subDays(2));
        $this->entry('Older draft', published: false, author: 'me');
        $this->travelTo(now()->addDay());
        $this->entry('Newer draft', published: false, author: 'me');
        $this->travelBack();
        $this->entry('Already live', author: 'me');
        $this->entry('Not mine', published: false, author: 'other');
        $this->entry('Elsewhere', published: false, author: 'me', collection: 'news');

        $this->assertSame(['Newer draft', 'Older draft'], $this->titles(Briefings::draftsOf('me')->get()));
    }

    public function test_the_public_list_and_the_landing_page_show_the_order_the_class_defines(): void
    {
        // Written in one order, published in another, so a page that sorted by creation would disagree.
        $this->travelTo(now()->subDays(4));
        $this->entry('Alpha', publishedAt: 4000);
        $this->travelTo(now()->addDay());
        $this->entry('Bravo', publishedAt: 1000);
        $this->travelTo(now()->addDay());
        $this->entry('Charlie', publishedAt: 3000);
        $this->travelTo(now()->addDay());
        $this->entry('Delta', publishedAt: 2000);
        $this->travelBack();

        $expected = $this->titles(Briefings::newestPublished()->get());
        $this->assertSame(['Alpha', 'Charlie', 'Delta', 'Bravo'], $expected);

        $this->get('/briefings')->assertOk()->assertSeeInOrder($expected);
        $this->get('/')->assertOk()->assertSeeInOrder(array_slice($expected, 0, 3));
    }

    public function test_the_list_template_does_not_hardcode_the_collection_or_the_sort(): void
    {
        $template = File::get(resource_path('views/briefings/index.antlers.html'));

        $this->assertStringNotContainsString('collection:briefings', $template);
        $this->assertStringNotContainsString('published_at', $template);
        $this->assertStringContainsString(':sort="sort"', $template);
    }
}
