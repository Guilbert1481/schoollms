<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Teacher\Lesson\ResourceController;

Route::middleware(['web', 'auth'])
    ->prefix('teacher/lessons/resources')
    ->name('teacher.lessons.resources.')
    ->group(function () {
        Route::get('/', [ResourceController::class, 'index'])->name('index');
    });
