{{-- Discounts & scholarships partial — loaded into the studentDiscountsModal. --}}
@php
    $sym = $currency ?? '₱';
    $hasScholarship = $enrollment && ! empty($enrollment->scholarship_label);
    $hasCashDiscount = $enrollment
        && ($enrollment->payment_option ?? null) === 'cash'
        && (float) ($enrollment->cash_discount_value ?? 0) > 0;
    $fmtPct = fn ($v) => rtrim(rtrim(number_format((float) $v, 2), '0'), '.');
@endphp

<div class="space-y-5 text-sm">
    <div>
        <h4 class="mb-2 text-xs font-bold uppercase tracking-wide text-slate-500">Scholarship</h4>
        @if($hasScholarship)
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3">
                <div class="font-semibold text-emerald-800">{{ $enrollment->scholarship_label }}</div>
                <div class="mt-1 text-xs text-emerald-700">
                    @if($enrollment->scholarship_percent !== null)
                        {{ $fmtPct($enrollment->scholarship_percent) }}% off
                        {{ ($enrollment->scholarship_apply_to ?? 'total') === 'tuition' ? 'tuition' : 'total assessment' }}
                        ·
                    @endif
                    {{ $sym }}{{ number_format((float) $enrollment->scholarship_amount, 2) }}
                </div>
            </div>
        @else
            <p class="text-slate-400">No scholarship on the current enrollment.</p>
        @endif
    </div>

    <div>
        <h4 class="mb-2 text-xs font-bold uppercase tracking-wide text-slate-500">Payment Plan Discount</h4>
        @if($hasCashDiscount)
            <div class="rounded-lg border border-sky-200 bg-sky-50 px-4 py-3">
                <div class="font-semibold text-sky-800">Cash Discount{{ $enrollment->plan_name ? ' · '.$enrollment->plan_name : '' }}</div>
                <div class="mt-1 text-xs text-sky-700">
                    @if(($enrollment->cash_discount_type ?? '') === 'percentage')
                        {{ $fmtPct($enrollment->cash_discount_value) }}% off for paying in full (cash)
                    @else
                        {{ $sym }}{{ number_format((float) $enrollment->cash_discount_value, 2) }} off for paying in full (cash)
                    @endif
                </div>
            </div>
        @else
            <p class="text-slate-400">No payment-plan discount.</p>
        @endif
    </div>

    @if($itemDiscounts->isNotEmpty())
        <div>
            <h4 class="mb-2 text-xs font-bold uppercase tracking-wide text-slate-500">Fee Discounts</h4>
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="text-slate-500">
                        <th class="py-1 pr-3 font-semibold">Discount</th>
                        <th class="py-1 pr-3 font-semibold">Invoice</th>
                        <th class="py-1 text-right font-semibold">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($itemDiscounts as $d)
                        <tr class="border-t border-slate-100">
                            <td class="py-1.5 pr-3">{{ $d->discount_name ?: ($d->description ?: 'Discount') }}</td>
                            <td class="py-1.5 pr-3">{{ $d->invoice_number }}</td>
                            <td class="py-1.5 text-right">{{ $sym }}{{ number_format((float) $d->discount_amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div>
        <h4 class="mb-2 text-xs font-bold uppercase tracking-wide text-slate-500">Applied on Invoices</h4>
        @if($invoiceDiscounts->isNotEmpty())
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="text-slate-500">
                        <th class="py-1 pr-3 font-semibold">Invoice</th>
                        <th class="py-1 pr-3 font-semibold">Notes</th>
                        <th class="py-1 text-right font-semibold">Discount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoiceDiscounts as $d)
                        <tr class="border-t border-slate-100">
                            <td class="py-1.5 pr-3">{{ $d->invoice_number }}</td>
                            <td class="py-1.5 pr-3 text-slate-500">{{ $d->notes ?: '—' }}</td>
                            <td class="py-1.5 text-right">{{ $sym }}{{ number_format((float) $d->discount_amount, 2) }}</td>
                        </tr>
                    @endforeach
                    <tr class="border-t border-slate-200 font-semibold text-slate-700">
                        <td class="py-1.5 pr-3" colspan="2">Total discounts applied</td>
                        <td class="py-1.5 text-right">{{ $sym }}{{ number_format((float) $invoiceDiscounts->sum('discount_amount'), 2) }}</td>
                    </tr>
                </tbody>
            </table>
        @else
            <p class="text-slate-400">No discounts have been applied on invoices yet.</p>
        @endif
    </div>
</div>
