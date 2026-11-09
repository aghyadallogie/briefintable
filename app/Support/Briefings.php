<?php

namespace App\Support;

use Statamic\Contracts\Entries\QueryBuilder;
use Statamic\Facades\Entry;

/**
 * The few things that were being decided in more than one place: which collection holds briefings, what
 * "published" means, and in what order they are shown. Change them here and every page follows.
 *
 * Everything else (finding one entry, slugs, ...) is a plain Statamic call at the place that needs it.
 */
class Briefings
{
    /** Handle of the Statamic collection that holds every briefing. */
    public const COLLECTION = 'briefings';

    /**
     * Newest published first: by when it went live, then by when it was written (which also orders any
     * entry that has no published_at yet). The public list and the landing page both use this order.
     */
    public const ORDER = [['published_at', 'desc'], ['created_at', 'desc']];

    /** Live briefings. */
    public static function published(): QueryBuilder
    {
        return self::all()->whereStatus('published');
    }

    /** Live briefings, newest published first. */
    public static function newestPublished(): QueryBuilder
    {
        $query = self::published();

        foreach (self::ORDER as [$column, $direction]) {
            $query->orderBy($column, $direction);
        }

        return $query;
    }

    /** The same order as ORDER, written the way Antlers' `sort` parameter wants it ("a:desc|b:desc"). */
    public static function antlersSort(): string
    {
        return collect(self::ORDER)->map(fn (array $order) => implode(':', $order))->implode('|');
    }

    /** One author's unpublished briefings, newest first. */
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
}
