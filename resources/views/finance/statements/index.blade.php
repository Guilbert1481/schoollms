@extends('layouts.app')

@section('content')
<div class="w-full space-y-6">

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Statements of Account</h1>
            <p class="text-sm text-slate-500">
                Cadence: <span class="font-semibold text-slate-700">{{ $setting->frequencyLabel() }}</span>
                @if($setting->auto_generate_soa)
                    <span class="ml-1 rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-bold text-emerald-700">Auto-generation ON</span>
                @else
                    <span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-600">Auto-generation OFF</span>
                @endif
                @if($setting->last_soa_run_at)
                    &middot; last run {{ $setting->last_soa_run_at->format('M d, Y H:i') }}
                @endif
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('finance.settings.edit') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Frequency Settings</a>
            <form method="POST" action="{{ route('finance.statements.batch') }}"
                  onsubmit="return confirm('Generate statements for the current period for every student with ledger activity?');">
                @csrf
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Generate Current Period</button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
    @endif

    <x-table.table
        tableKey="finance_statements"
        :columns="$columns"
        :data="$rows"
        :actions="$actions"
        perPage="20"
    />
</div>
@endsection
