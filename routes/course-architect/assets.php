<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CourseArchitect\ResourceVaultController;
use App\Http\Controllers\CourseArchitect\MediaOptimizerController;

Route::middleware(['web', 'auth', 'role:course_architect'])
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
