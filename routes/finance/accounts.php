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
        Route::get('/ledger/{student}', [LedgerController::class, 'show'])->name('ledger.show');
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

        // Finance settings (billing frequency, auto-generation, due days)
        Route::get('/settings', [FinanceSettingController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [FinanceSettingController::class, 'update'])->name('settings.update');
    });
