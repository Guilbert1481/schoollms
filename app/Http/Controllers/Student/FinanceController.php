<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\FinanceSetting;
use App\Models\Invoice;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\StatementOfAccount;
use App\Services\Finance\LedgerService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

/**
 * Student-facing finance: the student's own ledger balance, Statements of
 * Account, invoices, and payment history. Read-only — students cannot mutate
 * their ledger.
 */
class FinanceController extends Controller
{
    public function __construct(private readonly LedgerService $ledger)
    {
    }

    public function index(Request $request)
    {
        $user     = $request->user();
        $schoolId = (int) $user->school_id;

        $entries = LedgerEntry::query()
            ->where('school_id', $schoolId)
            ->where('student_id', $user->id)
            ->orderBy('id')
            ->get();

        $statements = StatementOfAccount::query()
            ->where('school_id', $schoolId)
            ->where('student_id', $user->id)
            ->orderByDesc('id')
            ->get();

        return view('student.finance.index', [
            'balance'    => $this->ledger->currentBalance($schoolId, (int) $user->id),
            'entries'    => $entries,
            'statements' => $statements,
            'setting'    => FinanceSetting::forSchool($schoolId),
        ]);
    }

    public function invoices(Request $request)
    {
        $user     = $request->user();
        $schoolId = (int) $user->school_id;

        $invoices = Invoice::query()
            ->where('school_id', $schoolId)
            ->where('student_id', $user->id)
            ->orderByDesc('id')
            ->get();

        return view('student.finance.invoices', compact('invoices'));
    }

    public function payments(Request $request)
    {
        $user     = $request->user();
        $schoolId = (int) $user->school_id;

        $payments = Payment::query()
            ->where('school_id', $schoolId)
            ->where('student_id', $user->id)
            ->whereNull('training_enrollment_id')
            ->orderByDesc('id')
            ->get();

        return view('student.finance.payments', compact('payments'));
    }

    public function downloadStatement(Request $request, StatementOfAccount $statement)
    {
        abort_unless(
            (int) $statement->student_id === (int) $request->user()->id
                && (int) $statement->school_id === (int) $request->user()->school_id,
            404
        );

        $statement->load(['student', 'school']);

        $pdf = Pdf::loadView('finance.pdf.statement_of_account', [
            'statement' => $statement,
            'school'    => $statement->school,
        ])->setPaper('a4');

        return $pdf->download('SOA-'.$statement->soa_number.'.pdf');
    }

    public function downloadInvoice(Request $request, Invoice $invoice)
    {
        abort_unless(
            (int) $invoice->student_id === (int) $request->user()->id
                && (int) $invoice->school_id === (int) $request->user()->school_id,
            404
        );

        $invoice->load(['items', 'student', 'school', 'enrollment.term', 'enrollment.program']);

        $pdf = Pdf::loadView('finance.pdf.invoice', [
            'invoice' => $invoice,
            'school'  => $invoice->school,
        ])->setPaper('a4');

        return $pdf->download('Invoice-'.$invoice->invoice_number.'.pdf');
    }
}
