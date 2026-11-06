<?php

use App\Livewire\BriefingForm;
use Illuminate\Support\Facades\Route;
use Statamic\View\View as StatamicView;

Route::get('/briefings', function () {
    return StatamicView::make('briefings.index')
        ->layout('layout')
        ->render();
})->name('briefings.index');

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/briefings/create', BriefingForm::class)->name('briefings.create');
});
