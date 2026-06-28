@extends('layouts.app')

@php $cur = 'PHP'; @endphp

@section('content')
<div class="w-full space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-800">Payment History</h1>
        <p class="text-sm text-slate-500">Payments recorded on your account.</p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        @if($payments->isEmpty())
            <p class="text-sm text-slate-500">No payments recorded yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-xs uppercase text-slate-400">
                            <th class="px-3 py-2">Date</th>
                            <th class="px-3 py-2">Reference</th>
                            <th class="px-3 py-2">Method</th>
                            <th class="px-3 py-2">Type</th>
                            <th class="px-3 py-2 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payments as $p)
                            <tr class="border-b last:border-0">
                                <td class="px-3 py-2 text-slate-500">{{ optional($p->paid_at)->format('M d, Y') }}</td>
                                <td class="px-3 py-2 text-slate-500">{{ $p->reference_number }}</td>
                                <td class="px-3 py-2 text-slate-500">{{ ucfirst(str_replace('_', ' ', (string) $p->payment_method)) }}</td>
                                <td class="px-3 py-2 text-slate-500">{{ ucfirst((string) $p->payment_type) }}</td>
                                <td class="px-3 py-2 text-right text-emerald-600 font-semibold">{{ $cur }} {{ number_format((float) $p->amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
