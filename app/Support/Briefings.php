<?php

namespace App\Support;

use Illuminate\Support\Str;
use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Contracts\Entries\QueryBuilder;
use Statamic\Facades\Entry;

/**
 * Centralizes domain logic for the "briefings" collection.
 *
 * The single source of truth for the collection handle, permissions, queries, sort and order.
 * This keeps those rules out of controllers and Livewire components, ensuring consistent
 * behavior across the app. Slug rules live in BriefingSlugs.
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

    /** The public briefings list and the landing page both use this order. */
    public static function newestPublished(): QueryBuilder
    {
        $query = self::published();

        foreach (self::ORDER as [$column, $direction]) {
            $query->orderBy($column, $direction);
        }

        return $query;
    }

    /** One author's unpublished briefings, newest first. */
    public static function draftsOf(string $authorId): QueryBuilder
    {
        return self::all()
            ->where('published', false)
            ->where('author', $authorId)
            ->orderBy('created_at', 'desc');
    }

    /** Plain-text preview (the rendered markdown without its tags), cut to $length characters. */
    public static function excerpt(EntryContract $entry, int $length): string
    {
        return Str::limit(html_entity_decode(strip_tags((string) $entry->augmentedValue('content'))), $length);
    }

    private static function all(): QueryBuilder
    {
        return Entry::query()->where('collection', self::COLLECTION);
    }

    /** The same ORDER, written the way Antlers 'sort' parameter wants it ("a:desc|b:desc"). */
    public static function antlersSort(): string
    {
        return collect(self::ORDER)->map(fn (array $order) => implode(':', $order))->implode('|');
    }
}
