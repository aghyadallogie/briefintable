<?php

namespace App\Http\Controllers\Auth;

use Statamic\Http\Controllers\CP\Auth\LoginController as StatamicLoginController;

/**
 * Statamic's control panel login, except that a fresh login lands on the public
 * briefings list instead of the CP dashboard.
 *
 * Statamic still sends people back to the page they originally asked for (the
 * "intended" URL), so a guest who opens a CP screen or /briefings/create is
 * returned there after logging in. This only changes the fallback.
 *
 * Swapped in through the container in AppServiceProvider.
 */
class LoginController extends StatamicLoginController
{
    public function redirectPath()
    {
        return route('briefings.index');
    }
}
