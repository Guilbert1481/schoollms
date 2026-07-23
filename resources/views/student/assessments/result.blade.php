@extends('layouts.app')

@section('page-title', 'Result')

@section('content')
<div class="w-full space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-800">{{ $test->title }}</h1>
        <p class="text-sm text-slate-500">{{ $test->subject?->name ?? 'Assessment' }} · Result</p>
    </div>

    @if (session('success'))
        <div class="max-w-2xl rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-5">

        <div class="flex items-center gap-3 text-sm">
            <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 font-semibold text-emerald-700">
                {{ $attempt->isSubmitted() ? 'Submitted' : ucfirst($attempt->status) }}
            </span>
            @if ($attempt->submitted_at)
                <span class="text-slate-500">on {{ $attempt->submitted_at->format('M j, Y · g:i A') }}</span>
            @endif
            @if ($attempt->auto_submitted)
                <span class="text-amber-600">· auto-submitted (time up)</span>
            @endif
        </div>

        @if ($showScore)
            <div class="rounded-xl bg-sky-50 border border-sky-100 p-5 text-center">
                <div class="text-4xl font-extrabold text-sky-800 tabular-nums">{{ rtrim(rtrim($attempt->percentage, '0'), '.') }}%</div>
                <div class="mt-1 text-sm text-slate-600">
                    {{ rtrim(rtrim($attempt->raw_score, '0'), '.') }} / {{ rtrim(rtrim($attempt->max_score, '0'), '.') }} points
                </div>
            </div>
            @if ($attempt->needs_manual)
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    This score is <b>provisional</b> — it will be finalized once your teacher grades the written
                    (essay) items.
                </div>
            @endif

            {{-- Speed Dash run summary — game meters only; the academic score above
                 is the official one and is computed exactly like a standard quiz. --}}
            @if (is_array($attempt->meta['game'] ?? null))
                @php $g = $attempt->meta['game']; @endphp
                <div class="rounded-xl border border-cyan-200 bg-cyan-50 p-4">
                    <div class="mb-2 text-xs font-bold uppercase tracking-wide text-cyan-700">🎮 Quiz Speed Dash run</div>
                    <div class="grid grid-cols-2 gap-3 text-center sm:grid-cols-4">
                        <div>
                            <div class="text-xl font-extrabold text-cyan-800 tabular-nums">{{ (int) ($g['score'] ?? 0) }}</div>
                            <div class="text-xs text-slate-500">Game score</div>
                        </div>
                        <div>
                            <div class="text-xl font-extrabold text-cyan-800 tabular-nums">x{{ (int) ($g['best_streak'] ?? 0) }}</div>
                            <div class="text-xs text-slate-500">Best streak</div>
                        </div>
                        <div>
                            <div class="text-xl font-extrabold text-cyan-800 tabular-nums">{{ (int) ($g['correct'] ?? 0) }}</div>
                            <div class="text-xs text-slate-500">Correct</div>
                        </div>
                        <div>
                            <div class="text-xl font-extrabold text-cyan-800 tabular-nums">{{ (int) ($g['wrong'] ?? 0) }}</div>
                            <div class="text-xs text-slate-500">Missed</div>
                        </div>
                    </div>
                    <p class="mt-2 text-center text-xs text-slate-500">Game points never change your official grade.</p>
                </div>
            @endif
        @else
            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                Your answers were recorded. Your teacher will release the results.
            </div>
        @endif

        <a href="{{ route('student.dashboard') }}" class="inline-flex text-sm font-semibold text-sky-700 hover:underline">
            ← Back to dashboard
        </a>
    </div>
</div>
@endsection
