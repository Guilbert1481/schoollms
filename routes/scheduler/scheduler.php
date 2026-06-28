<?php

use App\Http\Controllers\Scheduler\ApplyScheduleController;
use App\Http\Controllers\Scheduler\GenerateScheduleController;
use App\Http\Controllers\Scheduler\ResolveScheduleController;
use App\Http\Controllers\Scheduler\SchedulerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin,superadmin,registrar,program_head,dean'])
    ->prefix('/scheduler')
    ->name('scheduler.')
    ->group(function () {
        Route::get('/', [SchedulerController::class, 'index'])->name('index');
        Route::post('/config',   [SchedulerController::class, 'saveConfig'])->name('saveConfig');
        Route::post('/generate', GenerateScheduleController::class)->name('generate');
        Route::post('/apply',    ApplyScheduleController::class)->name('apply');
        Route::post('/resolve',  ResolveScheduleController::class)->name('resolve');
    });
