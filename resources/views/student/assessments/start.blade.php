@extends('layouts.app')

@section('page-title', 'Start Test')

@section('content')
<div class="w-full space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-800">{{ $test->title }}</h1>
        <p class="text-sm text-slate-500">{{ $test->subject?->name ?? 'Assessment' }} · Online test</p>
    </div>

    <div class="max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-5">

        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
            <div>
                <div class="text-xs uppercase tracking-wide text-slate-400">Questions</div>
                <div class="text-lg font-bold text-slate-800">{{ $itemCount }}</div>
            </div>
            <div>
                <div class="text-xs uppercase tracking-wide text-slate-400">Time limit</div>
                <div class="text-lg font-bold text-slate-800">
                    {{ $durationMinutes ? $durationMinutes.' min' : 'None' }}
                </div>
            </div>
            <div>
                <div class="text-xs uppercase tracking-wide text-slate-400">Attempts left</div>
                <div class="text-lg font-bold text-slate-800">{{ $attemptsLeft }}</div>
            </div>
        </div>

        @if ($isGame ?? false)
            <div class="rounded-xl border border-cyan-200 bg-cyan-50 p-4 text-sm text-cyan-900 leading-relaxed">
                <b>🎮 Quiz Speed Dash!</b> Your teacher set this test to play as a runner game — answer by
                choosing gates (tap, click, or keys A–E / 1–5). Hearts, streaks, and game points are just for
                fun: your <b>official score counts correct answers only</b>, same as a normal quiz.
            </div>
        @endif

        <div class="rounded-xl bg-sky-50 border border-sky-100 p-4 text-sm text-sky-900 leading-relaxed">
            <b>Before you start:</b> the timer runs on the server and keeps going even if you close the tab —
            your answers save as you go, so you can resume where you left off. When you're done, press
            <b>Submit Test</b>. If the time runs out, the test submits automatically.
        </div>

        @if ($status === 'upcoming')
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                This test opens on <b>{{ $opensAt?->format('M j, Y · g:i A') }}</b>. Come back then.
            </div>
        @elseif ($status === 'closed')
            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                This test closed on <b>{{ $closesAt?->format('M j, Y · g:i A') }}</b>.
                <a href="{{ route('student.assessments.result', $test) }}" class="font-semibold text-sky-700 hover:underline">View your result</a>.
            </div>
        @elseif ($attemptsLeft <= 0 && ! $hasActive)
            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                You've used all your attempts.
                <a href="{{ route('student.assessments.result', $test) }}" class="font-semibold text-sky-700 hover:underline">View your result</a>.
            </div>
        @else
            <form method="POST" action="{{ route('student.assessments.begin', $test) }}">
                @csrf
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-6 py-3 font-bold text-white shadow-md hover:bg-sky-700">
                    {{ $hasActive ? 'Resume Test' : 'Start Test' }}
                </button>
                @if ($closesAt)
                    <span class="ml-3 text-xs text-slate-400">Closes {{ $closesAt->format('M j, g:i A') }}</span>
                @endif
            </form>
        @endif
    </div>
</div>
@endsection
