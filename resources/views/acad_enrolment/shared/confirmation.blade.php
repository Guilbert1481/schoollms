@extends('layouts.enrollment')

@section('content')
<div class="px-8 py-12 max-w-2xl text-center mx-auto">

    <div class="text-xs font-extrabold text-slate-500 tracking-widest mb-1">
        STEP 7 OF 7 — CONFIRMATION
    </div>
    <div class="h-2 w-full bg-slate-200 rounded-full overflow-hidden mb-8">
        <div class="h-full bg-emerald-500" style="width:100%"></div>
    </div>

    <div class="bg-white border border-emerald-200 rounded-2xl p-8 shadow-lg">
        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 text-3xl font-extrabold">
            ✓
        </div>
        <h1 class="text-2xl font-extrabold text-slate-800 mb-2">Application Submitted!</h1>
        <p class="text-sm text-slate-500 mb-6">
            Thanks — we've received your application. You'll get an email update once
            it's reviewed by the registrar.
        </p>

        <div class="text-left text-sm bg-slate-50 border border-slate-200 rounded-xl p-4 mb-6 space-y-1.5">
            <div class="flex justify-between"><span class="text-slate-500">Reference #</span>
                <span class="font-mono font-bold">ENR-{{ str_pad($enrollment->id, 6, '0', STR_PAD_LEFT) }}</span></div>
            <div class="flex justify-between"><span class="text-slate-500">Term</span>
                <span class="font-bold">{{ $term->name ?? $term->code ?? '—' }}</span></div>
            <div class="flex justify-between"><span class="text-slate-500">Programme</span>
                <span class="font-bold">{{ $enrollment->program?->name ?? '—' }}</span></div>
            <div class="flex justify-between"><span class="text-slate-500">Year Level</span>
                <span class="font-bold">{{ $enrollment->year_level ?? '—' }}</span></div>
            <div class="flex justify-between"><span class="text-slate-500">Modality</span>
                <span class="font-bold">{{ $enrollment->modality?->name ?? '—' }}</span></div>
            <div class="flex justify-between"><span class="text-slate-500">Status</span>
                <span class="font-bold uppercase text-amber-600">{{ $enrollment->status }}</span></div>
        </div>

        <a href="{{ route('student.dashboard') }}"
           class="inline-block px-7 py-3 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold shadow">
            Go to my Dashboard →
        </a>
    </div>
</div>
@endsection
