<?php

use App\Http\Controllers\BriefingController;
use App\Http\Controllers\DeleteDraftController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MyDraftsController;
use App\Http\Controllers\ShowDraftController;
use App\Livewire\BriefingForm;
use App\Support\Briefings;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', HomeController::class)->name('home');

Route::get('/briefings', [BriefingController::class, 'index'])->name('briefings.index');

Route::get('/login', function () {
    session()->forget('url.intended');

    return redirect()->to(cp_route('login'));
})->name('login');

// Authenticated routes
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/briefings/create', BriefingForm::class)
        ->middleware('can:'.Briefings::CAN_CREATE)
        ->name('briefings.create');

    Route::get('/my-drafts', MyDraftsController::class)->name('drafts.index');

    // Gate-protected routes for managing drafts
    Route::middleware('can:'.Briefings::CAN_CREATE)->group(function () {
        Route::get('/my-drafts/{draft}', ShowDraftController::class)->name('drafts.show');
        Route::get('/my-drafts/{draft}/edit', BriefingForm::class)->name('drafts.edit');
        Route::delete('/my-drafts/{draft}', DeleteDraftController::class)->name('drafts.destroy');
    });
});
