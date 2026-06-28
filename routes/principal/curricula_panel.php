<?php

use App\Http\Controllers\Principal\CurriculumPanelController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'role:principal'])
    ->prefix('principal/curricula-panel')
    ->name('principal.curricula-panel.')
    ->controller(CurriculumPanelController::class)
    ->group(function () {
        // Default landing → subjects masterlist
        Route::get('/',              'indexSubjects')->name('index');
        Route::get('/subjects',      'indexSubjects')->name('subjects');
        Route::get('/grade-levels',  'indexGradeLevels')->name('grade-levels');

        // Subject CRUD
        Route::post('/subjects',          'storeSubject')->name('subjects.store');
        Route::put('/subjects/{id}',      'updateSubject')->name('subjects.update');
        Route::delete('/subjects/{id}',   'destroySubject')->name('subjects.destroy');

        // Grade Level ↔ Subject pivot
        Route::post('/grade-level-subjects',                   'attachGradeLevelSubject')->name('grade-level-subjects.store');
        Route::delete('/grade-level-subjects/{pivot}',         'detachGradeLevelSubject')->name('grade-level-subjects.destroy');
        Route::patch('/grade-level-subjects/{pivot}/toggle',   'toggleGradeLevelSubject')->name('grade-level-subjects.toggle');
        Route::post('/grade-level-subjects/import',            'importGradeLevelSubjects')->name('grade-level-subjects.import');
    });
