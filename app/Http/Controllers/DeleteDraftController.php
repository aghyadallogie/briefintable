<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Gate;
use Statamic\Facades\Entry;

/**
 * Permanently deletes one of the signed-in user's own unpublished briefings.
 *
 * A 404 (not 403) for anything else, so ids don't reveal what exists. Deleting a draft
 * doesn't touch the public list cache; only published entries do.
 */
class DeleteDraftController extends Controller
{
    public function __invoke(string $draft)
    {
        $entry = Entry::find($draft);

        abort_unless($entry && Gate::allows('manage-own-draft', $entry), 404);

        $entry->delete();

        return redirect()->route('drafts.index')->with('success', 'Draft deleted.');
    }
}
