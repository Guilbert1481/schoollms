<?php

use App\Http\Controllers\Student\ApplicationsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'role:student'])
    ->prefix('student')
    ->name('student.')
    ->group(function () {
        Route::get('/applications', [ApplicationsController::class, 'index'])
            ->name('applications.index');

        Route::get('/applications/{id}/edit', [ApplicationsController::class, 'edit'])
            ->whereNumber('id')
            ->name('applications.edit');

        Route::get('/applications/{id}/pdf', [ApplicationsController::class, 'pdf'])
            ->whereNumber('id')
            ->name('applications.pdf');

        // Full letterhead preview (same design as the enrolment "Preview Application")
        Route::get('/applications/{id}/view', [ApplicationsController::class, 'view'])
            ->whereNumber('id')
            ->name('applications.view');
    });
