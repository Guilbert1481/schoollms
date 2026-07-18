<?php

use App\Http\Controllers\Superadmin\LogController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Superadmin — Logs (Roadmap Phase 4)
|--------------------------------------------------------------------------
| Read-only viewers over append-only logs. The old /activity, /errors and
| /clear entries were dead references (no controller existed) and were
| dropped; a clear action will never return — logs are append-only.
*/
Route::middleware(['web', 'auth', 'role:superadmin', '2fa'])
    ->prefix('superadmin/logs')
    ->name('superadmin.logs.')
    ->group(function () {
        Route::get('/logins', [LogController::class, 'logins'])->name('logins');
    });
