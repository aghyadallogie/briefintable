<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

/**
 * Briefings are served at /briefings/{slug}, but our own routes (/briefings/create, ...) are
 * matched first, so a briefing with the same slug could never be opened.
 *
 * The list comes from the registered routes, so a new /briefings/<word> route is reserved
 * automatically.
 */
class ReservedSlugs
{
    /** @return list<string> */
    public static function briefings(): array
    {
        return collect(Route::getRoutes()->getRoutes())
            ->map(fn ($route) => $route->uri())
            ->filter(fn (string $uri) => str_starts_with($uri, 'briefings/'))
            ->map(fn (string $uri) => explode('/', $uri)[1])
            ->reject(fn (string $segment) => str_contains($segment, '{'))
            ->unique()
            ->values()
            ->all();
    }

    public static function isReserved(string $slug): bool
    {
        return in_array($slug, self::briefings(), true);
    }
}
