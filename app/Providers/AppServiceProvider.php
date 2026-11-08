<?php

namespace App\Providers;

use App\Http\Controllers\Auth\LoginController;
use App\Support\BriefingsCache;
use App\Support\ReservedSlugs;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Events\EntryDeleted;
use Statamic\Events\EntrySaved;
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

        // Used by the briefing blueprint's slug field, so entries made in the CP can't take a
        // slug that one of our /briefings/<word> routes would shadow.
        Validator::extend(
            'briefing_slug_not_reserved',
            fn ($attribute, $value) => ! ReservedSlugs::isReserved((string) $value),
            'That slug is reserved by the app. Please choose another.',
        );

        // A draft can be edited or deleted by the person who wrote it, and only while it is still
        // unpublished. Deliberately not a Statamic permission: those would also cover published
        // entries, and super users would pass for other people's drafts too.
        Gate::define('manage-own-draft', fn ($user, ?EntryContract $entry) => $entry !== null
            && $entry->collectionHandle() === 'briefings'
            && ! $entry->published()
            && filled($entry->get('author'))
            && (string) $entry->get('author') === (string) $user->getAuthIdentifier());

        Authenticate::redirectUsing(function () {
            if (Route::has('statamic.cp.login')) {
                return route('statamic.cp.login');
            }

            return url('/');
        });
    }
}
