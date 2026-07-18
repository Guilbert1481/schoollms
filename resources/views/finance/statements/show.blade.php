@extends('layouts.app')

@php
    $cur = 'PHP';
    $student = $statement->student;
    $studentName = $student ? trim($student->first_name.' '.$student->last_name) : '—';
    $items = $statement->line_items ?? [];

    // Grade/Year level · Section · Academic Year (shared with the invoice).
    $soaStudent = \App\Models\Student::where('user_id', $statement->student_id)->first();
    $soaEnr = $statement->student_enrollment_id
        ? \App\Models\StudentEnrollment::find($statement->student_enrollment_id)
        : ($soaStudent ? \App\Models\StudentEnrollment::where('student_id', $soaStudent->id)->latest('id')->first() : null);
    $soaCtx = $soaEnr?->financeContext();
    $soaContextLabel = $soaCtx ? implode(' · ', array_filter([
        $soaCtx['level'] ?? null,
        ($soaCtx['section'] ?? null) ? 'Section '.$soaCtx['section'] : null,
        ($soaCtx['academic_year'] ?? null) ? 'SY '.$soaCtx['academic_year'] : null,
    ])) : '';
@endphp

@section('content')
<div class="w-full max-w-4xl space-y-6">

    <a href="{{ route('finance.statements.index') }}" class="inline-flex items-center text-sm text-slate-500 hover:text-indigo-600">&larr; Back to statements</a>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 pb-4">
            <div>
                <h1 class="text-xl font-bold text-slate-800">Statement {{ $statement->soa_number }}</h1>
                <p class="text-sm text-slate-500">{{ $studentName }}</p>
                @if($soaContextLabel)
                    <p class="text-xs font-semibold text-slate-500">{{ $soaContextLabel }}</p>
                @endif
                <p class="text-xs text-slate-400">Period: {{ $statement->period_label }}</p>
            </div>
            <div class="text-right text-sm text-slate-500">
                <div>Generated: {{ optional($statement->generated_at)->format('M d, Y') ?? '—' }}</div>
                <div>Due: {{ optional($statement->due_date)->format('M d, Y') ?? '—' }}</div>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-lg bg-slate-50 p-3"><div class="text-xs text-slate-400">Opening</div><div class="font-bold text-slate-700">{{ $cur }} {{ number_format((float) $statement->opening_balance, 2) }}</div></div>
            <div class="rounded-lg bg-rose-50 p-3"><div class="text-xs text-slate-400">Charges</div><div class="font-bold text-rose-700">{{ $cur }} {{ number_format((float) $statement->total_charges, 2) }}</div></div>
            <div class="rounded-lg bg-emerald-50 p-3"><div class="text-xs text-slate-400">Payments</div><div class="font-bold text-emerald-700">{{ $cur }} {{ number_format((float) $statement->total_credits, 2) }}</div></div>
            <div class="rounded-lg bg-indigo-50 p-3"><div class="text-xs text-slate-400">Closing</div><div class="font-bold text-indigo-700">{{ $cur }} {{ number_format((float) $statement->closing_balance, 2) }}</div></div>
        </div>

        <div class="overflow-x-auto">
        <table class="mt-5 w-full text-sm">
            <thead>
                <tr class="border-b text-left text-xs uppercase text-slate-400">
                    <th class="px-3 py-2">Date</th>
                    <th class="px-3 py-2">Description</th>
                    <th class="px-3 py-2">Reference</th>
                    <th class="px-3 py-2 text-right">Charge</th>
                    <th class="px-3 py-2 text-right">Payment</th>
                    <th class="px-3 py-2 text-right">Balance</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    <tr class="border-b last:border-0">
                        <td class="px-3 py-2 text-slate-500">{{ $item['date'] ? \Carbon\Carbon::parse($item['date'])->format('M d, Y') : '' }}</td>
                        <td class="px-3 py-2 text-slate-700">{{ $item['description'] ?? '' }}</td>
                        <td class="px-3 py-2 text-slate-400">{{ $item['reference'] ?? '—' }}</td>
                        <td class="px-3 py-2 text-right text-rose-600">{{ ($item['debit'] ?? 0) > 0 ? $cur.' '.number_format((float) $item['debit'], 2) : '' }}</td>
                        <td class="px-3 py-2 text-right text-emerald-600">{{ ($item['credit'] ?? 0) > 0 ? $cur.' '.number_format((float) $item['credit'], 2) : '' }}</td>
                        <td class="px-3 py-2 text-right font-semibold">{{ $cur }} {{ number_format((float) ($item['balance_after'] ?? 0), 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-3 py-4 text-center text-slate-400">No transactions in this period (carry-over balance only).</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>

        <div class="mt-4 flex gap-2">
            <a href="{{ route('finance.statements.pdf', $statement->id) }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Download PDF</a>
        </div>
    </div>
</div>
@endsection
