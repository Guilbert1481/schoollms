<?php

use App\Http\Controllers\Tools\ScientificCalculatorController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->prefix('/tools')
    ->name('tools.')
    ->group(function () {
        Route::get('/scientific-calculator', [ScientificCalculatorController::class, 'index'])->name('scientific-calculator');
    });
