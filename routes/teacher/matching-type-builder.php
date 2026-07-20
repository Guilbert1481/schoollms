<?php

use App\Http\Controllers\Teacher\Question\MatchingController;

Route::middleware(['auth', 'role:teacher,course_architect,subject_coordinator,trainor'])->prefix('teacher/tests')->group(function () {
    Route::get('/matching', [MatchingController::class, 'index'])->name('matching.builder');
    Route::post('/matching/save', [MatchingController::class, 'saveMatching'])->name('matching.save');
    Route::post('/session/clear', [MatchingController::class, 'clearQuestionSession']);
});
