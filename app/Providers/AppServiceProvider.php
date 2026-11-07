<?php

namespace App\Providers;

use App\Http\Controllers\Auth\LoginController;
use App\Support\BriefingsCache;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
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

        Authenticate::redirectUsing(function () {
            if (Route::has('statamic.cp.login')) {
                return route('statamic.cp.login');
            }

            return url('/');
        });
    }
}
