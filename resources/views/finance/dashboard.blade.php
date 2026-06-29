{{-- views/finance/dashboard.blade.php --}}

@extends('layouts.app')

@section('content')
<div class="w-full space-y-6">

    <div>
        <h1 class="text-2xl font-extrabold text-slate-800">Finance Dashboard</h1>
        <p class="text-sm text-slate-500">
            Overview of admissions billing, payments, and outstanding balances.
        </p>
    </div>

    {{-- KPI Section --}}
    @include('partials.dashboard.kpi-row')

    @include('partials.dashboard.stat-card', ['trendCharts' => $trendCharts])

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <a href="{{ route('finance.billing.index') }}"
           class="rounded-2xl border bg-white p-5 hover:shadow transition">
            <div class="text-xs font-bold uppercase tracking-widest text-slate-500">Billing</div>
            <div class="mt-1 text-lg font-extrabold text-slate-800">Statements &amp; Invoices</div>
            <p class="text-sm text-slate-500 mt-1">
                Issue and manage Statements of Account.
            </p>
        </a>
        <a href="{{ route('finance.payments.index') }}"
           class="rounded-2xl border bg-white p-5 hover:shadow transition">
            <div class="text-xs font-bold uppercase tracking-widest text-slate-500">Payments</div>
            <div class="mt-1 text-lg font-extrabold text-slate-800">Record &amp; Track</div>
            <p class="text-sm text-slate-500 mt-1">
                Capture tuition and fee collections.
            </p>
        </a>
        <a href="{{ route('finance.tuition-setup.index') }}"
           class="rounded-2xl border bg-white p-5 hover:shadow transition">
            <div class="text-xs font-bold uppercase tracking-widest text-slate-500">Setup</div>
            <div class="mt-1 text-lg font-extrabold text-slate-800">Tuition &amp; Discounts</div>
            <p class="text-sm text-slate-500 mt-1">
                Maintain fee rules and discount types.
            </p>
        </a>
    </div>

</div>
@endsection
