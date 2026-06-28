<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\School\Settings\SignatoryController;

Route::prefix('school/settings')
    ->name('school.settings.')
    ->middleware(['auth', 'role:admin,superadmin'])
    ->group(function () {

        Route::get('/signatories', [SignatoryController::class, 'indexSignatory'])
            ->name('signatory.index');

        Route::post('/signatory/store', [SignatoryController::class, 'storeSignatory'])
            ->name('storeSignatory');

        Route::post('/document/store', [SignatoryController::class, 'storeDocument'])
            ->name('storeDocument');
});