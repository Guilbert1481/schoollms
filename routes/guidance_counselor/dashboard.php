<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GuidanceCounselor\DashboardController;
use App\Http\Controllers\GuidanceCounselor\FlaggedInterventionsController;

Route::middleware(['web', 'auth', 'role:guidance_counselor'])
    ->prefix('guidance-counselor')
    ->name('guidance_counselor.')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | DASHBOARD
        |--------------------------------------------------------------------------
        */
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        /*
        |--------------------------------------------------------------------------
        | FLAGGED INTERVENTIONS
        |--------------------------------------------------------------------------
        */
        Route::get('/flagged', [FlaggedInterventionsController::class, 'index'])
            ->name('flagged.index');

        Route::get('/flagged/{source}/{id}', [FlaggedInterventionsController::class, 'show'])
            ->where('source', '[a-z_]+')
            ->where('id', '[0-9]+')
            ->name('flagged.show');
    });
