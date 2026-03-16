<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Staff\ProgramHead\SubjectController;
use App\Http\Controllers\Staff\ProgramHead\SubjectTopicController;
use App\Models\Topic;
use App\Models\Lesson;

Route::middleware(['auth'])
    ->prefix('staff/program-head')
    ->name('program_head.')
    ->group(function () {

    // ===============================
    // Subject Management
    // ===============================
    Route::get('/subjects', [SubjectController::class, 'index'])
        ->name('subjects.index');

    Route::post('/subjects', [SubjectController::class, 'store'])
        ->name('subjects.store');

    Route::put('/subjects/{subject}', [SubjectController::class, 'update'])
        ->name('subjects.update');

    Route::delete('/subjects/{subject}', [SubjectController::class, 'destroy'])
        ->name('subjects.destroy');


    // ===============================
    // Topic Management (Under Subject)
    // ===============================
    Route::post('/subjects/{subject}/topics', [SubjectTopicController::class, 'store'])
        ->name('subjects.topics.store');


    // ===============================
    // Data Fetching for Modals
    // ===============================
    Route::get('/subjects/{subject}/get-topics', function ($subjectId) {
        return Topic::where('subject_id', $subjectId)->get(['id', 'name']);
    })->name('subjects.get-topics');

    Route::get('/subjects/{subject}/get-lessons', function ($subjectId) {
        return Lesson::whereHas('topic', function ($q) use ($subjectId) {
            $q->where('subject_id', $subjectId);
        })->get(['id', 'name']);
    })->name('subjects.get-lessons');
});