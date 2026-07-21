<?php

use App\Http\Controllers\Student\ReportCardController;
use App\Http\Controllers\Student\TranscriptController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'role:student'])
    ->prefix('student')
    ->name('student.')
    ->group(function () {
        // "Form 137" sidebar item (basic ed) → gated by the Principal's toggle.
        Route::get('/transcript', [TranscriptController::class, 'index'])
            ->middleware('student.grade-view:form137')
            ->name('transcript.index');
        // "Grades" sidebar item → the student's Report Card (current period),
        // gated by the Principal's toggle.
        Route::get('/report-card', [ReportCardController::class, 'index'])
            ->middleware('student.grade-view:grades')
            ->name('report-card');
    });
