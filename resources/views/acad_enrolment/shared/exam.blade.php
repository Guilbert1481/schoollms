@extends('layouts.enrollment')

@section('content')
<div class="px-8 py-12 max-w-3xl mx-auto">

    <div class="bg-white border border-slate-200 rounded-2xl p-8 shadow-lg">
        <h1 class="text-2xl font-extrabold text-slate-800 mb-2">
            {{ $examPurpose ?? 'Diagnostic Test' }}
        </h1>
        <p class="text-sm text-slate-500 mb-6">
            Reference #
            <span class="font-mono font-bold">ENR-{{ str_pad($enrollment->id, 6, '0', STR_PAD_LEFT) }}</span>
            &middot; {{ $enrollment->program?->name ?? '—' }}
        </p>

        @if(!empty($examInstructions))
        <div class="mb-6 rounded-xl border border-indigo-100 bg-indigo-50 p-4 text-sm text-indigo-900 whitespace-pre-line">
            {{ $examInstructions }}
        </div>
        @endif

        <div class="border border-dashed border-slate-300 rounded-xl p-8 text-center text-slate-500 text-sm">
            The online exam interface will appear here.
            <div class="text-xs mt-2">(Question bank &amp; timer to be wired in the next phase.)</div>
        </div>

        {{-- Temporary outcome capture so the workflow status can advance
             end-to-end while the real exam UI is being built. --}}
        <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-3">
            <form method="POST"
                  action="{{ route('public.apply.exam.submit', ['term' => $term->id, 'enrollment' => $enrollment->id]) }}">
                @csrf
                <input type="hidden" name="outcome" value="passed">
                <button type="submit"
                        class="w-full px-5 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-sm">
                    Submit as Passed
                </button>
            </form>
            <form method="POST"
                  action="{{ route('public.apply.exam.submit', ['term' => $term->id, 'enrollment' => $enrollment->id]) }}">
                @csrf
                <input type="hidden" name="outcome" value="failed">
                <button type="submit"
                        class="w-full px-5 py-2.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-sm">
                    Submit as Failed
                </button>
            </form>
        </div>

        <div class="mt-6 flex justify-between">
            <a href="{{ route('public.apply.confirmation', ['term' => $term->id, 'enrollment' => $enrollment->id]) }}"
               class="px-5 py-2.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-extrabold text-sm">
                ← Back
            </a>
            <a href="{{ route('student.dashboard') }}"
               class="px-5 py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-sm">
                Go to my Dashboard
            </a>
        </div>
    </div>
</div>
@endsection
