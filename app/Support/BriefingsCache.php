<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
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

    public const CACHE_TTL = '1 day';

    public static function listKey(int $page, int $perPage): string
    {
        return sprintf('briefings.list.v%s.n%d.p%d', self::version(), $perPage, $page);
    }

    /** Key for the landing page's latest briefings and published count. */
    public static function homeKey(): string
    {
        return sprintf('briefings.home.v%s', self::version());
    }

    /** Returns the cached value for $key, or runs $resolve and caches the result for CACHE_TTL. */
    public static function remember(string $key, Closure $resolve): mixed
    {
        return Cache::remember($key, now()->modify('+'.self::CACHE_TTL), $resolve);
    }

    /** The published count, shared by the list page and the landing page. $resolve runs the actual query. */
    public static function publishedCount(Closure $resolve): int
    {
        return (int) self::remember(sprintf('briefings.count.v%s', self::version()), $resolve);
    }

    /**
     * Starts a new version. The version is a random token, not a counter: a counter is read, added to
     * and written back, so two bumps at the same moment could write the same number and leave a page
     * cached in between with a key that is never invalidated. A fresh token never repeats, and works
     * on every cache driver (the file driver's increment() is not atomic either).
     */
    public static function bump(): void
    {
        Cache::forever(self::VERSION_KEY, Str::random(12));
    }

    /** Only bump cache version if entry is a briefing AND is published or was published. Ignores drafts. */
    public static function bumpFor(Entry $entry): void
    {
        if ($entry->collectionHandle() !== Briefings::COLLECTION) {
            return;
        }

        if ($entry->published() || $entry->getOriginal('published')) {
            self::bump();
        }
    }

    private static function version(): string
    {
        return (string) Cache::get(self::VERSION_KEY, '1');
    }
}
