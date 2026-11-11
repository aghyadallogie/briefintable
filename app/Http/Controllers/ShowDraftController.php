<?php

namespace App\Http\Controllers;

use App\Support\DraftState;
use App\Support\OwnDrafts;
use Illuminate\Support\Facades\Auth;
use Statamic\Facades\Entry;

/**
 * The read-only details page for one of the signed-in user's own drafts.
 *
 * A 404 for anything that isn't theirs, so ids don't reveal what exists. If it is theirs but has
 * been published since the list was loaded, they are sent to the public briefing page instead.
 */
class ShowDraftController extends Controller
{
    public function __invoke(string $draft)
    {
        $entry = Entry::find($draft);
        $state = OwnDrafts::stateFor(Auth::user(), $entry);

        abort_unless($state->isYours(), 404);

        if ($state === DraftState::Published) {
            return redirect($entry->url() ?? route('briefings.index'));
        }

        return view('drafts.show', ['draft' => $entry]);
    }
}
