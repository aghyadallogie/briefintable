<?php

use App\Http\Controllers\BriefingController;
use App\Livewire\BriefingForm;
use Illuminate\Support\Facades\Route;

Route::get('/briefings', [BriefingController::class, 'index'])->name('briefings.index');

// The navbar's "Login" link. Linking straight to /cp would make the CP the "intended" page,
// so a fresh login would land there instead of on /briefings. Also drops a stale intended
// page left over from an earlier visit. Guests bounced from a protected page don't pass
// through here, so they still return to the page they asked for.
Route::get('/login', function () {
    session()->forget('url.intended');

    return redirect()->to(cp_route('login'));
})->name('login');

Route::middleware(['web', 'auth'])->group(function () {
    // Statamic permission, so the CP and this form share one set of rules. The Livewire
    // `save` action is a separate request that skips this middleware and checks it again.
    Route::get('/briefings/create', BriefingForm::class)
        ->middleware('can:create briefings entries')
        ->name('briefings.create');
});
