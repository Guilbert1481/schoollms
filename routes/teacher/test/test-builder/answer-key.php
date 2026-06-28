<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Teacher\Test\TestBuilder\PrintKeyController;

Route::prefix('teacher')
    ->name('teacher.')
    ->middleware(['auth', 'role:teacher,course_architect,trainor'])
    ->group(function () {
        // The dedicated answer key print page (separated controller)
        Route::get('tests/{test}/print-answer-key', [PrintKeyController::class, 'printAnswerKey'])
            ->name('tests.print-answer-key')
            ->missing(function () {
                abort(404, 'No valid test found.');
            });
    });