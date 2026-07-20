<?php

use App\Http\Controllers\Teacher\Question\IdentificationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:teacher,course_architect,subject_coordinator,trainor'])->prefix('teacher/tests')->group(function () {
    Route::get('/identification', [IdentificationController::class, 'index'])->name('identification.builder');
    Route::post('/identification/save', [IdentificationController::class, 'saveIdentification'])->name('identification.save');
    Route::post('/session/clear', [IdentificationController::class, 'clearQuestionSession']);
});
