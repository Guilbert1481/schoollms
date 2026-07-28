<?php

use App\Http\Controllers\Base\BaseCrudController;
use Illuminate\Support\Facades\Route;

/*
 * Generic CRUD for the registered master-data tables.
 *
 * The role list below is the UNION of the `roles` allowlists declared by the
 * tables in config/tables/tables.php — a coarse first gate. The real decision is
 * per table, inside BaseCrudController::tableConfigForRequest(), because one
 * flat list here cannot express "a dean may edit curriculums but not offices".
 *
 * Keep this list as the union: widen it only when a table's own `roles` gains a
 * role, and narrow it when the last table using one drops it. It previously
 * carried 15 roles, which let a teacher or course_architect update `subjects`,
 * `topics` and `lessons` directly and walk around the ownership rules the
 * Lesson Studio and the curricula panels enforce on their own screens.
 */
Route::prefix('school/system/dynamic')
    ->middleware(['web', 'auth', 'role:admin,superadmin,dean,principal'])
    ->group(function () {

        // STORE
        Route::post('/store/{table}', [BaseCrudController::class, 'storeRecord'])
            ->name('school.system.dynamic.store');

        // GET RECORD
        Route::get('/get/{table}/{id}', [BaseCrudController::class, 'getRecord'])
            ->name('school.system.dynamic.get');

        // UPDATE (POST or PUT; supports _method spoofing and raw PUT)
        Route::match(['put', 'post'], '/update', [BaseCrudController::class, 'updateRecord'])
            ->name('school.system.dynamic.update');

        // DELETE
        Route::delete('/destroy/{table}/{id}', [BaseCrudController::class, 'deleteRecord'])
            ->name('school.system.dynamic.destroy');

    });
