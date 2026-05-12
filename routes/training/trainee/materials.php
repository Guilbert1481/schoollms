<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Training\Trainee\CourseController;

Route::middleware(['web', 'auth'])
    ->prefix('training/trainee')
    ->name('training.trainee.')
    ->group(function () {
        Route::get('/materials', [CourseController::class, 'materials'])
            ->name('materials');
    });