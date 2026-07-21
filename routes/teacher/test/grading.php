<?php

use App\Http\Controllers\Teacher\Test\GradingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| TEACHER — ONLINE TEST GRADING (manual / essay)
|--------------------------------------------------------------------------
| Score the essay answers an online test couldn't auto-grade. Author-only
| ownership is enforced in the controller. Auto items were frozen at submit;
| saving essay scores finalizes the attempt and re-feeds the gradebook.
*/

Route::prefix('teacher')
    ->name('teacher.')
    ->middleware(['auth', 'role:teacher,course_architect,subject_coordinator,trainor'])
    ->group(function () {
        Route::get('tests/{test}/grade', [GradingController::class, 'index'])->name('tests.grade');
        Route::get('tests/{test}/attempts/{attempt}/grade', [GradingController::class, 'show'])->name('tests.grade.show');
        Route::post('tests/{test}/attempts/{attempt}/grade', [GradingController::class, 'store'])->name('tests.grade.store');
    });
