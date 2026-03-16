<?php

use App\Http\Controllers\Teacher\Test\TestBuilder\TestBuilderController;
use App\Http\Controllers\Teacher\Test\TestBuilder\SaveBuilderController;
use Illuminate\Support\Facades\Route;

Route::prefix('teacher')
    ->name('teacher.')
    ->middleware(['auth'])
    ->group(function () {

        // PAGE ROUTES
        Route::get('tests', [TestBuilderController::class, 'index'])
            ->name('tests.index');

        Route::get('tests/create', [TestBuilderController::class, 'create'])
            ->name('tests.create');

        Route::get('tests/{test}/edit', [TestBuilderController::class, 'edit'])
            ->name('tests.edit');

        // AVAILABILITY ROUTE (THIS IS THE ONE YOUR JS CALLS)
        Route::get('tests/availability', [TestBuilderController::class, 'availability'])
            ->name('tests.availability');

        // ⭐ SAVE TEST ROUTE (this is what your JS calls)
        Route::post('tests/builder/save', [SaveBuilderController::class, 'save'])
            ->name('tests.builder.save');

        Route::post('/test-builder/save-points-to-session', [TestBuilderController::class, 'savePointsToSession'])
            ->name('test_builder.savePointsToSession');

    
    });
