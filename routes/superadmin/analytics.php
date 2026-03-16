<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Superadmin\AnalyticsController;

Route::middleware([
        'web',
        'auth',
        'role:superadmin',
        '2fa'
    ])
    ->prefix('superadmin/analytics')
    ->name('superadmin.analytics.')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | ANALYTICS OVERVIEW
        |--------------------------------------------------------------------------
        */

        Route::get('/overview', [AnalyticsController::class, 'index'])
            ->name('index');


        /*
        |--------------------------------------------------------------------------
        | USAGE REPORTS
        |--------------------------------------------------------------------------
        */

        Route::get('/usage-reports', [AnalyticsController::class, 'usage'])
            ->name('usage');


        /*
        |--------------------------------------------------------------------------
        | REVENUE REPORTS
        |--------------------------------------------------------------------------
        */

        Route::get('/revenue', [AnalyticsController::class, 'revenue'])
            ->name('revenue');

    });
