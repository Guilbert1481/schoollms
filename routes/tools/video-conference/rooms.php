<?php

use App\Http\Controllers\Tools\VideoConference\RoomController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->prefix('/tools/video-conference')
    ->name('tools.video-conference.')
    ->group(function () {
        Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');
        Route::get('/rooms/create', [RoomController::class, 'create'])->name('rooms.create');
        Route::post('/rooms', [RoomController::class, 'store'])->name('rooms.store');
        Route::get('/rooms/{room}/edit', [RoomController::class, 'edit'])->whereNumber('room')->name('rooms.edit');
        Route::put('/rooms/{room}', [RoomController::class, 'update'])->whereNumber('room')->name('rooms.update');
        Route::get('/rooms/{room}/join', [RoomController::class, 'join'])->whereNumber('room')->name('rooms.join');
    });
