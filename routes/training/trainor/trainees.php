<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Training\Trainor\TraineeController;

Route::middleware(['web', 'auth', 'role:trainor'])
    ->prefix('training/trainor')
    ->name('training.trainor.')
    ->group(function () {
        Route::get('/trainees', [TraineeController::class, 'index'])
            ->name('trainees');

        Route::get('/trainees/{trainee}', [TraineeController::class, 'show'])
            ->whereNumber('trainee')
            ->name('trainees.show');
    });
