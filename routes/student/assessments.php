<?php

use App\Http\Controllers\Student\AssessmentController;
use App\Http\Controllers\Student\SnakeLadderController;
use App\Http\Controllers\Student\SpeedDashController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| STUDENT — ONLINE ASSESSMENTS (take flow)
|--------------------------------------------------------------------------
| The student side of online tests: start page → take screen → autosave →
| submit → result. Availability, enrolment, and the server deadline are all
| re-checked in the controller on every action.
*/

Route::middleware(['web', 'auth', 'role:student'])
    ->prefix('student/assessments')
    ->name('student.assessments.')
    ->group(function () {
        Route::get('/', [AssessmentController::class, 'index'])->name('index');
        Route::get('{test}/start', [AssessmentController::class, 'start'])->name('start');
        Route::post('{test}/begin', [AssessmentController::class, 'begin'])->name('begin');
        Route::get('{test}/take', [AssessmentController::class, 'take'])->name('take');
        Route::get('{test}/result', [AssessmentController::class, 'result'])->name('result');

        Route::post('attempt/{attempt}/answer', [AssessmentController::class, 'answer'])->name('answer');
        Route::post('attempt/{attempt}/event', [AssessmentController::class, 'event'])->name('event');
        Route::post('attempt/{attempt}/submit', [AssessmentController::class, 'submit'])->name('submit');

        // Quiz Speed Dash — the GAME delivery of the same attempt/grading
        // pipeline. Same guards, same tables, same grader.
        Route::get('{test}/play', [SpeedDashController::class, 'play'])->name('play');
        Route::post('attempt/{attempt}/game-answer', [SpeedDashController::class, 'answer'])->name('game-answer');
        Route::post('attempt/{attempt}/game-finish', [SpeedDashController::class, 'finish'])->name('game-finish');

        // Quiz Snakes & Ladders — the BOARD-GAME delivery of the same
        // attempt/grading pipeline. Dice and movement are server-authoritative.
        Route::get('{test}/board', [SnakeLadderController::class, 'play'])->name('board');
        Route::post('attempt/{attempt}/board-answer', [SnakeLadderController::class, 'answer'])->name('board-answer');
        Route::post('attempt/{attempt}/board-roll', [SnakeLadderController::class, 'roll'])->name('board-roll');
        Route::post('attempt/{attempt}/board-finish', [SnakeLadderController::class, 'finish'])->name('board-finish');
    });
