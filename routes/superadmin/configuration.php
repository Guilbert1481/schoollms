<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Superadmin\AiProviderController;
use App\Http\Controllers\Superadmin\ConfigurationController;

Route::middleware([
        'web',
        'auth',
        'role:superadmin',
        '2fa'
    ])
    ->prefix('superadmin/settings')
    ->name('superadmin.settings.')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | PLATFORM CONFIGURATION
        |--------------------------------------------------------------------------
        */

        Route::get('/', [ConfigurationController::class, 'index'])
            ->name('index');

        Route::post('/update', [ConfigurationController::class, 'update'])
            ->name('update');


        /*
        |--------------------------------------------------------------------------
        | AI PROVIDERS
        |--------------------------------------------------------------------------
        */

        Route::post('/ai', [AiProviderController::class, 'update'])
            ->name('ai.update');


        /*
        |--------------------------------------------------------------------------
        | SYSTEM MAINTENANCE
        |--------------------------------------------------------------------------
        */

        Route::post('/maintenance/toggle', [ConfigurationController::class, 'toggleMaintenance'])
            ->name('maintenance.toggle');


        /*
        |--------------------------------------------------------------------------
        | BACKUPS
        |--------------------------------------------------------------------------
        */

        Route::get('/backups', [ConfigurationController::class, 'backups'])
            ->name('backups');

    });
