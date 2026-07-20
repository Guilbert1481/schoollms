<?php

use App\Http\Controllers\CourseArchitect\MediaOptimizerController;
use App\Http\Controllers\CourseArchitect\ResourceVaultController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'role:course_architect,subject_coordinator'])
    ->prefix('course-architect')
    ->name('course-architect.')
    ->group(function () {

        Route::prefix('resource-vault')->name('resource-vault.')->group(function () {
            Route::get('/', [ResourceVaultController::class, 'index'])->name('index');
        });

        Route::prefix('media-optimizer')->name('media-optimizer.')->group(function () {
            Route::get('/', [MediaOptimizerController::class, 'index'])->name('index');
        });
    });
