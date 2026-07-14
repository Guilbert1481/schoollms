<?php

use App\Http\Controllers\Student\ReportCardController;
use App\Http\Controllers\Student\TranscriptController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'role:student'])
    ->prefix('student')
    ->name('student.')
    ->group(function () {
        Route::get('/transcript', [TranscriptController::class, 'index'])
            ->name('transcript.index');
        // "Grades" sidebar item → the student's Report Card (current period).
        Route::get('/report-card', [ReportCardController::class, 'index'])
            ->name('report-card');
    });
