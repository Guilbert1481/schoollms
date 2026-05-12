<?php

use App\Http\Controllers\Tools\PDFImageController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
	->prefix('/tools')
	->name('tools.')
	->group(function () {
		Route::post('/pdf-to-image', [PDFImageController::class, 'convert'])->name('pdf-to-image');
	});
