<?php

use App\Http\Controllers\Teacher\Question\EssayController;

Route::middleware(['auth'])->prefix('teacher/tests')->group(function () {
    Route::get('/essay', [EssayController::class, 'index'])->name('essay.builder');
    Route::post('/essay/save', [EssayController::class, 'saveEssay'])->name('essay.save');
    Route::post('/session/clear', [EssayController::class, 'clearQuestionSession']);
});