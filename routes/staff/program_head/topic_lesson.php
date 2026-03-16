<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Staff\ProgramHead\TopicLessonController;

Route::middleware(['auth'])
    ->prefix('staff/program-head')
    ->name('program_head.')
    ->group(function () {

    // ===============================
    // Lesson Management (Under Topic)
    // ===============================
    Route::get('/subjects/{subject}/lessons', [TopicLessonController::class, 'index'])
        ->name('subjects.lessons.index');

    Route::post('/topics/{topic}/lessons', [TopicLessonController::class, 'store'])
    ->name('topics.lessons.store');
});