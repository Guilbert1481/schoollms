<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Training Master Data Routes
|--------------------------------------------------------------------------
*/

Route::prefix('school/settings/master-data/training')
    ->name('school.settings.master-data.training.')
    ->middleware(['web', 'auth', 'role:admin,superadmin,training_program_head'])
    ->group(function () {

        Route::get('/programs', function () {
            return view('school.settings.master-data.training.programs');
        })->name('programs');

        Route::get('/sessions', function () {
            return view('school.settings.master-data.training.sessions');
        })->name('sessions');

        Route::get('/certificates', function () {
            return redirect()->route('school.settings.master-data.certificates.index');
        })
            ->name('certificates');

    });
