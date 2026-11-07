<?php

namespace App\Http\Controllers;

use App\Support\BriefingsCache;
use Illuminate\Http\Request;
use Statamic\Facades\Entry;
use Statamic\View\View as StatamicView;

/**
 * Handles the public-facing list of briefings.
 * 
 * This controller bypasses Statamic's default routing to provide a custom paginated list view. 
 * It enforces strict page bounds to prevent cache bloat 
 * and passes a version-stamped cache key to the Antlers template for efficient 
 * invalidation without relying on cache tags.
 */
class BriefingController extends Controller
{
    public const PER_PAGE = 5;

    public function index(Request $request)
    {
        $total_briefings_count = Entry::query()
            ->where('collection', 'briefings')
            ->whereStatus('published')
            ->count();

        $lastPage = max(1, (int) ceil($total_briefings_count / self::PER_PAGE));
        $page = max(1, (int) $request->query('page', 1));

        abort_if($page > $lastPage, 404);

        return StatamicView::make('briefings.index')
            ->layout('layout')
            ->with([
                'per_page' => self::PER_PAGE,
                'cache_key' => BriefingsCache::listKey($page, self::PER_PAGE),
                'page_title' => 'Table',
            ])
            ->render();
    }
}
