<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Version-stamped cache keys for the public briefings list.
 *
 * The file cache store has no tag support, so instead of flushing by tag we
 * bump a version number whenever a briefing changes. Old keys stop being read
 * and expire on their own TTL.
 */
class BriefingsCache
{
    private const VERSION_KEY = 'briefings.list.version';

    public static function listKey(int $page, int $perPage): string
    {
        return sprintf('briefings.list.v%d.n%d.p%d', self::version(), $perPage, $page);
    }

    public static function bump(): void
    {
        Cache::forever(self::VERSION_KEY, self::version() + 1);
    }

    private static function version(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 1);
    }
}
