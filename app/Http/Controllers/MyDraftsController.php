<?php

namespace App\Http\Controllers;

use App\Support\Briefings;
use Illuminate\Http\Request;
use Statamic\Facades\User;

/**
 * The signed-in user's own unpublished briefings, newest first.
 *
 * Deliberately not cached because it is different for every user, 
 * so it must never share the public list's cache key.
 */
class MyDraftsController extends Controller
{
    public function __invoke(Request $request)
    {
        $drafts = Briefings::draftsOf(User::current()->id())->paginate(BriefingController::PER_PAGE);

        abort_if($drafts->currentPage() > $drafts->lastPage(), 404);

        return view('drafts.index', ['drafts' => $drafts]);
    }
}
