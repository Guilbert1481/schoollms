<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Teacher\Lesson\CompetencyController;
use App\Http\Controllers\Teacher\Lesson\LessonController;

Route::middleware(['web', 'auth', 'role:teacher'])
    ->prefix('teacher/lessons')
    ->name('teacher.lessons.')
    ->group(function () {
        Route::get('/', [LessonController::class, 'index'])->name('index');
        Route::post('/', [LessonController::class, 'store'])->name('store');

        // Teacher-authored competencies + hide/delete of Course-Architect ones.
        Route::prefix('competencies')->name('competencies.')->group(function () {
            Route::post('/', [CompetencyController::class, 'store'])->name('store');
            Route::get('/{competency}', [CompetencyController::class, 'show'])->name('show');
            Route::delete('/{competency}', [CompetencyController::class, 'destroy'])->name('destroy');
            Route::post('/{competency}/hide', [CompetencyController::class, 'hide'])->name('hide');
            Route::post('/{competency}/unhide', [CompetencyController::class, 'unhide'])->name('unhide');
        });
    });
