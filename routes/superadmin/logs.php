<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Superadmin\LogController;

Route::prefix('superadmin/logs')->name('superadmin.logs.')->group(function () {
    Route::get('/activity', [LogController::class, 'activity'])->name('activity');
    Route::get('/errors', [LogController::class, 'errors'])->name('errors');
    Route::get('/logins', [LogController::class, 'logins'])->name('logins');
    Route::delete('/clear', [LogController::class, 'clear'])->name('clear');
    
});