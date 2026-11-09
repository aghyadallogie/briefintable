<?php

namespace App\Providers;

use App\Http\Controllers\Auth\LoginController;
use App\Support\Briefings;
use App\Support\BriefingsCache;
use App\Support\ReservedSlugs;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Statamic\Events\EntryDeleted;
use Statamic\Events\EntrySaved;
use Statamic\Events\EntrySaving;
use Statamic\Facades\Collection;
use Statamic\Http\Controllers\CP\Auth\LoginController as StatamicLoginController;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Statamic's CP routes resolve the controller from the container, so this swaps
        // in our subclass (see its docblock) without touching Statamic's routes.
        $this->app->bind(StatamicLoginController::class, LoginController::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Draft-to-draft saves don't invalidates the cached list.
        Event::listen([EntrySaved::class, EntryDeleted::class], function (EntrySaved|EntryDeleted $event) {
            BriefingsCache::bumpFor($event->entry);
        });

        // Remember when a briefing first goes live, so the public list can sort by publication
        // date instead of creation date. A draft can sit around for a long time before it is
        // published, and readers expect newly published briefings to appear at the top.
        Event::listen(EntrySaving::class, function (EntrySaving $event) {
            $entry = $event->entry;

            if ($entry->collectionHandle() !== Briefings::COLLECTION || ! $entry->published()) {
                return;
            }

            if (! $entry->get('published_at')) {
                $entry->set('published_at', now()->timestamp);
            }
        });

        // Used by the briefing blueprint's slug field, so entries made in the CP can't take a
        // slug that one of our /briefings/<word> routes would shadow.
        Validator::extend(
            'briefing_slug_not_reserved',
            fn ($attribute, $value) => ! ReservedSlugs::isReserved((string) $value),
            'That slug is reserved by the app. Please choose another.',
        );

        // Entries don't expose a creation date to templates (`date` is empty because the collection
        // has no dates, and `updated_at` changes when an admin publishes), so the bylines use this.
        Collection::computed(Briefings::COLLECTION, 'written_at', fn ($entry) => $entry->model()?->created_at);

        Authenticate::redirectUsing(function () {
            if (Route::has('statamic.cp.login')) {
                return route('statamic.cp.login');
            }

            return url('/');
        });
    }
}
