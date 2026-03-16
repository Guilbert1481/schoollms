<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Teacher\Question\FIBController;

Route::middleware(['auth'])->prefix('teacher/tests')->group(function () {
    Route::get('/fib', [FIBController::class, 'builder'])->name('fib.builder');
    Route::post('/fib/save', [FIBController::class, 'save'])->name('fib.save');
});