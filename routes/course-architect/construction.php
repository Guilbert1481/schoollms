<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CourseArchitect\LessonStudioController;
use App\Http\Controllers\CourseArchitect\AssessmentLabController;

Route::middleware(['web', 'auth', 'role:course_architect'])
    ->prefix('course-architect')
    ->name('course-architect.')
    ->group(function () {

        Route::prefix('lesson-studio')->name('lesson-studio.')->group(function () {
            // Folder browse (hierarchical, URL-persistent)
            Route::get('/', [LessonStudioController::class, 'index'])->name('index');
            Route::get('/s/{subject}', [LessonStudioController::class, 'index'])->name('subject');
            Route::get('/s/{subject}/t/{topic}', [LessonStudioController::class, 'index'])->name('topic');
            Route::get('/s/{subject}/t/{topic}/l/{lesson}', [LessonStudioController::class, 'index'])->name('lesson');

            // Folder creation (Topic / Lesson / Competency depending on level)
            Route::post('/folder', [LessonStudioController::class, 'createFolder'])->name('folder.store');
            Route::delete('/folder/{type}/{id}', [LessonStudioController::class, 'destroyFolder'])
                ->whereIn('type', ['topic', 'lesson', 'competency'])
                ->name('folder.destroy');
            Route::post('/folder/reorder', [LessonStudioController::class, 'reorder'])->name('folder.reorder');

            // Lesson resource CRUD
            Route::post('/', [LessonStudioController::class, 'store'])->name('store');
            Route::get('/{lesson_resource}/preview', [LessonStudioController::class, 'preview'])->name('preview');
            Route::get('/{lesson_resource}/edit', [LessonStudioController::class, 'edit'])->name('edit');
            Route::put('/{lesson_resource}', [LessonStudioController::class, 'update'])->name('update');
            Route::delete('/{lesson_resource}', [LessonStudioController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('assessment-lab')->name('assessment-lab.')->group(function () {
            Route::get('/', [AssessmentLabController::class, 'index'])->name('index');
        });
    });
