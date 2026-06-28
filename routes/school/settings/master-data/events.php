<?php

use App\Http\Controllers\Events\MasterDataEventFormController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'role:admin,superadmin'])
    ->prefix('school/settings/master-data/events')
    ->name('school.settings.master-data.events.')
    ->group(function () {
        Route::get('/', [MasterDataEventFormController::class, 'index'])->name('index');
        Route::post('/', [MasterDataEventFormController::class, 'store'])->name('store');
    });

Route::middleware(['web', 'auth', 'signed'])
    ->prefix('event-form')
    ->name('school.settings.master-data.events.public.')
    ->group(function () {
        Route::get('/{event}', [MasterDataEventFormController::class, 'showPublic'])->name('show');
        Route::post('/{event}', [MasterDataEventFormController::class, 'submitPublic'])->name('submit');
    });
