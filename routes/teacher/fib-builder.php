<?php

use App\Http\Controllers\Teacher\Question\FIBController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:teacher,course_architect,subject_coordinator,trainor'])->prefix('teacher/tests')->group(function () {
    Route::get('/fib', [FIBController::class, 'builder'])->name('fib.builder');
    Route::post('/fib/save', [FIBController::class, 'save'])->name('fib.save');
});
