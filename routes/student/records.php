<?php

use App\Http\Controllers\Student\RecordsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Student Records (Academics → Records)
|--------------------------------------------------------------------------
| Grade details for every graded activity, with the grading-scheme running
| average and the Subjects-at-Risk breakdown.
*/

Route::middleware(['web', 'auth', 'role:student'])
    ->prefix('student')
    ->name('student.')
    ->group(function () {
        Route::get('/records', [RecordsController::class, 'index'])->name('records.index');
    });
