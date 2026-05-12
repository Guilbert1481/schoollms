<?php

use App\Http\Controllers\Tools\VideoConference\MeetingRoomController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->prefix('/tools/video-conference')
    ->name('tools.video-conference.')
    ->group(function () {
        Route::get('/meeting-room/{room}', [MeetingRoomController::class, 'show'])->whereNumber('room')->name('meeting-room.show');
    });
