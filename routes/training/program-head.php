<?php

use App\Http\Controllers\Training\ProgramHeadController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('training/program-head')
    ->name('training.program_head.')
    ->group(function () {
        Route::get('/dashboard', [ProgramHeadController::class, 'index'])
            ->name('dashboard');
    });
