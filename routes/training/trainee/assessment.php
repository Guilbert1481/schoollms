<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Training\Trainee\EnrollmentController;

Route::middleware(['web', 'auth'])
    ->prefix('training/trainee')
    ->name('training.trainee.')
    ->group(function () {
        Route::get('/assessment', [EnrollmentController::class, 'assessment'])
            ->name('assessment');
    });