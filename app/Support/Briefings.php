<?php

namespace App\Support;

use Statamic\Contracts\Entries\QueryBuilder;
use Statamic\Eloquent\Entries\EntryModel;
use Statamic\Facades\Entry;
use Statamic\Support\Str;

/**
 * Centralizes domain logic for the "briefings" collection.
 *
 * The single source of truth for collection queries, sort, order,
 * and write-path utilities (like unique slug generation).
 * This keeps queries and slug rules out of controllers and Livewire
 * components, ensuring consistent behavior across the app.
 */
class Briefings
{
    public const COLLECTION = 'briefings';

    // Statamic's permission names are still hard-coded in roles.yaml
    public const CAN_CREATE = 'create briefings entries';

    public const CAN_PUBLISH = 'publish briefings entries';

    public const ORDER = [['published_at', 'desc'], ['created_at', 'desc']];

    public static function published(): QueryBuilder
    {
        return self::all()->whereStatus('published');
    }

    // The public briefings list and the landing page both use this order.
    public static function newestPublished(): QueryBuilder
    {
        $query = self::published();

        foreach (self::ORDER as [$column, $direction]) {
            $query->orderBy($column, $direction);
        }

        return $query;
    }

    // One author's unpublished briefings, newest first.
    public static function draftsOf(string $authorId): QueryBuilder
    {
        return self::all()
            ->where('published', false)
            ->where('author', $authorId)
            ->orderBy('created_at', 'desc');
    }

    private static function all(): QueryBuilder
    {
        return Entry::query()->where('collection', self::COLLECTION);
    }

    // Generates a unique and unreserved slug based on title. If it's already taken, it appends a number.
    public static function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'briefing';

        $taken = [
            ...ReservedSlugs::briefings(),
            ...EntryModel::query()
                ->where('collection', self::COLLECTION)
                ->where('slug', 'like', $base.'%')
                ->pluck('slug')
                ->all(),
        ];

        $slug = $base;
        $suffix = 2;

        while (in_array($slug, $taken, true)) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    // The same ORDER, written the way Antlers 'sort' parameter wants it ("a:desc|b:desc").
    public static function antlersSort(): string
    {
        return collect(self::ORDER)->map(fn (array $order) => implode(':', $order))->implode('|');
    }
}
