<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\School\Settings\SystemController;

/*
|--------------------------------------------------------------------------
| System Settings Routes
|--------------------------------------------------------------------------
| System configuration like pagination, timeout, backup, smtp, etc.
|--------------------------------------------------------------------------
*/

Route::prefix('school/settings')
    ->middleware(['web', 'auth', 'role:admin,superadmin'])
    ->name('school.settings.')
    ->group(function () {

        Route::get('/system', [SystemController::class, 'indexSystem'])
            ->name('system.index');

        Route::post('/system/save', [SystemController::class, 'saveSystem'])
            ->name('system.save');

    });