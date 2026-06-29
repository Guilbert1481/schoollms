<?php

namespace App\Http\Controllers\Finance\Concerns;

use App\Models\Invoice;
use Illuminate\Http\Request;

/**
 * Builds the invoices list (rows + columns + actions + status filter) so it can
 * be rendered both on the standalone Invoices page and inside the Billing
 * page's "Invoices" tab.
 */
trait BuildsInvoiceList
{
    /** @return array{rows: \Illuminate\Support\Collection, columns: array, actions: array, status: string, statuses: array} */
    protected function invoiceListData(Request $request): array
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

        $rows = $invoices->map(fn (Invoice $inv) => (object) [
            'id'             => $inv->id,
            'invoice_number' => $inv->invoice_number,
            'student'        => $inv->student ? trim($inv->student->first_name.' '.$inv->student->last_name) : '—',
            'total_label'    => 'PHP '.number_format((float) $inv->total_amount, 2),
            'paid_label'     => 'PHP '.number_format((float) $inv->paid_amount, 2),
            'balance_label'  => 'PHP '.number_format((float) $inv->balance, 2),
            'due'            => optional($inv->due_date)->format('M d, Y') ?? '—',
            'status'         => $this->invoiceStatusPill($inv),
        ]);

        return [
            'rows'     => $rows,
            'columns'  => $this->invoiceColumns(),
            'actions'  => $this->invoiceActions(),
            'status'   => $status,
            'statuses' => [
                'all'                   => 'All',
                Invoice::STATUS_UNPAID  => 'Unpaid',
                Invoice::STATUS_PARTIAL => 'Partial',
                Invoice::STATUS_PAID    => 'Paid',
            ],
        ];
    }

    protected function invoiceStatusPill(Invoice $invoice): string
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

    protected function invoiceColumns(): array
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

    protected function invoiceActions(): array
    {
        return [
            ['type' => 'link', 'label' => 'View', 'route' => 'finance.invoices.show', 'class' => 'bg-indigo-600 text-white hover:bg-indigo-700'],
        ];
    }
}
