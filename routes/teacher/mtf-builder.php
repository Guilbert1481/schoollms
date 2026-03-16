<?php

use App\Http\Controllers\Teacher\Question\MTFController;

Route::middleware(['auth'])->prefix('teacher/tests')->group(function () {
    Route::get('/mtf', [MTFController::class, 'index'])->name('mtf.builder');
    Route::post('/mtf/save', [MTFController::class, 'saveMtf'])->name('mtf.save');
    Route::post('/session/clear', [MTFController::class, 'clearQuestionSession']);
});