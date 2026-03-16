<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Superadmin\DashboardController;
use App\Http\Controllers\Superadmin\ExportController;
use App\Http\Controllers\Superadmin\FileExportController;
use App\Http\Controllers\Superadmin\ProfileController;

Route::middleware([
        'web',
        'auth',
        'role:superadmin',
        '2fa'
    ])
    ->prefix('superadmin')
    ->name('superadmin.')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | DASHBOARD
        |--------------------------------------------------------------------------
        */

        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

        Route::get('/system-status', [DashboardController::class, 'status'])
            ->name('status');

        Route::post('/maintenance', [DashboardController::class, 'toggleMaintenance'])
            ->name('maintenance');


        /*
        |--------------------------------------------------------------------------
        | EXPORTS
        |--------------------------------------------------------------------------
        */

        Route::get('/export/school/{school}', [ExportController::class, 'export'])
            ->name('export.school');

        Route::get('/export/school-files/{school}', [FileExportController::class, 'exportFiles'])
            ->name('export.school.files');


        /*
        |--------------------------------------------------------------------------
        | PROFILE
        |--------------------------------------------------------------------------
        */

        Route::controller(ProfileController::class)->group(function () {

            Route::get('/profile', 'edit')
                ->name('profile.edit');

            Route::patch('/profile', 'update')
                ->name('profile.update');

            Route::post('/profile/2fa/enable', 'enable2FA')
                ->name('profile.2fa.enable');

            Route::post('/profile/2fa/confirm', 'confirm2FA')
                ->name('profile.2fa.confirm');

            Route::delete('/profile/2fa/disable', 'disable2FA')
                ->name('profile.2fa.disable');

        });





        

    });
