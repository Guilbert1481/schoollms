<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\School\Settings\Calendar\CalendarController;

Route::middleware(['web', 'auth'])
    ->prefix('school/settings/master-data/calendar')
    ->name('school.settings.master-data.calendar.')
    ->group(function () {

        Route::get('/', [CalendarController::class, 'index'])
            ->name('index');

    });