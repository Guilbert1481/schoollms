<?php

use App\Http\Controllers\Teacher\Test\TestBuilder\OmrScanController;
use App\Http\Controllers\Teacher\Test\TestBuilder\PrintOmrController;
use Illuminate\Support\Facades\Route;

Route::prefix('teacher')
    ->name('teacher.')
    ->middleware(['auth', 'role:teacher,course_architect,subject_coordinator,trainor'])
    ->group(function () {

        // Printable OMR answer sheets (per student in a chosen section).
        Route::get('tests/{test}/answer-sheets', [PrintOmrController::class, 'print'])
            ->name('tests.answer-sheets')
            ->missing(function () {
                abort(404, 'No valid test found.');
            });

        // Manual record page (Phase 2a) — key/confirm answers per student.
        Route::get('tests/{test}/omr/record', [PrintOmrController::class, 'record'])
            ->name('tests.omr.record')
            ->missing(fn () => abort(404, 'No valid test found.'));

        // Camera scan page (Phase 2b) — client-side detection → auto-record.
        Route::get('tests/{test}/omr/scan-camera', [PrintOmrController::class, 'scanCamera'])
            ->name('tests.omr.scan-camera')
            ->missing(fn () => abort(404, 'No valid test found.'));

        // Record a scan / manual submission for one sheet → graded result.
        Route::post('tests/omr/scan', [OmrScanController::class, 'store'])
            ->name('tests.omr.scan');

    });
