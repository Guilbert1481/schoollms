@extends('layouts.app')

@section('content')
<div class="w-full space-y-6">

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Student Ledgers</h1>
            <p class="text-sm text-slate-500">Each student's individual account — charges, payments, and running balance.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                {{ $studentCount }} students
            </span>
            <span class="rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-700">
                {{ $studentsWithBalance }} with balance
            </span>
            <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700">
                Outstanding: PHP {{ number_format($totalOutstanding, 2) }}
            </span>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
    @endif

    <x-table.table
        tableKey="finance_ledgers"
        :columns="$columns"
        :data="$rows"
        :actions="$actions"
        perPage="15"
    />
</div>
@endsection
