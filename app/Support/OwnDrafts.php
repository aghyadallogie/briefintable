<?php

namespace App\Support;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Eloquent\Entries\EntryModel;
use Statamic\Facades\Entry;

/**
 * The rule behind "edit or delete your own draft": the briefing is yours (you are its author)
 * and it has not been published yet. Deliberately not a Statamic permission: those would also
 * cover published entries, and super users would pass for other people's drafts too.
 *
 * Writes go through whileLocked() so an admin publishing at the same moment can't be overwritten.
 */
class OwnDrafts
{
    /** No such briefing. */
    public const MISSING = 'missing';

    /** Someone else's briefing (or a different collection). Callers should answer with a 404. */
    public const FOREIGN = 'foreign';

    /** Yours, but already live, so it can no longer be changed here. */
    public const PUBLISHED = 'published';

    /** Yours and still a draft: the only state that may be edited or deleted. */
    public const DRAFT = 'draft';

    public static function stateFor(?Authenticatable $user, ?EntryContract $entry): string
    {
        if (! $entry || $entry->collectionHandle() !== Briefings::COLLECTION) {
            return self::MISSING;
        }

        $author = $entry->get('author');

        if (! $user || blank($author) || (string) $author !== (string) $user->getAuthIdentifier()) {
            return self::FOREIGN;
        }

        return $entry->published() ? self::PUBLISHED : self::DRAFT;
    }

    /**
     * Runs $callback with a freshly read entry while holding a row lock.
     *
     * A concurrent publish (from the CP) waits for the lock, so either it lands first and the
     * callback sees "published", or it lands after the callback has finished. The entry is read
     * through the query builder (not the id lookup Statamic memoises), so a copy cached earlier in the request
     * can't be stale. SQLite (used by the tests) has no row locks and just ignores the lock.
     */
    public static function whileLocked(string $id, Closure $callback): mixed
    {
        return DB::transaction(function () use ($id, $callback) {
            EntryModel::query()->whereKey($id)->lockForUpdate()->first();

            return $callback(Entry::query()->where('id', $id)->first());
        });
    }
}
