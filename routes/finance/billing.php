<?php

use App\Http\Controllers\Finance\BillingController;
use App\Http\Controllers\Finance\EnrollmentQueueController;
use App\Http\Controllers\Finance\TuitionSetupController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
	->prefix('finance')
	->name('finance.')
	->group(function () {
		Route::get('/billing', [BillingController::class, 'index'])
			->name('billing.index');

		// Enrollment decision queue: officially / provisionally enroll or
		// reject gate-approved students (with or without payment).
		Route::middleware('role:finance_manager,admin,superadmin')->group(function () {
			Route::get('/enrollment-queue', [EnrollmentQueueController::class, 'index'])
				->name('enrollment-queue.index');
			Route::put('/enrollment-queue/{enrollment}/decision', [EnrollmentQueueController::class, 'decide'])
				->name('enrollment-queue.decide');
		});

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

				Route::post('/incidental', [TuitionSetupController::class, 'storeIncidental'])
					->name('incidental.store');
				Route::put('/incidental/{incidental}', [TuitionSetupController::class, 'updateIncidental'])
					->name('incidental.update');
				Route::delete('/incidental/{incidental}', [TuitionSetupController::class, 'destroyIncidental'])
					->name('incidental.destroy');

				Route::post('/discounts', [TuitionSetupController::class, 'storeDiscount'])
					->name('discounts.store');
				Route::put('/discounts/{discount}', [TuitionSetupController::class, 'updateDiscount'])
					->name('discounts.update');
				Route::delete('/discounts/{discount}', [TuitionSetupController::class, 'destroyDiscount'])
					->name('discounts.destroy');

				Route::post('/scholarships', [TuitionSetupController::class, 'storeScholarship'])
					->name('scholarships.store');
				Route::put('/scholarships/{scholarship}', [TuitionSetupController::class, 'updateScholarship'])
					->name('scholarships.update');
				Route::delete('/scholarships/{scholarship}', [TuitionSetupController::class, 'destroyScholarship'])
					->name('scholarships.destroy');

				Route::post('/payment-plans', [TuitionSetupController::class, 'storePaymentPlan'])
					->name('payment-plans.store');
				Route::put('/payment-plans/{paymentPlan}', [TuitionSetupController::class, 'updatePaymentPlan'])
					->name('payment-plans.update');
				Route::delete('/payment-plans/{paymentPlan}', [TuitionSetupController::class, 'destroyPaymentPlan'])
					->name('payment-plans.destroy');

				Route::post('/penalty-rules', [TuitionSetupController::class, 'storePenalty'])
					->name('penalty-rules.store');
				Route::put('/penalty-rules/{penaltyRule}', [TuitionSetupController::class, 'updatePenalty'])
					->name('penalty-rules.update');
				Route::delete('/penalty-rules/{penaltyRule}', [TuitionSetupController::class, 'destroyPenalty'])
					->name('penalty-rules.destroy');
			});
	});
