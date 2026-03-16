<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Teacher\Test\TestBuilder\PrintTestController;

Route::prefix('teacher')
    ->name('teacher.')
    ->middleware(['auth'])
    ->group(function () {

        Route::get('tests/{test}/print', [PrintTestController::class, 'print'])
        ->name('tests.print')
        ->missing(function () {
            abort(404, 'No valid test found.');
        });


        Route::get('tests/{test}/answer-key', [PrintTestController::class, 'answerKey'])
        ->name('tests.answer-key')
        ->missing(function () {
            abort(404, 'No valid test found.');
        });



    });
