<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Training\Trainor\MaterialController;

Route::middleware(['web', 'auth'])
    ->prefix('training/trainor')
    ->name('training.trainor.')
    ->group(function () {
        Route::get('/materials', [MaterialController::class, 'index'])
            ->name('materials');

        Route::post('/materials', [MaterialController::class, 'store'])
            ->name('materials.store');

        Route::delete('/materials/{material}', [MaterialController::class, 'destroy'])
            ->whereNumber('material')
            ->name('materials.destroy');
    });
