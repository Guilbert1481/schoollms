<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ProgramController;
use App\Http\Controllers\Admin\AcademicController;

Route::middleware([
        'web',
        'auth',
        'role:academics',
        'subscription'
    ])
    ->prefix('academics')
    ->name('academics.')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | ACADEMICS DASHBOARD
        |--------------------------------------------------------------------------
        */

        Route::get('/dashboard', [AcademicController::class, 'dashboard'])
            ->name('dashboard');


        /*
        |--------------------------------------------------------------------------
        | PROGRAM MANAGEMENT (INSERT BELOW)
        |--------------------------------------------------------------------------
        */

        

    });
