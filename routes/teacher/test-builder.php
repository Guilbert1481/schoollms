<?php

use App\Http\Controllers\Teacher\Test\TestBuilder\SaveBuilderController;
use App\Http\Controllers\Teacher\Test\TestBuilder\TestBuilderController;
use App\Http\Controllers\Teacher\TestManagementController;
use Illuminate\Support\Facades\Route;

Route::prefix('teacher')
    ->name('teacher.')
    ->middleware(['auth', 'role:teacher,course_architect,subject_coordinator,trainor'])
    ->group(function () {

        // PAGE ROUTES
        Route::get('tests', [TestBuilderController::class, 'index'])
            ->name('tests.index');

        Route::get('tests/create', [TestBuilderController::class, 'create'])
            ->name('tests.create');

        Route::get('tests/{test}/edit', [TestBuilderController::class, 'edit'])
            ->name('tests.edit');

        // TEST MANAGEMENT (list of the teacher's own tests + publish)
        Route::get('tests/management', [TestManagementController::class, 'index'])
            ->name('tests.management');

        Route::post('tests/{test}/publish', [TestManagementController::class, 'publish'])
            ->name('tests.publish');

        // AVAILABILITY ROUTE (THIS IS THE ONE YOUR JS CALLS)
        Route::get('tests/availability', [TestBuilderController::class, 'availability'])
            ->name('tests.availability');

        // ⭐ SAVE TEST ROUTE (this is what your JS calls)
        Route::post('tests/builder/save', [SaveBuilderController::class, 'save'])
            ->name('tests.builder.save');

        Route::post('/test-builder/save-points-to-session', [TestBuilderController::class, 'savePointsToSession'])
            ->name('test_builder.savePointsToSession');

    });
