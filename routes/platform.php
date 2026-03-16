<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Platform\TenantController;
use App\Http\Controllers\Platform\SubscriptionController;

Route::middleware(['auth:super_admin'])
    ->prefix('platform')
    ->group(function () {

        Route::get('/dashboard', function () {
            return view('platform.dashboard');
        })->name('platform.dashboard');

        Route::get('/tenants', [TenantController::class, 'index'])
            ->name('platform.tenants');

        Route::get('/subscriptions', [SubscriptionController::class, 'index'])
            ->name('platform.subscriptions');

    });
