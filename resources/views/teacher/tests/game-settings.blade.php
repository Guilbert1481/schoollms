@extends('layouts.app')

@section('page-title', 'Play as Game')

@section('content')
<div class="w-full space-y-6">

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
    @endif

    <div class="flex items-center gap-2 text-xs text-slate-500">
        <a href="{{ route('teacher.tests.management') }}" class="hover:text-cyan-700">Test Management</a>
        <i data-lucide="chevron-right" class="h-3 w-3"></i>
        <span class="truncate">{{ $test->title }}</span>
        <i data-lucide="chevron-right" class="h-3 w-3"></i>
        <span>Play as Game</span>
    </div>

    <div>
        <h1 class="flex items-center gap-2 text-2xl font-bold text-slate-800">
            <i data-lucide="gamepad-2" class="h-7 w-7 text-cyan-600"></i>
            Quiz Speed Dash — {{ $test->title }}
        </h1>
        <p class="mt-1 text-sm text-slate-600">
            Deliver this test as an endless-runner game. Questions, points, schedule, attempt limits,
            and the official grade are exactly what the Test Builder says — the game only changes how
            students <em>experience</em> the same assessment.
        </p>
    </div>

    {{-- Eligibility --}}
    @if(! $isOnline)
        <div class="max-w-3xl rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            <span class="font-semibold">This is not an online test.</span>
            Speed Dash only delivers online tests — switch the test's mode to Online in the Test Builder first.
        </div>
    @elseif(! $eligibility['ok'])
        <div class="max-w-3xl rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            <div class="font-semibold">This test can't be played as Speed Dash yet:</div>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach(array_slice($eligibility['reasons'], 0, 8) as $reason)
                    <li>{{ $reason }}</li>
                @endforeach
                @if(count($eligibility['reasons']) > 8)
                    <li>…and {{ count($eligibility['reasons']) - 8 }} more.</li>
                @endif
            </ul>
            <p class="mt-2">Only <span class="font-semibold">True/False</span> and <span class="font-semibold">Multiple Choice</span>
            questions with 2–5 options can be played as answer gates.</p>
        </div>
    @else
        <div class="max-w-3xl rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
            <span class="font-semibold">Ready to play.</span> Every question fits the game (True/False or
            Multiple Choice, 2–5 options, one correct answer).
        </div>
    @endif

    <form method="POST" action="{{ route('teacher.tests.game.save', $test) }}" class="max-w-3xl space-y-6">
        @csrf

        {{-- Enable --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <label class="flex items-center justify-between gap-4">
                <span>
                    <span class="block text-sm font-bold text-slate-800">Enable Quiz Speed Dash</span>
                    <span class="block text-xs text-slate-500">Students will take this test as the runner game instead of the standard form.</span>
                </span>
                <span class="relative inline-block cursor-pointer">
                    <input type="hidden" name="enabled" value="0">
                    <input type="checkbox" name="enabled" value="1" class="peer sr-only"
                           @checked($enabled) @disabled(! $isOnline || ! $eligibility['ok'])>
                    <span class="block h-6 w-11 rounded-full bg-slate-300 transition peer-checked:bg-cyan-600"></span>
                    <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                </span>
            </label>
        </div>

        {{-- Core game settings --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-5">
            <h2 class="text-sm font-bold text-slate-800">Game settings</h2>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-400">Starting lives (hearts)</label>
                    <select name="starting_lives" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                        @foreach([1, 2, 3, 4, 5] as $n)
                            <option value="{{ $n }}" @selected((int) $settings['starting_lives'] === $n)>{{ $n }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-500">
                        Running out of hearts never ends a graded run — the game switches to Recovery Mode
                        (slower, no bonuses) and the student still finishes every question.
                    </p>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-400">Answer submission</label>
                    <select name="instant_submit" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                        <option value="1" @selected($settings['instant_submit'])>Instant — picking a gate submits</option>
                        <option value="0" @selected(! $settings['instant_submit'])>Confirm — pick, then press Enter / Confirm</option>
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Confirm mode lets students change their pick before it counts.</p>
                </div>
            </div>
        </div>

        {{-- Advanced (collapsed by default) --}}
        <details class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <summary class="cursor-pointer select-none px-5 py-4 text-sm font-bold text-slate-700">Advanced</summary>
            <div class="grid gap-5 border-t border-slate-100 p-5 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-400">Power-ups</label>
                    <select name="powerups_enabled" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                        <option value="1" @selected($settings['powerups_enabled'])>On — Speed Boost at 3-streak, Shield at 5-streak</option>
                        <option value="0" @selected(! $settings['powerups_enabled'])>Off</option>
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Power-ups affect the game score and visuals only — never the official grade.</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-400">Max speed bonus (game points)</label>
                    <input type="number" name="speed_bonus_max" min="0" max="50" value="{{ (int) $settings['speed_bonus_max'] }}"
                           class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                    <p class="mt-1 text-xs text-slate-500">Small bonus for fast correct answers (0 disables it). Accuracy always outweighs speed.</p>
                </div>
            </div>
        </details>

        {{-- What the game inherits (read-only) --}}
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 text-sm text-slate-600">
            <h2 class="mb-2 text-sm font-bold text-slate-700">Inherited from the Test Builder</h2>
            <ul class="grid gap-1 sm:grid-cols-2">
                <li>Questions: <span class="font-semibold">{{ $test->testQuestions()->count() }}</span></li>
                <li>Attempts allowed: <span class="font-semibold">{{ $test->settings->attempts_allowed ?? 1 }}</span></li>
                <li>Timer: <span class="font-semibold">{{ ($test->settings->duration_minutes ?? $test->settings->timer_minutes) ? (($test->settings->duration_minutes ?? $test->settings->timer_minutes).' min') : 'None' }}</span></li>
                <li>Shuffle questions: <span class="font-semibold">{{ ($test->settings->shuffle_questions ?? false) ? 'Yes' : 'No' }}</span></li>
                <li>Shuffle choices: <span class="font-semibold">{{ ($test->settings->shuffle_mcq_choices ?? false) ? 'Yes' : 'No' }}</span></li>
                <li>Show correct answers: <span class="font-semibold">{{ ($test->settings->show_correct_answers ?? false) ? 'Yes' : 'No' }}</span></li>
            </ul>
            <p class="mt-2 text-xs">Change these in the Test Builder — the game follows them automatically.</p>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit"
                    class="rounded-lg bg-cyan-600 px-5 py-2.5 text-sm font-bold text-white shadow hover:bg-cyan-700">
                Save game settings
            </button>
            <a href="{{ route('teacher.tests.management') }}"
               class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Back
            </a>
        </div>
    </form>
</div>
@endsection
