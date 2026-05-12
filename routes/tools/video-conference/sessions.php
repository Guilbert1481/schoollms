<?php

use App\Http\Controllers\Tools\VideoConference\SessionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->prefix('/tools/video-conference')
    ->name('tools.video-conference.')
    ->group(function () {
        Route::get('/sessions', [SessionController::class, 'index'])->name('sessions.index');
        Route::post('/rooms/{room}/sessions/start', [SessionController::class, 'start'])->whereNumber('room')->name('sessions.start');
        Route::post('/rooms/{room}/sessions/reopen', [SessionController::class, 'reopen'])->whereNumber('room')->name('sessions.reopen');
        Route::post('/sessions/{session}/end', [SessionController::class, 'end'])->whereNumber('session')->name('sessions.end');
    });
