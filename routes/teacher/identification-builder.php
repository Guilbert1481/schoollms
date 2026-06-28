<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Teacher\Question\IdentificationController;

Route::middleware(['auth', 'role:teacher,course_architect,trainor'])->prefix('teacher/tests')->group(function () {
    Route::get('/identification', [IdentificationController::class, 'index'])->name('identification.builder');
    Route::post('/identification/save', [IdentificationController::class, 'saveIdentification'])->name('identification.save');
    Route::post('/session/clear', [IdentificationController::class, 'clearQuestionSession']);
});


