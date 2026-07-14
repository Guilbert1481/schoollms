{{--
    Invoice detail card — shared by the standalone show page and the invoice
    modal (fetched via finance.invoices.preview). Self-contained: expects only
    $invoice (with items + payments loaded). No PDF button on the finance side
    (kept on the student/parent portal). A Pay Now action is added in Phase 2.
--}}
@php
    $cur = 'PHP';
    $student = $invoice->student;
    $studentName = $student ? trim($student->first_name.' '.$student->last_name) : '—';

    // Context line: for basic ed show the grade level + section (more useful
    // than the enrollment term); for higher ed keep the programme + term.
    $enr = $invoice->enrollment;
    $isBasicInv = $enr && (
        in_array(strtolower((string) $enr->education_level), ['kinder', 'elementary', 'junior_high', 'senior_high', 'basic', 'basic_ed'], true)
        || (empty($enr->program_id) && ! empty($enr->education_node_id))
    );
    if ($isBasicInv && $enr) {
        $gradeName = $enr->education_node_id
            ? \Illuminate\Support\Facades\DB::table('education_nodes')->where('id', $enr->education_node_id)->value('name')
            : null;
        $gradeName = $gradeName ?: ($enr->year_level ? 'Grade '.$enr->year_level : 'Basic Ed');
        $sectionName = $enr->section_id
            ? \Illuminate\Support\Facades\DB::table('sections')->where('id', $enr->section_id)->value('name')
            : null;
        $invContextLabel = trim($gradeName.($sectionName ? ' · '.$sectionName : ''));
    } else {
        $invContextLabel = trim(($enr?->program?->code ?? '').' '.($enr?->term?->name ?? ''));
    }
@endphp

<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 pb-4">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Invoice {{ $invoice->invoice_number }}</h1>
            <p class="text-sm text-slate-500">{{ $studentName }}</p>
            <p class="text-xs text-slate-400">{{ $invContextLabel }}</p>
        </div>
        <div class="text-right text-sm text-slate-500">
            <div>Billed: {{ optional($invoice->billing_date)->format('M d, Y') ?? optional($invoice->issue_date)->format('M d, Y') ?? '—' }}</div>
            <div>Due: {{ optional($invoice->due_date)->format('M d, Y') ?? '—' }}</div>
            <div class="mt-1">
                <span class="rounded-full px-2 py-0.5 text-[11px] font-bold
                    {{ $invoice->status === 'paid' ? 'bg-emerald-100 text-emerald-700' : ($invoice->status === 'partial' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600') }}">
                    {{ ucfirst($invoice->status) }}
                </span>
            </div>
        </div>
    </div>

    <table class="mt-4 w-full text-sm">
        <thead>
            <tr class="border-b text-left text-xs uppercase text-slate-400">
                <th class="px-3 py-2">Description</th>
                <th class="px-3 py-2">Basis</th>
                <th class="px-3 py-2 text-right">Qty</th>
                <th class="px-3 py-2 text-right">Unit</th>
                <th class="px-3 py-2 text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
                <tr class="border-b last:border-0">
                    <td class="px-3 py-2 text-slate-700">{{ $item->description }}</td>
                    <td class="px-3 py-2 text-slate-500">{{ $item->billing_basis === 'per_unit' ? 'Per unit' : 'Fixed' }}</td>
                    <td class="px-3 py-2 text-right">{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}</td>
                    <td class="px-3 py-2 text-right">{{ $cur }} {{ number_format((float) $item->unit_amount, 2) }}</td>
                    <td class="px-3 py-2 text-right">{{ $cur }} {{ number_format((float) $item->net_amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4 flex justify-end">
        <div class="w-64 space-y-1 text-sm">
            <div class="flex justify-between"><span class="text-slate-500">Subtotal</span><span>{{ $cur }} {{ number_format((float) $invoice->subtotal_amount, 2) }}</span></div>
            <div class="flex justify-between"><span class="text-slate-500">Discount</span><span>{{ $cur }} {{ number_format((float) $invoice->discount_amount, 2) }}</span></div>
            <div class="flex justify-between border-t pt-1 font-bold"><span>Total</span><span>{{ $cur }} {{ number_format((float) $invoice->total_amount, 2) }}</span></div>
            <div class="flex justify-between"><span class="text-slate-500">Paid</span><span>{{ $cur }} {{ number_format((float) $invoice->paid_amount, 2) }}</span></div>
            <div class="flex justify-between text-rose-600 font-semibold"><span>Balance</span><span>{{ $cur }} {{ number_format((float) $invoice->balance, 2) }}</span></div>
        </div>
    </div>

    @if((float) $invoice->balance > 0)
        <div class="mt-5 flex justify-end border-t border-slate-100 pt-4">
            <a href="{{ route('checkout.invoice.show', $invoice) }}"
               class="inline-flex items-center rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">
                Pay Now
            </a>
        </div>
    @endif
</div>

@if($invoice->payments->isNotEmpty())
    <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="mb-3 text-lg font-semibold text-slate-800">Payments</h2>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b text-left text-xs uppercase text-slate-400">
                    <th class="px-3 py-2">Date</th>
                    <th class="px-3 py-2">Reference</th>
                    <th class="px-3 py-2">Method</th>
                    <th class="px-3 py-2 text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->payments as $p)
                    <tr class="border-b last:border-0">
                        <td class="px-3 py-2 text-slate-500">{{ optional($p->paid_at)->format('M d, Y') }}</td>
                        <td class="px-3 py-2 text-slate-500">{{ $p->reference_number }}</td>
                        <td class="px-3 py-2 text-slate-500">{{ ucfirst(str_replace('_', ' ', (string) $p->payment_method)) }}</td>
                        <td class="px-3 py-2 text-right text-emerald-600">{{ $cur }} {{ number_format((float) $p->amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
