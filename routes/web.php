<?php

use App\Http\Controllers\BriefingController;
use App\Livewire\BriefingForm;
use Illuminate\Support\Facades\Route;

Route::get('/briefings', [BriefingController::class, 'index'])->name('briefings.index');

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/briefings/create', BriefingForm::class)->name('briefings.create');
});
