<?php

namespace App\Http\Controllers;

use App\Support\Briefings;
use App\Support\BriefingsCache;
use Illuminate\Support\Facades\Cache;

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

    /**
     * If the cache is empty, load the data from the database and return it as an array.
     *
     * @return array{count: int, latest: list<array{title: string, excerpt: string, url: string, date: string}>}
     */
    private function load(): array
    {
        return [
            'count' => Briefings::published()->count(),
            'latest' => Briefings::newestPublished()->limit(self::LATEST)->get()
                ->map(fn ($entry) => [
                    'title' => (string) $entry->get('title'),
                    'excerpt' => Briefings::excerpt($entry, 160),
                    'url' => (string) $entry->url(),
                    'date' => $entry->value('written_at')->format('M j, Y'),
                ])
                ->all(),
        ];
    }
}
