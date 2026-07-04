@extends('layouts.app')

@php $cur = 'PHP'; @endphp

@section('content')
<div class="w-full space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-800">Billing Details</h1>
        <p class="text-sm text-slate-500">Invoices issued to your account.</p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        @if($invoices->isEmpty())
            <p class="text-sm text-slate-500">No invoices yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-xs uppercase text-slate-400">
                            <th class="px-3 py-2">Invoice #</th>
                            <th class="px-3 py-2">Issued</th>
                            <th class="px-3 py-2">Due</th>
                            <th class="px-3 py-2 text-right">Total</th>
                            <th class="px-3 py-2 text-right">Paid</th>
                            <th class="px-3 py-2 text-right">Balance</th>
                            <th class="px-3 py-2">Status</th>
                            <th class="px-3 py-2 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoices as $inv)
                            <tr class="border-b last:border-0">
                                <td class="px-3 py-2 font-medium text-slate-700">{{ $inv->invoice_number }}</td>
                                <td class="px-3 py-2 text-slate-500">{{ optional($inv->issue_date)->format('M d, Y') ?? '—' }}</td>
                                <td class="px-3 py-2 text-slate-500">{{ optional($inv->due_date)->format('M d, Y') ?? '—' }}</td>
                                <td class="px-3 py-2 text-right">{{ $cur }} {{ number_format((float) $inv->total_amount, 2) }}</td>
                                <td class="px-3 py-2 text-right">{{ $cur }} {{ number_format((float) $inv->paid_amount, 2) }}</td>
                                <td class="px-3 py-2 text-right font-semibold">{{ $cur }} {{ number_format((float) $inv->balance, 2) }}</td>
                                <td class="px-3 py-2">
                                    <span class="rounded-full px-2 py-0.5 text-[11px] font-bold
                                        {{ $inv->status === 'paid' ? 'bg-emerald-100 text-emerald-700' : ($inv->status === 'partial' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600') }}">
                                        {{ ucfirst($inv->status) }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <span style="display:inline-flex; gap:0.375rem; justify-content:flex-end;">
                                        @if((float) $inv->balance > 0)
                                            <a href="{{ route('checkout.invoice.show', $inv->id) }}" class="rounded bg-emerald-600 px-3 py-1 text-xs font-semibold text-white hover:bg-emerald-700">Pay Now</a>
                                        @endif
                                        <a href="{{ route('student.finance.invoice.pdf', $inv->id) }}" class="rounded bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-200">PDF</a>
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
