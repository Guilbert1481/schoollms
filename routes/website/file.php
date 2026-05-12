<?php

use App\Http\Controllers\Website\FileController;
use Illuminate\Support\Facades\Route;

Route::prefix('{schoolSlug}')
    ->where(['schoolSlug' => '[A-Za-z0-9\-]+'])
    ->name('website.')
    ->group(function () {
        Route::get('/', [FileController::class, 'home'])->name('home');
        Route::get('/about', [FileController::class, 'about'])->name('about');
        Route::get('/programs', [FileController::class, 'programs'])->name('programs');
        Route::get('/courses', [FileController::class, 'courses'])->name('courses');
        Route::post('/courses/enrol/login', [FileController::class, 'traineeLogin'])->name('enrol.login');
        Route::post('/courses/enrol/signup', [FileController::class, 'traineeSignup'])->name('enrol.signup');
        Route::get('/admissions', [FileController::class, 'admissions'])->name('admissions');
        Route::get('/blog', [FileController::class, 'blog'])->name('blog');
    });
