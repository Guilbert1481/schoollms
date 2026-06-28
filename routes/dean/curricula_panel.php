<?php

use App\Http\Controllers\Dean\CurriculumPanelController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'role:dean,admin,superadmin'])
    ->prefix('dean/curricula-panel')
    ->name('dean.curricula-panel.')
    ->controller(CurriculumPanelController::class)
    ->group(function () {
        // Default landing → curricula tab
        Route::get('/',          'indexCurricula')->name('index');
        Route::get('/curricula', 'indexCurricula')->name('curricula');
        Route::get('/subjects',  'indexSubjects')->name('subjects');
        Route::get('/programs',  'indexPrograms')->name('programs');

        // CRUD endpoints (delegated to BaseCrudController helpers)
        Route::post('/curriculums',         'storeCurriculum')->name('curriculums.store');
        Route::put('/curriculums/{id}',     'updateCurriculum')->name('curriculums.update');
        Route::delete('/curriculums/{id}',  'destroyCurriculum')->name('curriculums.destroy');

        Route::post('/subjects',            'storeSubject')->name('subjects.store');
        Route::put('/subjects/{id}',        'updateSubject')->name('subjects.update');
        Route::delete('/subjects/{id}',     'destroySubject')->name('subjects.destroy');

        // Program ↔ Subject pivot
        Route::post('/program-subjects',           'attachProgramSubject')->name('program-subjects.store');
        Route::put('/program-subjects/{pivot}',    'updateProgramSubject')->name('program-subjects.update');
        Route::delete('/program-subjects/{pivot}', 'detachProgramSubject')->name('program-subjects.destroy');
        Route::post('/program-subjects/import',    'importProgramSubjects')->name('program-subjects.import');
    });
