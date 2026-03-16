<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Teacher\Question\TFController;

Route::middleware(['auth'])->prefix('teacher/tests')->group(function () {

    // 🟢 This route name must match what's in your controller
    Route::get('/tf', [TFController::class, 'index'])->name('tf.builder');
    Route::post('/tf/save', [TFController::class, 'saveTf'])->name('tf.save');
    Route::post('/session/clear', [TFController::class, 'clearQuestionSession']);
});