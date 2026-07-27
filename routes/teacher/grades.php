<?php

use App\Http\Controllers\Teacher\GradebookController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'role:teacher'])
    ->prefix('teacher/gradebook')
    ->name('teacher.gradebook.')
    ->group(function () {
        // Pick a class, enter component scores (draft), then post finals.
        Route::get('/', [GradebookController::class, 'index'])->name('index');
        Route::post('/draft', [GradebookController::class, 'draft'])->name('draft');
        Route::post('/post', [GradebookController::class, 'post'])->name('post');

        // Manual "Add Record" entry + a student's detailed ledger (right drawer).
        Route::post('/record', [GradebookController::class, 'storeRecord'])->name('record');
        Route::get('/ledger', [GradebookController::class, 'ledger'])->name('ledger');
    });
