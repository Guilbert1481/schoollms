<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\StudentEnrollment;
use App\Services\Finance\InvoiceService;
use App\Services\Payments\PaymentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoices,
        private readonly PaymentService $payments,
    ) {
    }

    public function index(Request $request)
    {
        $schoolId = (int) auth()->user()->school_id;

        $status  = $request->string('status', 'all')->toString();
        $allowed = ['all', Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL, Invoice::STATUS_PAID];
        if (! in_array($status, $allowed, true)) {
            $status = 'all';
        }

        $invoices = Invoice::query()
            ->where('school_id', $schoolId)
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->with(['student:id,first_name,last_name', 'enrollment:id,term_id'])
            ->orderByDesc('id')
            ->get();

        $rows = $invoices->map(function (Invoice $inv) {
            return (object) [
                'id'             => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'student'        => $inv->student ? trim($inv->student->first_name.' '.$inv->student->last_name) : '—',
                'total_label'    => 'PHP '.number_format((float) $inv->total_amount, 2),
                'paid_label'     => 'PHP '.number_format((float) $inv->paid_amount, 2),
                'balance_label'  => 'PHP '.number_format((float) $inv->balance, 2),
                'due'            => optional($inv->due_date)->format('M d, Y') ?? '—',
                'status'         => $this->statusPill($inv),
            ];
        });

        return view('finance.invoices.index', [
            'rows'     => $rows,
            'columns'  => $this->columns(),
            'actions'  => $this->actions(),
            'status'   => $status,
            'statuses' => [
                'all'                   => 'All',
                Invoice::STATUS_UNPAID  => 'Unpaid',
                Invoice::STATUS_PARTIAL => 'Partial',
                Invoice::STATUS_PAID    => 'Paid',
            ],
        ]);
    }

    public function show(Invoice $invoice)
    {
        $this->authorizeSchool($invoice);
        $invoice->load(['items', 'student', 'payments', 'enrollment.term', 'enrollment.program']);

        return view('finance.invoices.show', compact('invoice'));
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

        return back()->with('success', "Invoice {$invoice->invoice_number} generated.");
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

    protected function statusPill(Invoice $invoice): string
    {
        if ($invoice->isOverdue()) {
            return '<span class="inline-flex rounded-full bg-rose-100 px-2 py-0.5 text-[11px] font-bold text-rose-700">Overdue</span>';
        }

        return match ($invoice->status) {
            Invoice::STATUS_PAID    => '<span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-bold text-emerald-700">Paid</span>',
            Invoice::STATUS_PARTIAL => '<span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-bold text-amber-700">Partial</span>',
            default                 => '<span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-600">Unpaid</span>',
        };
    }

    protected function columns(): array
    {
        return [
            ['key' => 'invoice_number', 'label' => 'Invoice #', 'width' => '180px'],
            ['key' => 'student', 'label' => 'Student', 'width' => '220px'],
            ['key' => 'total_label', 'label' => 'Total', 'width' => '140px'],
            ['key' => 'paid_label', 'label' => 'Paid', 'width' => '140px'],
            ['key' => 'balance_label', 'label' => 'Balance', 'width' => '140px'],
            ['key' => 'due', 'label' => 'Due', 'width' => '130px'],
            ['key' => 'status', 'label' => 'Status', 'width' => '120px', 'raw' => true],
        ];
    }

    protected function actions(): array
    {
        return [
            ['type' => 'link', 'label' => 'View', 'route' => 'finance.invoices.show', 'class' => 'bg-indigo-600 text-white hover:bg-indigo-700'],
        ];
    }
}
