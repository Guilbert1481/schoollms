<?php

use App\Http\Controllers\Finance\FinanceSettingController;
use App\Http\Controllers\Finance\InvoiceController;
use App\Http\Controllers\Finance\LedgerController;
use App\Http\Controllers\Finance\StatementController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Finance — Student Ledgers, Invoices, Statements of Account, Settings
|--------------------------------------------------------------------------
| Restricted to finance staff. The student-facing counterparts live in
| routes/student/finance.php.
*/
Route::middleware(['web', 'auth', 'role:finance_manager,admin,superadmin'])
    ->prefix('finance')
    ->name('finance.')
    ->group(function () {

        // Individual student ledgers
        Route::get('/ledger', [LedgerController::class, 'index'])->name('ledger.index');
        Route::get('/ledger/export', [LedgerController::class, 'export'])->name('ledger.export');
        Route::post('/ledger/record-payment', [LedgerController::class, 'recordPayment'])->name('ledger.record-payment');
        Route::post('/ledger/send-reminder', [LedgerController::class, 'sendReminder'])->name('ledger.send-reminder');
        Route::post('/ledger/import-entries', [LedgerController::class, 'importEntries'])->name('ledger.import-entries');
        Route::get('/ledger/{student}/drawer', [LedgerController::class, 'drawer'])->name('ledger.drawer');
        Route::get('/ledger/{student}/entries', [LedgerController::class, 'entries'])->name('ledger.entries');
        Route::get('/ledger/{student}/export', [LedgerController::class, 'studentExport'])->name('ledger.student-export');
        Route::post('/ledger/{student}/adjust', [LedgerController::class, 'adjust'])->name('ledger.adjust');

        // Invoices
        Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::post('/invoices/generate', [InvoiceController::class, 'generate'])->name('invoices.generate');
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');
        Route::post('/invoices/{invoice}/pay', [InvoiceController::class, 'pay'])->name('invoices.pay');

        // Statements of Account
        Route::get('/statements', [StatementController::class, 'index'])->name('statements.index');
        Route::post('/statements/generate', [StatementController::class, 'generate'])->name('statements.generate');
        Route::post('/statements/run-batch', [StatementController::class, 'runBatch'])->name('statements.batch');
        Route::get('/statements/{statement}', [StatementController::class, 'show'])->name('statements.show');
        Route::get('/statements/{statement}/pdf', [StatementController::class, 'downloadPdf'])->name('statements.pdf');

        // Finance settings — grouped sub-pages under the Settings sidebar parent.
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/transaction-types', [FinanceSettingController::class, 'transactionTypes'])->name('transaction-types');
            Route::get('/payment-methods', [FinanceSettingController::class, 'paymentMethods'])->name('payment-methods');
            Route::get('/penalty-rules', [FinanceSettingController::class, 'penaltyRules'])->name('penalty-rules');
            Route::get('/receipt-numbering', [FinanceSettingController::class, 'receiptNumbering'])->name('receipt-numbering');
            Route::get('/invoice-numbering', [FinanceSettingController::class, 'invoiceNumbering'])->name('invoice-numbering');
            Route::get('/soa-template', [FinanceSettingController::class, 'soaTemplate'])->name('soa-template');
            Route::get('/preferences', [FinanceSettingController::class, 'preferences'])->name('preferences');
            Route::put('/preferences', [FinanceSettingController::class, 'updatePreferences'])->name('preferences.update');
        });
    });
