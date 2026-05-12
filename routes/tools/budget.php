<?php

use App\Http\Controllers\Tools\BudgetController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->prefix('/tools')
    ->name('tools.')
    ->group(function () {
        Route::get('/budget', [BudgetController::class, 'index'])->name('budget');
    });
