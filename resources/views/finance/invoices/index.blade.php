@extends('layouts.app')

@section('content')
<div class="w-full space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-800">Invoices</h1>
        <p class="text-sm text-slate-500">Assessed charges issued to students. Generate invoices from a student's ledger.</p>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
    @endif

    <div class="flex flex-wrap gap-2">
        @foreach($statuses as $key => $label)
            <a href="{{ route('finance.invoices.index', ['status' => $key]) }}"
               class="rounded-full px-3 py-1 text-sm font-semibold {{ $status === $key ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <x-table.table
        tableKey="finance_invoices"
        :columns="$columns"
        :data="$rows"
        :actions="$actions"
        perPage="20"
    />
</div>
@endsection
