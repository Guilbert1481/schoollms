<?php

use App\Http\Controllers\Dean\ProgramsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('dean')
    ->name('dean.')
    ->group(function () {

        Route::get('/programs', [ProgramsController::class, 'index'])
            ->name('programs.index');

        Route::post('/programs', [ProgramsController::class, 'store'])
            ->name('programs.store');

        Route::put('/programs/{program}', [ProgramsController::class, 'update'])
            ->name('programs.update');

        Route::delete('/programs/{program}', [ProgramsController::class, 'destroy'])
            ->name('programs.destroy');

    });