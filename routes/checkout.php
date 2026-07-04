<?php

use App\Http\Controllers\Checkout\CheckoutController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Online Checkout — pay an invoice + upload proof of payment
|--------------------------------------------------------------------------
| Shared by the student/parent portal (pay your own invoice) and finance
| staff (submit on behalf of a walk-in payer). Creates a PENDING
| PaymentSubmission; finance verifies it before any ledger credit posts.
*/
Route::middleware(['web', 'auth', 'role:student,finance_manager,admin,superadmin'])
    ->prefix('checkout')
    ->name('checkout.')
    ->group(function () {
        Route::get('/invoices/{invoice}', [CheckoutController::class, 'show'])->name('invoice.show');
        Route::post('/invoices/{invoice}', [CheckoutController::class, 'submit'])->name('invoice.submit');
    });
