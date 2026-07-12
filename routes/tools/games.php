<?php

use App\Http\Controllers\Tools\Games\GamesController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->prefix('/tools/games')
    ->name('tools.games.')
    ->group(function () {
        Route::get('/', [GamesController::class, 'index'])->name('index');
        Route::get('/api/questions', [GamesController::class, 'questions'])->name('questions');
        Route::post('/api/quiz-mode', [GamesController::class, 'saveQuizMode'])->name('quiz-mode');
        Route::get('/{slug}', [GamesController::class, 'play'])
            ->where('slug', '[a-z0-9\-]+')
            ->name('play');
    });
