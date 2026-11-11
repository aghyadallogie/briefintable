<?php

namespace App\Http\Controllers;

use App\Support\Briefings;
use App\Support\BriefingsCache;
use Illuminate\Http\Request;
use Statamic\View\View as StatamicView;

/**
 * Serves the public, paginated briefings list.
 *
 * Bypasses Statamic's default routing to provide a custom paginated list view.
 * Ensures zero database queries on cache hits by utilizing version-stamped
 * Enforces strict page bounds to prevent cache bloat.
 * and passes a version-stamped cache key and TTL to the Antlers template for efficient
 * cache invalidation because file cache driver does not support cache tags.
 */
class BriefingController extends Controller
{
    public const PER_PAGE = 5;

    public function index(Request $request)
    {
        $totalBriefingsCount = BriefingsCache::publishedCount(fn () => Briefings::published()->count());

        $lastPage = max(1, (int) ceil($totalBriefingsCount / self::PER_PAGE));
        $page = max(1, (int) $request->query('page', 1));

        // Shown with the styled errors/404 template (see withExceptions in bootstrap/app.php).
        abort_if($page > $lastPage, 404);

        return StatamicView::make('briefings.index')
            ->layout('layout')
            ->with([
                'page_title' => 'Briefings',
                'collection_handle' => Briefings::COLLECTION,
                'sort' => Briefings::antlersSort(),
                'per_page' => self::PER_PAGE,
                'cache_key' => BriefingsCache::listKey($page, self::PER_PAGE),
                'cache_ttl' => BriefingsCache::CACHE_TTL,
            ])
            ->render();
    }
}
