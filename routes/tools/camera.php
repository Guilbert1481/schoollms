<?php

use App\Http\Controllers\Tools\CameraController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
	->prefix('/tools')
	->name('tools.')
	->group(function () {
		Route::get('/camera', [CameraController::class, 'index'])->name('camera');
	});
