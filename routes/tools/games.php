<?php

use App\Http\Controllers\Tools\Games\GameConfigController;
use App\Http\Controllers\Tools\Games\GamesController;
use App\Http\Controllers\Tools\Games\GameSessionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->prefix('/tools/games')
    ->name('tools.games.')
    ->group(function () {
        Route::get('/', [GamesController::class, 'index'])->name('index');
        Route::get('/api/questions', [GamesController::class, 'questions'])->name('questions');
        Route::post('/api/quiz-mode', [GamesController::class, 'saveQuizMode'])->name('quiz-mode');
        Route::get('/api/config-options', [GameConfigController::class, 'options'])->name('config-options');

        // Live multiplayer game sessions (Tug-of-War). GameSession is
        // school-scoped (BelongsToSchool), so {session} binding 404s across
        // schools; host-only actions add an owner check in the controller.
        Route::prefix('/api/sessions')->name('sessions.')->group(function () {
            Route::get('/classmates', [GameSessionController::class, 'classmates'])->name('classmates');
            Route::post('/', [GameSessionController::class, 'store'])->name('store');
            Route::post('/join', [GameSessionController::class, 'join'])->name('join');
            Route::get('/{session}', [GameSessionController::class, 'show'])->name('show');
            Route::post('/{session}/start', [GameSessionController::class, 'start'])->name('start');
            Route::post('/{session}/answer', [GameSessionController::class, 'answer'])->name('answer');
            Route::post('/{session}/advance', [GameSessionController::class, 'advance'])->name('advance');
            Route::post('/{session}/end', [GameSessionController::class, 'end'])->name('end');
        });

        Route::get('/{slug}', [GamesController::class, 'play'])
            ->where('slug', '[a-z0-9\-]+')
            ->name('play');
    });
