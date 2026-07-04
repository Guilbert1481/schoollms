<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Finance\Concerns\BuildsInvoiceList;
use App\Models\Invoice;
use App\Models\StudentEnrollment;
use App\Services\Finance\InvoiceService;
use App\Services\Payments\PaymentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    use BuildsInvoiceList;

    public function __construct(
        private readonly InvoiceService $invoices,
        private readonly PaymentService $payments,
    ) {
    }

    public function index(Request $request)
    {
        return view('finance.invoices.index', $this->invoiceListData($request));
    }

    public function show(Invoice $invoice)
    {
        $this->authorizeSchool($invoice);
        $invoice->load(['items', 'student', 'payments', 'enrollment.term', 'enrollment.program']);

        return view('finance.invoices.show', compact('invoice'));
    }

    /**
     * The invoice detail card only (no layout) — fetched over AJAX and shown in
     * a modal on the Billing / Invoices list.
     */
    public function preview(Invoice $invoice)
    {
        $this->authorizeSchool($invoice);
        $invoice->load(['items', 'student', 'payments', 'enrollment.term', 'enrollment.program']);

        return view('finance.invoices._detail', compact('invoice'));
    }

    /**
     * Generate an invoice for an enrollment (manual trigger from the ledger or
     * billing queue). Idempotent — one invoice per enrollment.
     */
    public function generate(Request $request)
    {
        $schoolId = (int) auth()->user()->school_id;

        $data = $request->validate([
            'enrollment_id' => ['required', 'integer'],
        ]);

        $enrollment = StudentEnrollment::query()
            ->where('school_id', $schoolId)
            ->with('student')
            ->findOrFail((int) $data['enrollment_id']);

        $invoice = $this->invoices->generateForEnrollment(
            $enrollment,
            actorId: (int) auth()->id(),
        );

        if (! $invoice) {
            return back()->with('error', 'Could not generate an invoice — the student has no linked account, or no active fees match this enrollment. Check Tuition & Fees Setup.');
        }

        $count = Invoice::where('school_id', $schoolId)
            ->where('student_enrollment_id', $enrollment->id)
            ->count();

        $message = $count > 1
            ? "Generated {$count} invoices for the payment schedule (starting {$invoice->invoice_number})."
            : "Invoice {$invoice->invoice_number} generated.";

        return back()->with('success', $message);
    }

    public function pay(Request $request, Invoice $invoice)
    {
        $this->authorizeSchool($invoice);

        $data = $request->validate([
            'amount'           => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
            'payment_method'   => ['required', 'string', 'max:50'],
            'reference_number' => ['nullable', 'string', 'max:120'],
        ]);

        $this->payments->recordInvoicePayment(
            actor: $request->user(),
            invoice: $invoice,
            amount: round((float) $data['amount'], 2),
            paymentMethod: (string) $data['payment_method'],
            referenceNumber: $data['reference_number'] ?? null,
        );

        return back()->with('success', 'Payment recorded against '.$invoice->invoice_number.'.');
    }

    public function downloadPdf(Invoice $invoice)
    {
        $this->authorizeSchool($invoice);
        $invoice->load(['items', 'student', 'school', 'enrollment.term', 'enrollment.program']);

        $pdf = Pdf::loadView('finance.pdf.invoice', [
            'invoice' => $invoice,
            'school'  => $invoice->school,
        ])->setPaper('a4');

        return $pdf->download('Invoice-'.$invoice->invoice_number.'.pdf');
    }

    protected function authorizeSchool(Invoice $invoice): void
    {
        abort_unless((int) $invoice->school_id === (int) auth()->user()->school_id, 404);
    }
}
