<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Superadmin\PricingController;

Route::middleware([
        'web',
        'auth',
        'role:superadmin',
        '2fa'
    ])
    ->prefix('superadmin/pricing')
    ->name('superadmin.pricing.')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | PRICING CONFIGURATION
        |--------------------------------------------------------------------------
        */

        Route::get('/', [PricingController::class, 'index'])
            ->name('index');

        Route::post('/', [PricingController::class, 'update'])
            ->name('update');

    });
