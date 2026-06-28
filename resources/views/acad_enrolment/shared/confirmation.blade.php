@extends('layouts.enrollment')

@section('content')
<div class="px-8 py-12 max-w-2xl text-center mx-auto">

    <div class="text-xs font-extrabold text-slate-500 tracking-widest mb-1">
        STEP 8 OF 8 — CONFIRMATION
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

{{-- ===========================================================
     Online Admission / Diagnostic Exam prompt
     Shown only when the school requires the exam for this enrollee type.
     =========================================================== --}}
@if(!empty($examRequired))
<div id="exam-prompt-modal"
     class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-7 text-center">
        <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600">
            <i data-lucide="clipboard-check" class="w-7 h-7"></i>
        </div>
        <h2 class="text-xl font-extrabold text-slate-800 mb-2">
            Ready to take the {{ $examPurpose ?? 'Diagnostic Test' }}?
        </h2>
        <p class="text-sm text-slate-500 mb-6">
            @if(!empty($examInstructions))
                {{ $examInstructions }}
            @else
                Your application has been received. You can take the online
                {{ strtolower($examPurpose ?? 'diagnostic test') }} now, or
                schedule it for later from your dashboard.
            @endif
        </p>

        <div class="flex gap-3 justify-center">
            <button type="button"
                    onclick="document.getElementById('exam-prompt-modal').remove();"
                    class="px-5 py-2.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-extrabold text-sm">
                Next time
            </button>
            <a href="{{ route('public.apply.exam', ['term' => $term->id, 'enrollment' => $enrollment->id]) }}"
               class="px-5 py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-sm">
                Start
            </a>
        </div>
    </div>
</div>
<script>
    // Re-render Lucide icons inside the freshly-injected modal (icons script
    // is loaded globally by the enrollment layout).
    if (window.lucide && typeof window.lucide.createIcons === 'function') {
        window.lucide.createIcons();
    }
</script>
@endif
@endsection
