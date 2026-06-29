{{--
    Shared ledger table. Used by the drawer's Ledger tab and the "View Ledger"
    modal. Columns: Date · Type · Description · Reference · Debit · Credit · Balance.
    Expects: $entries (collection of ledger rows), $currency (optional).
--}}
@php
    $num = fn ($v) => (float) $v > 0 ? number_format((float) $v, 2) : '-';
    $fmt = fn ($d) => \Illuminate\Support\Carbon::parse((string) $d)->format('M d, Y');
    // Transaction-type → label + colours (inline hex; build-independent).
    $typeStyles = [
        'charge'     => ['Charge',     '#e0e7ff', '#4338ca'],
        'payment'    => ['Payment',    '#d1fae5', '#047857'],
        'discount'   => ['Discount',   '#ede9fe', '#6d28d9'],
        'adjustment' => ['Adjustment', '#fef3c7', '#b45309'],
        'refund'     => ['Refund',     '#e0f2fe', '#0369a1'],
    ];
@endphp

<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-[10px] font-bold uppercase tracking-wide text-slate-400">
                <th class="py-2 pr-2">Date</th>
                <th class="py-2 pr-2">Type</th>
                <th class="py-2 pr-2">Description</th>
                <th class="py-2 pr-2">Reference</th>
                <th class="py-2 pr-2 text-right">Debit</th>
                <th class="py-2 pr-2 text-right">Credit</th>
                <th class="py-2 text-right">Balance</th>
            </tr>
        </thead>
        <tbody>
            @forelse($entries as $e)
                @php
                    $t = strtolower((string) ($e->type ?? 'adjustment'));
                    $ts = $typeStyles[$t] ?? [ucfirst($t), '#f1f5f9', '#475569'];
                @endphp
                <tr class="border-t border-slate-100">
                    <td class="whitespace-nowrap py-2 pr-2 text-slate-600">{{ $fmt($e->entry_date) }}</td>
                    <td class="py-2 pr-2">
                        <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold"
                              style="background-color:{{ $ts[1] }};color:{{ $ts[2] }};">{{ $ts[0] }}</span>
                    </td>
                    <td class="py-2 pr-2 text-slate-700">{{ $e->description ?: '—' }}</td>
                    <td class="py-2 pr-2 text-slate-500">{{ $e->reference ?: '—' }}</td>
                    <td class="py-2 pr-2 text-right text-slate-700">{{ $num($e->debit) }}</td>
                    <td class="py-2 pr-2 text-right text-slate-700">{{ $num($e->credit) }}</td>
                    <td class="py-2 text-right font-semibold text-slate-800">{{ number_format((float) $e->balance_after, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="py-6 text-center text-xs text-slate-400">No ledger entries yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
