<?php

use App\Http\Controllers\Tools\EditPDFController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
	->prefix('/tools')
	->name('tools.')
	->group(function () {
		Route::get('/edit-pdf', [EditPDFController::class, 'index'])->name('edit-pdf');
	});
