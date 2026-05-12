<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CourseArchitect\DashboardController;
use App\Http\Controllers\CourseArchitect\PathVisualizerController;
use App\Http\Controllers\CourseArchitect\PreviewController;

Route::middleware(['web', 'auth', 'role:course_architect'])
    ->prefix('course-architect')
    ->name('course-architect.')
    ->group(function () {

        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Path Visualizer
        Route::prefix('path-visualizer')->name('path-visualizer.')->group(function () {
            Route::get('/', [PathVisualizerController::class, 'index'])->name('index');
        });

        // Preview Mode
        Route::prefix('preview')->name('preview.')->group(function () {
            Route::get('/', [PreviewController::class, 'index'])->name('index');
        });
    });
