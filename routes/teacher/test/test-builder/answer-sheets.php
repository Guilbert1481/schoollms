<?php

use App\Http\Controllers\Teacher\Test\TestBuilder\OmrScanController;
use App\Http\Controllers\Teacher\Test\TestBuilder\PrintOmrController;
use Illuminate\Support\Facades\Route;

Route::prefix('teacher')
    ->name('teacher.')
    ->middleware(['auth', 'role:teacher,course_architect,trainor'])
    ->group(function () {

        // Printable OMR answer sheets (per student in a chosen section).
        Route::get('tests/{test}/answer-sheets', [PrintOmrController::class, 'print'])
            ->name('tests.answer-sheets')
            ->missing(function () {
                abort(404, 'No valid test found.');
            });

        // Record a scan / manual submission for one sheet → graded result.
        Route::post('tests/omr/scan', [OmrScanController::class, 'store'])
            ->name('tests.omr.scan');

    });
