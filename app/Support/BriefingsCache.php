<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;
use Statamic\Contracts\Entries\Entry;

/**
 * Manages version-stamped cache keys for the public briefings list.
 *
 * Because Laravel's file cache driver does not support tags, this class
 * uses a global version number instead. When published content changes,
 * the version is bumped, instantly making previous cache keys obsolete
 * so they naturally expire via TTL.
 */
class BriefingsCache
{
    private const VERSION_KEY = 'briefings.list.version';

    public const CACHE_TTL = '3 hours';

    public static function listKey(int $page, int $perPage): string
    {
        return sprintf('briefings.list.v%d.n%d.p%d', self::version(), $perPage, $page);
    }

    /** Key for the landing page's latest briefings and published count. */
    public static function homeKey(): string
    {
        return sprintf('briefings.home.v%d', self::version());
    }

    // Query is passed in from Controller to keep cache class decoupled from Statamic.
    public static function publishedCount(Closure $resolve): int
    {
        return (int) Cache::remember(
            sprintf('briefings.count.v%d', self::version()),
            now()->modify('+'.self::CACHE_TTL),
            $resolve,
        );
    }

    public static function bump(): void
    {
        Cache::forever(self::VERSION_KEY, self::version() + 1);
    }

    // Only bump cache version if entry is a briefing AND is published or was published.
    public static function bumpFor(Entry $entry): void
    {
        if ($entry->collectionHandle() !== 'briefings') {
            return;
        }

        if ($entry->published() || $entry->getOriginal('published')) {
            self::bump();
        }
    }

    private static function version(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 1);
    }
}
