<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Facades\Entry;
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
        $drafts = Entry::query()
            ->where('collection', 'briefings')
            ->where('published', false)
            ->where('author', User::current()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(BriefingController::PER_PAGE);

        // Statamic's exception, unlike a plain abort(), renders the styled errors/404 template
        // through the site layout. A plain abort(404) falls back to Laravel's bare error view.
        throw_if($drafts->currentPage() > $drafts->lastPage(), new NotFoundHttpException);

        return view('drafts.index', ['drafts' => $drafts]);
    }
}
