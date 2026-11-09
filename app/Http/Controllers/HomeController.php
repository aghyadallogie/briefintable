<?php

namespace App\Http\Controllers;

use App\Support\Briefings;
use App\Support\BriefingsCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * The landing page at "/".
 *
 * Everything that depends on the database (the published count and the newest briefings)
 * is cached as plain strings under the same version as the public list, so publishing,
 * unpublishing or deleting a briefing refreshes it immediately and a cache hit costs no queries.
 */
class HomeController extends Controller
{
    private const LATEST = 3;

    public function __invoke()
    {
        $data = Cache::remember(
            BriefingsCache::homeKey(),
            now()->modify('+'.BriefingsCache::CACHE_TTL),
            fn () => $this->load(),
        );

        return view('landing', $data);
    }

    /** @return array{count: int, latest: list<array{title: string, excerpt: string, url: string, date: string}>} */
    private function load(): array
    {
        return [
            'count' => Briefings::published()->count(),
            'latest' => Briefings::newestPublished()->limit(self::LATEST)->get()
                ->map(fn ($entry) => [
                    'title' => (string) $entry->get('title'),
                    'excerpt' => Str::limit(html_entity_decode(strip_tags((string) $entry->augmentedValue('content'))), 160),
                    'url' => (string) $entry->url(),
                    // The date the list and the briefing page show, not "last modified", which changes
                    // whenever an admin edits the entry.
                    'date' => $entry->value('written_at')->format('M j, Y'),
                ])
                ->all(),
        ];
    }
}
