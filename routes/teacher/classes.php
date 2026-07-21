<?php

use App\Http\Controllers\Teacher\ClassListController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'role:teacher'])
    ->prefix('teacher/classes')
    ->name('teacher.classes.')
    ->group(function () {
        Route::get('/', [ClassListController::class, 'index'])->name('index');
    });
