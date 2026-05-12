<?php

use App\Http\Controllers\Finance\BillingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
	->prefix('finance')
	->name('finance.')
	->group(function () {
		Route::get('/billing', [BillingController::class, 'index'])
			->name('billing.index');
	});

