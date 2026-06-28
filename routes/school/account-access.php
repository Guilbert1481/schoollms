<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\School\AccountAccessController;

Route::middleware(['auth', 'role:admin,superadmin'])->prefix('school')->name('school.')->group(function () {

    Route::get('/account-access', [AccountAccessController::class, 'index'])
        ->name('account-access.index');

    Route::post('/account-access/transfer', [AccountAccessController::class, 'transfer'])
        ->name('account-access.transfer');

    Route::post('/account-access/deactivate', [AccountAccessController::class, 'deactivate'])
    ->name('account-access.deactivate');

    Route::post('/account-access/assign', [AccountAccessController::class, 'assign'])
    ->name('account-access.assign');

});