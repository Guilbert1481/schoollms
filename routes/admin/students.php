<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\StudentController;

/*
|--------------------------------------------------------------------------
| Student Management & Registration
|--------------------------------------------------------------------------
*/

Route::prefix('admin/students')->name('admin.students.')->group(function () {
    
    // List all students
    Route::get('/', [StudentController::class, 'index'])->name('index');

    // Registration Form
    Route::get('/create', [StudentController::class, 'create'])->name('create');

    // Save New Student (The Registration Action)
    Route::post('/', [StudentController::class, 'store'])->name('store');

    // Profile & Editing
    Route::get('/{student}', [StudentController::class, 'show'])->name('show');
    Route::get('/{student}/edit', [StudentController::class, 'edit'])->name('edit');
    Route::put('/{student}', [StudentController::class, 'update'])->name('update');
});