<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Superadmin\SuperSchoolRegistrationController;
use App\Http\Controllers\Superadmin\ModuleController;

Route::middleware([
        'web',
        'auth',
        'role:superadmin',
        '2fa'
    ])
    ->prefix('superadmin/schools')
    ->name('superadmin.schools.')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | SCHOOL MODULE MANAGEMENT (Must Come Before {school})
        |--------------------------------------------------------------------------
        */

        Route::get('/{schoolId}/modules', [ModuleController::class, 'index'])
            ->name('modules.index');

        Route::post('/{schoolId}/modules', [ModuleController::class, 'update'])
            ->name('modules.update');


        /*
        |--------------------------------------------------------------------------
        | SCHOOL MANAGEMENT (CRUD)
        |--------------------------------------------------------------------------
        */

        Route::get('/', [SuperSchoolRegistrationController::class, 'index'])
            ->name('index');

        Route::get('/create', [SuperSchoolRegistrationController::class, 'create'])
            ->name('create');

        Route::post('/', [SuperSchoolRegistrationController::class, 'register'])
            ->name('store');

        Route::get('/{school}', [SuperSchoolRegistrationController::class, 'show'])
            ->name('show');

        Route::get('/{school}/edit', [SuperSchoolRegistrationController::class, 'edit'])
            ->name('edit');

        Route::put('/{school}', [SuperSchoolRegistrationController::class, 'update'])
            ->name('update');

        Route::patch('/{school}/toggle', [SuperSchoolRegistrationController::class, 'toggleStatus'])
            ->name('toggle');

        Route::delete('/{school}', [SuperSchoolRegistrationController::class, 'destroy'])
            ->name('destroy');

    });
