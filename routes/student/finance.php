<?php

use App\Http\Controllers\Student\FinanceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Student Finance — own ledger balance, statements, invoices, payments
|--------------------------------------------------------------------------
*/
Route::middleware(['web', 'auth', 'role:student'])
    ->prefix('student')
    ->name('student.')
    ->group(function () {
        Route::get('/finance', [FinanceController::class, 'index'])->name('finance.index');
        Route::get('/finance/invoices', [FinanceController::class, 'invoices'])->name('finance.invoices');
        Route::get('/finance/payments', [FinanceController::class, 'payments'])->name('finance.payments');

        Route::get('/finance/statements/{statement}/pdf', [FinanceController::class, 'downloadStatement'])->name('finance.soa.pdf');
        Route::get('/finance/invoices/{invoice}/pdf', [FinanceController::class, 'downloadInvoice'])->name('finance.invoice.pdf');
    });
