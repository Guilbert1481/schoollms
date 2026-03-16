<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Superadmin\SubscriptionController;
use App\Http\Controllers\KPIController;

Route::middleware([
        'web',
        'auth',
        'role:admin',
        'subscription'
    ])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | DASHBOARD
        |--------------------------------------------------------------------------
        */

        


        /*
        |--------------------------------------------------------------------------
        | TEACHERS (PLACEHOLDER)
        |--------------------------------------------------------------------------
        */

        Route::get('/teachers', function () {
            return "Teacher Management Coming Soon";
        })->name('teachers');


        /*
        |--------------------------------------------------------------------------
        | TEST MANAGER (PLACEHOLDER)
        |--------------------------------------------------------------------------
        */

        Route::get('/test-manager', function () {
            return "Test Manager Coming Soon";
        })->name('test-manager');


        /*
        |--------------------------------------------------------------------------
        | SUBSCRIPTION
        |--------------------------------------------------------------------------
        */

        Route::get('/subscription', function () {
            return view('admin.subscription.index');
        })->name('subscription.index');


        Route::post('/subscription/update', [SubscriptionController::class, 'update'])
            ->name('subscription.update');

    });
