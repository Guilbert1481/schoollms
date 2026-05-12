<?php

use App\Http\Controllers\Student\SubjectController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'role:student'])
    ->prefix('student')
    ->name('student.')
    ->group(function () {
        Route::get('/subjects', [SubjectController::class, 'index'])
            ->name('subjects.index');
    });
