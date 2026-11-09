<?php

namespace App\Http\Controllers;

use App\Support\Briefings;
use Illuminate\Http\Request;
use Statamic\Facades\User;

/**
 * The signed-in user's own unpublished briefings, newest first.
 *
 * Deliberately not cached: the page is different for every user, so it must never share
 * the public list's cache key.
 */
class MyDraftsController extends Controller
{
    public function __invoke(Request $request)
    {
        $drafts = Briefings::draftsOf(User::current()->id())->paginate(BriefingController::PER_PAGE);

        // Shown with the styled errors/404 template (see withExceptions in bootstrap/app.php).
        abort_if($drafts->currentPage() > $drafts->lastPage(), 404);

        return view('drafts.index', ['drafts' => $drafts]);
    }
}
