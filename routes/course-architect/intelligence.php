<?php

use App\Http\Controllers\CourseArchitect\LearningAnalyticsController;
use App\Http\Controllers\CourseArchitect\ProductionReportsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'role:course_architect,subject_coordinator'])
    ->prefix('course-architect')
    ->name('course-architect.')
    ->group(function () {

        Route::prefix('learning-analytics')->name('learning-analytics.')->group(function () {
            Route::get('/', [LearningAnalyticsController::class, 'index'])->name('index');
        });

        Route::prefix('production-reports')->name('production-reports.')->group(function () {
            Route::get('/', [ProductionReportsController::class, 'index'])->name('index');
        });
    });
