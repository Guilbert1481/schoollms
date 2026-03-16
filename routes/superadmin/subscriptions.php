<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Superadmin\SubscriptionController;

Route::middleware([
        'web',
        'auth',
        'role:superadmin',
        '2fa'
    ])
    ->prefix('superadmin/subscriptions')
    ->name('superadmin.subscriptions.')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | SUBSCRIPTION OVERVIEW
        |--------------------------------------------------------------------------
        */

        Route::get('/', [SubscriptionController::class, 'index'])
            ->name('index');


        /*
        |--------------------------------------------------------------------------
        | PLANS
        |--------------------------------------------------------------------------
        */

        Route::get('/plans', [SubscriptionController::class, 'plans'])
            ->name('plans');


        /*
        |--------------------------------------------------------------------------
        | INVOICE
        |--------------------------------------------------------------------------
        */

        Route::get('/{id}/invoice', [SubscriptionController::class, 'invoice'])
            ->name('invoice');


        /*
        |--------------------------------------------------------------------------
        | CANCEL SUBSCRIPTION
        |--------------------------------------------------------------------------
        */

        Route::post('/{id}/cancel', [SubscriptionController::class, 'cancel'])
            ->name('cancel');

    });
