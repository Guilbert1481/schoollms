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
        Route::get('/ledger/{student}/discounts', [LedgerController::class, 'discounts'])->name('ledger.discounts');
        Route::get('/ledger/{student}/export', [LedgerController::class, 'studentExport'])->name('ledger.student-export');
        Route::post('/ledger/{student}/adjust', [LedgerController::class, 'adjust'])->name('ledger.adjust');

        // Invoices
        Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::post('/invoices/generate', [InvoiceController::class, 'generate'])->name('invoices.generate');
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::get('/invoices/{invoice}/preview', [InvoiceController::class, 'preview'])->name('invoices.preview');
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

            // These sub-pages moved into Finance Preferences as tabs —
            // old bookmarks land on the matching tab.
            foreach (['payment-methods', 'penalty-rules', 'receipt-numbering', 'invoice-numbering', 'soa-template'] as $tab) {
                Route::get('/'.$tab, fn () => redirect()->route('finance.settings.preferences', ['tab' => $tab]))
                    ->name($tab);
            }

            Route::get('/preferences', [FinanceSettingController::class, 'preferences'])->name('preferences');
            Route::put('/preferences', [FinanceSettingController::class, 'updatePreferences'])->name('preferences.update');
            Route::get('/email', [FinanceSettingController::class, 'email'])->name('email');
            Route::put('/email', [FinanceSettingController::class, 'updateEmail'])->name('email.update');
            Route::post('/email/test-smtp', [FinanceSettingController::class, 'testSmtp'])->name('email.test-smtp');
            Route::post('/email/test-imap', [FinanceSettingController::class, 'testImap'])->name('email.test-imap');
        });
    });
