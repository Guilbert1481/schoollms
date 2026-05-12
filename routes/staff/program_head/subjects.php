<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Staff\ProgramHead\SubjectController;
use App\Http\Controllers\Staff\ProgramHead\SubjectTopicController;
use App\Http\Controllers\Staff\ProgramHead\LessonStudioController;
use App\Models\Topic;
use App\Models\Lesson;

Route::middleware(['auth'])
    ->prefix('staff/program-head')
    ->name('program_head.')
    ->group(function () {

    // ===============================
    // Lesson Studio (folder browser) — replaces the old subjects.index page
    // ===============================
    Route::get('/subjects', [LessonStudioController::class, 'index'])
        ->name('subjects.index');

    Route::get('/lesson-studio/s/{subject}', [LessonStudioController::class, 'index'])
        ->name('lesson-studio.subject');

    Route::get('/lesson-studio/s/{subject}/t/{topic}', [LessonStudioController::class, 'index'])
        ->name('lesson-studio.topic');

    Route::get('/lesson-studio/s/{subject}/t/{topic}/l/{lesson}', [LessonStudioController::class, 'index'])
        ->name('lesson-studio.lesson');

    Route::post('/lesson-studio/folder', [LessonStudioController::class, 'createFolder'])
        ->name('lesson-studio.folder.store');

    Route::delete('/lesson-studio/folder/{type}/{id}', [LessonStudioController::class, 'destroyFolder'])
        ->name('lesson-studio.folder.destroy');

    Route::put('/lesson-studio/folder/{type}/{id}', [LessonStudioController::class, 'updateFolder'])
        ->name('lesson-studio.folder.update');

    Route::post('/lesson-studio/folder/reorder', [LessonStudioController::class, 'reorder'])
        ->name('lesson-studio.folder.reorder');

    // ===============================
    // Legacy CRUD endpoints kept for compatibility
    // ===============================
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