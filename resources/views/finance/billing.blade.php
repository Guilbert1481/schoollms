@extends('layouts.app')

@section('content')
<div class="w-full space-y-6">

    <h1 class="text-2xl font-bold text-slate-800">Billing</h1>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
    @endif

    <div x-data="{ tab: 'invoices' }">
        {{-- Tabs --}}
        <nav class="flex gap-6 border-b border-slate-200 text-sm">
            @foreach(['invoices' => 'Invoices', 'billing_run' => 'Billing Run'] as $key => $label)
                <button type="button" @click="tab = '{{ $key }}'"
                        :class="tab === '{{ $key }}' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="-mb-px border-b-2 px-1 py-3 font-semibold">{{ $label }}</button>
            @endforeach
        </nav>

        {{-- Invoices tab --}}
        <div x-show="tab === 'invoices'" class="pt-5">
            @include('finance.invoices._list', ['filterRoute' => 'finance.billing.index'])
        </div>

        {{-- Billing Run tab --}}
        <div x-show="tab === 'billing_run'" x-cloak class="pt-5">
            <div class="rounded-2xl border border-slate-200 bg-white p-6">
                <h2 class="text-sm font-bold text-slate-800">Billing Run</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Billing-run scaffold is ready — generate schedules and outstanding-balance runs here next.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide?.createIcons) window.lucide.createIcons();
    });
</script>
@endsection
