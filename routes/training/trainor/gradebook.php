<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Training\Trainor\GradebookController;

Route::middleware(['web', 'auth'])
    ->prefix('training/trainor')
    ->name('training.trainor.')
    ->group(function () {
        Route::get('/gradebook', [GradebookController::class, 'index'])
            ->name('gradebook');

        Route::post('/gradebook', [GradebookController::class, 'update'])
            ->name('gradebook.update');
    });
