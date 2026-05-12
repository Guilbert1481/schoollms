<?php

use App\Http\Controllers\Finance\PaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
	->prefix('finance')
	->name('finance.')
	->group(function () {
		Route::get('/payments', [PaymentController::class, 'index'])
			->name('payments.index');

		Route::post('/payments', [PaymentController::class, 'store'])
			->name('payments.store');
	});

