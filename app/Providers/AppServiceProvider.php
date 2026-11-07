<?php

namespace App\Providers;

use App\Support\BriefingsCache;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Statamic\Events\EntryDeleted;
use Statamic\Events\EntrySaved;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void {}

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
