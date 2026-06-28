<?php

use App\Http\Controllers\Finance\BillingController;
use App\Http\Controllers\Finance\BillingQueueController;
use App\Http\Controllers\Finance\TuitionSetupController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
	->prefix('finance')
	->name('finance.')
	->group(function () {
		Route::get('/billing', [BillingController::class, 'index'])
			->name('billing.index');

		// Admissions → Billing handoff queue (Finance Manager)
		Route::get('/billing-queue', [BillingQueueController::class, 'index'])
			->name('billing.queue');
		Route::post('/billing-queue/{enrollment}/paid', [BillingQueueController::class, 'markPaid'])
			->name('billing.paid');

		Route::middleware('role:finance_manager,admin,superadmin')
			->prefix('tuition-setup')
			->name('tuition-setup.')
			->group(function () {
				Route::get('/', [TuitionSetupController::class, 'index'])
					->name('index');

				Route::post('/fees', [TuitionSetupController::class, 'storeFee'])
					->name('fees.store');
				Route::put('/fees/{fee}', [TuitionSetupController::class, 'updateFee'])
					->name('fees.update');
				Route::delete('/fees/{fee}', [TuitionSetupController::class, 'destroyFee'])
					->name('fees.destroy');

				Route::post('/discounts', [TuitionSetupController::class, 'storeDiscount'])
					->name('discounts.store');
				Route::put('/discounts/{discount}', [TuitionSetupController::class, 'updateDiscount'])
					->name('discounts.update');
				Route::delete('/discounts/{discount}', [TuitionSetupController::class, 'destroyDiscount'])
					->name('discounts.destroy');
			});
	});
