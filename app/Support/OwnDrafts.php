<?php

namespace App\Support;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Eloquent\Entries\EntryModel;
use Statamic\Facades\Entry;

/**
 * To edit or delete a draft, the user must be the author of it and its state must be DRAFT.
 * state can be: MISSING, FOREIGN, PUBLISHED, DRAFT.
 */
class OwnDrafts
{
    /** No such briefing. */
    public const MISSING = 'missing';

    /** Someone else's briefing (or a different collection). */
    public const FOREIGN = 'foreign';

    /** Yours, but already live, so it can no longer be changed. */
    public const PUBLISHED = 'published';

    /** Yours and still a draft: the only state that may be edited or deleted. */
    public const DRAFT = 'draft';

    /** Returns the state of a briefing with respect to a user: MISSING, FOREIGN, PUBLISHED, DRAFT. */
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
     * Prevents race conditions between editor in form and admin in CP by a row lock.
     *
     * Acquires the lock *before* the callback executes, so any state check inside the
     * callback sees the most up-to-date data.
     *
     * @return mixed The value returned by the callback.
     */
    public static function whileLocked(string $id, Closure $callback): mixed
    {
        return DB::transaction(function () use ($id, $callback) {
            EntryModel::query()->whereKey($id)->lockForUpdate()->first();

            return $callback(Entry::query()->where('id', $id)->first());
        });
    }
}
