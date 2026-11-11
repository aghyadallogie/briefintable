<?php

namespace App\Http\Controllers;

use App\Support\DraftState;
use App\Support\OwnDrafts;
use Illuminate\Support\Facades\Auth;

/**
 * Permanently deletes one of the signed-in user's own unpublished briefings.
 *
 * A 404 for anything that isn't theirs, so ids don't reveal what exists.
 * If it is theirs but admin has published it in the meantime, they get a message instead.
 * The check and the delete run under a row lock so a concurrent publish can't be deleted by mistake.
 * Deleting a draft doesn't touch the public list cache; only published entries do.
 */
class DeleteDraftController extends Controller
{
    public function __invoke(string $draft)
    {
        $state = OwnDrafts::whileLocked($draft, function ($entry) {
            $state = OwnDrafts::stateFor(Auth::user(), $entry);

            if ($state === DraftState::Draft) {
                $entry->delete();
            }

            return $state;
        });

        if ($state === DraftState::Published) {
            return redirect()->route('drafts.index')
                ->with('error', 'That briefing has been published, so it can no longer be deleted.');
        }

        abort_unless($state === DraftState::Draft, 404);

        return redirect()->route('drafts.index')->with('success', 'Draft deleted.');
    }
}
