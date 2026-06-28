@extends('layouts.app')

@php
    $cur = $setting->currency ?? 'PHP';
    $balanceColor = $balance > 0.005 ? 'text-rose-600' : ($balance < -0.005 ? 'text-sky-600' : 'text-emerald-600');
    $typeColors = [
        'charge' => 'bg-rose-50 text-rose-700',
        'payment' => 'bg-emerald-50 text-emerald-700',
        'discount' => 'bg-amber-50 text-amber-700',
        'adjustment' => 'bg-slate-100 text-slate-600',
        'refund' => 'bg-sky-50 text-sky-700',
    ];
@endphp

@section('content')
<div class="w-full space-y-6">

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">My Account</h1>
                <p class="text-sm text-slate-500">Your balance, charges, and Statements of Account.</p>
            </div>
            <div class="text-right">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Current Balance</div>
                <div class="text-3xl font-extrabold {{ $balanceColor }}">{{ $cur }} {{ number_format($balance, 2) }}</div>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="mb-3 text-lg font-semibold text-slate-800">Statements of Account</h2>
        @if($statements->isEmpty())
            <p class="text-sm text-slate-500">No statements yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-xs uppercase text-slate-400">
                            <th class="px-3 py-2">SOA #</th>
                            <th class="px-3 py-2">Period</th>
                            <th class="px-3 py-2 text-right">Closing Balance</th>
                            <th class="px-3 py-2">Due</th>
                            <th class="px-3 py-2 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($statements as $s)
                            <tr class="border-b last:border-0">
                                <td class="px-3 py-2 font-medium text-slate-700">{{ $s->soa_number }}</td>
                                <td class="px-3 py-2 text-slate-500">{{ $s->period_label }}</td>
                                <td class="px-3 py-2 text-right font-semibold">{{ $cur }} {{ number_format((float) $s->closing_balance, 2) }}</td>
                                <td class="px-3 py-2 text-slate-500">{{ optional($s->due_date)->format('M d, Y') ?? '—' }}</td>
                                <td class="px-3 py-2 text-right">
                                    <a href="{{ route('student.finance.soa.pdf', $s->id) }}" class="rounded bg-indigo-600 px-3 py-1 text-xs font-semibold text-white hover:bg-indigo-700">Download PDF</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="mb-3 text-lg font-semibold text-slate-800">Transaction History</h2>
        @if($entries->isEmpty())
            <p class="text-sm text-slate-500">No transactions yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-xs uppercase text-slate-400">
                            <th class="px-3 py-2">Date</th>
                            <th class="px-3 py-2">Type</th>
                            <th class="px-3 py-2">Description</th>
                            <th class="px-3 py-2 text-right">Charge</th>
                            <th class="px-3 py-2 text-right">Payment</th>
                            <th class="px-3 py-2 text-right">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($entries as $e)
                            <tr class="border-b last:border-0">
                                <td class="px-3 py-2 text-slate-500">{{ optional($e->entry_date)->format('M d, Y') }}</td>
                                <td class="px-3 py-2"><span class="rounded-full px-2 py-0.5 text-[11px] font-bold {{ $typeColors[$e->type] ?? 'bg-slate-100 text-slate-600' }}">{{ ucfirst($e->type) }}</span></td>
                                <td class="px-3 py-2 text-slate-700">{{ $e->description }}</td>
                                <td class="px-3 py-2 text-right text-rose-600">{{ (float) $e->debit > 0 ? $cur.' '.number_format((float) $e->debit, 2) : '' }}</td>
                                <td class="px-3 py-2 text-right text-emerald-600">{{ (float) $e->credit > 0 ? $cur.' '.number_format((float) $e->credit, 2) : '' }}</td>
                                <td class="px-3 py-2 text-right font-semibold text-slate-800">{{ $cur }} {{ number_format((float) $e->balance_after, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
