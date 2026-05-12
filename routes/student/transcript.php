<?php

use App\Http\Controllers\Student\TranscriptController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'role:student'])
    ->prefix('student')
    ->name('student.')
    ->group(function () {
        Route::get('/transcript', [TranscriptController::class, 'index'])
            ->name('transcript.index');
    });
