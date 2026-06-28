<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\School\Settings\SchoolController;

Route::prefix('school/settings')
    ->middleware(['web', 'auth', 'role:admin,superadmin'])
    ->name('school.settings.')
    ->group(function () {

        Route::get('/school', [SchoolController::class, 'indexSchool'])
            ->name('school.index');

        Route::post('/school/save', [SchoolController::class, 'saveSchool'])
            ->name('school.save');

    });