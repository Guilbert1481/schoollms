<?php

use Illuminate\Support\Facades\Route;

Route::get('/dashboard', function () {
    return view('student.dashboard');
})->name('dashboard');
