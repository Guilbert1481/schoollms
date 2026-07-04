<?php

use App\Http\Controllers\Finance\PaymentController;
use Illuminate\Support\Facades\Route;

// Finance staff only — this whole group reads/records payments, so it carries
// the same role gate as every other finance route file.
Route::middleware(['web', 'auth', 'role:finance_manager,admin,superadmin'])
	->prefix('finance')
	->name('finance.')
	->group(function () {
		Route::get('/payments', [PaymentController::class, 'index'])
			->name('payments.index');

		Route::post('/payments', [PaymentController::class, 'store'])
			->name('payments.store');

		// Proof-of-payment review (money-posting).
		Route::post('/payments/submissions/{submission}/verify', [PaymentController::class, 'verify'])
			->name('payments.submissions.verify');
		Route::post('/payments/submissions/{submission}/reject', [PaymentController::class, 'reject'])
			->name('payments.submissions.reject');
	});

