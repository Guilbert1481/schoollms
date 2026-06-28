<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Training\ProgramHead\TrainorController;

Route::middleware(['web', 'auth', 'role:training_program_head'])
    ->prefix('training/program-head')
    ->name('training.program_head.')
    ->group(function () {
        Route::get('/trainors', [TrainorController::class, 'index'])->name('trainors.index');
    });
