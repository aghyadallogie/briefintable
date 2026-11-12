<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;
use Statamic\Eloquent\Entries\EntryModel;
use Statamic\Support\Str;

/**
 * Creates a reserved list of literal segments of every route under "briefings/",
 * so generated entry slugs can never shadow an existing route.
 * Generates a unique and unreserved slug based on title. If it's already taken, it appends a number.
 */
class BriefingSlugs
{
    /** @return list<string> */
    public static function reserved(): array
    {
        return collect(Route::getRoutes()->getRoutes())
            ->map(fn($route) => $route->uri())
            ->filter(fn(string $uri) => str_starts_with($uri, 'briefings/'))
            ->map(fn(string $uri) => explode('/', $uri)[1])
            ->reject(fn(string $segment) => str_contains($segment, '{'))
            ->unique()
            ->values()
            ->all();
    }

    public static function isReserved(string $slug): bool
    {
        return in_array($slug, self::reserved(), true);
    }

    /** Generates a unique and unreserved slug based on title. If it's already taken, it appends a number. */
    public static function unique(string $title): string
    {
        $base = Str::slug($title) ?: 'briefing';

        $taken = [
            ...self::reserved(),
            ...EntryModel::query()
                ->where('collection', Briefings::COLLECTION)
                ->where('slug', 'like', $base . '%')
                ->pluck('slug')
                ->all(),
        ];

        $slug = $base;
        $suffix = 2;

        while (in_array($slug, $taken, true)) {
            $slug = $base . '-' . $suffix++;
        }

        return $slug;
    }
}
