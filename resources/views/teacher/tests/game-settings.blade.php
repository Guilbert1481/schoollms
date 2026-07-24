@extends('layouts.app')

@section('page-title', 'Play as Game')

@section('content')
<style>[x-cloak]{display:none!important}</style>
<div class="w-full space-y-6" x-data="{ mode: @js($playMode) }">

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
            Play as Game — {{ $test->title }}
        </h1>
        <p class="mt-1 text-sm text-slate-600">
            Deliver this test as a game. Questions, points, schedule, attempt limits, and the official
            grade are exactly what the Test Builder says — the game only changes how students
            <em>experience</em> the same assessment.
        </p>
    </div>

    {{-- Eligibility --}}
    @if(! $isOnline)
        <div class="max-w-3xl rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            <span class="font-semibold">This is not an online test.</span>
            Games only deliver online tests — switch the test's mode to Online in the Test Builder first.
        </div>
    @elseif(! $eligibility['ok'])
        <div class="max-w-3xl rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            <div class="font-semibold">This test can't be played as a game yet:</div>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach(array_slice($eligibility['reasons'], 0, 8) as $reason)
                    <li>{{ $reason }}</li>
                @endforeach
                @if(count($eligibility['reasons']) > 8)
                    <li>…and {{ count($eligibility['reasons']) - 8 }} more.</li>
                @endif
            </ul>
            <p class="mt-2">Only <span class="font-semibold">True/False</span> and <span class="font-semibold">Multiple Choice</span>
            questions with 2–5 options can be played in a game.</p>
        </div>
    @else
        <div class="max-w-3xl rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
            <span class="font-semibold">Ready to play.</span> Every question fits a game (True/False or
            Multiple Choice, 2–5 options, one correct answer).
        </div>
    @endif

    <form method="POST" action="{{ route('teacher.tests.game.save', $test) }}" class="max-w-3xl space-y-6">
        @csrf

        {{-- Delivery mode --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-3 text-sm font-bold text-slate-800">How should students take this test?</h2>
            <div class="grid gap-3 sm:grid-cols-3">
                @foreach([
                    'standard' => ['file-text', 'Standard quiz', 'The regular online test screen.'],
                    'speed_dash' => ['zap', 'Quiz Speed Dash', 'Endless-runner: answer gates to keep running.'],
                    'snake_and_ladder' => ['dice-5', 'Quiz Snakes & Ladders', 'Answer to roll the dice and race to tile 100.'],
                ] as $value => [$icon, $label, $blurb])
                    <label class="cursor-pointer rounded-xl border p-4 transition"
                           :class="mode === '{{ $value }}' ? 'border-cyan-500 bg-cyan-50 ring-2 ring-cyan-200' : 'border-slate-200 hover:border-slate-300'">
                        <input type="radio" name="play_mode" value="{{ $value }}" x-model="mode" class="sr-only"
                               @disabled($value !== 'standard' && (! $isOnline || ! $eligibility['ok']))>
                        <span class="flex items-center gap-2 text-sm font-bold text-slate-800">
                            <i data-lucide="{{ $icon }}" class="h-4 w-4 text-cyan-600"></i>{{ $label }}
                        </span>
                        <span class="mt-1 block text-xs text-slate-500">{{ $blurb }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Snakes & Ladders settings --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-5" x-show="mode === 'snake_and_ladder'" x-cloak>
            <h2 class="text-sm font-bold text-slate-800">Snakes &amp; Ladders settings</h2>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-400">Movement</label>
                    <select name="movement_policy" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                        <option value="classic" @selected($boardSettings['movement_policy'] === 'classic')>Classic Dice — correct answer rolls 1–6</option>
                        <option value="knowledge" @selected($boardSettings['movement_policy'] === 'knowledge')>Knowledge Dice — harder questions roll higher (average 1–3, advanced 4–6)</option>
                        <option value="accuracy" @selected($boardSettings['movement_policy'] === 'accuracy')>Accuracy — every correct answer moves 4 tiles (+1 on a 3-streak)</option>
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Wrong answers never move. Dice luck never touches the official grade.</p>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-400">Reaching tile 100</label>
                    <select name="finish_rule" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                        <option value="exact" @selected($boardSettings['finish_rule'] === 'exact')>Exact roll required — overshooting stays put</option>
                        <option value="bounce" @selected($boardSettings['finish_rule'] === 'bounce')>Bounce back — extra pips bounce off 100</option>
                        <option value="cap" @selected($boardSettings['finish_rule'] === 'cap')>Cap at 100 — any overshoot finishes</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-400">Board layout</label>
                    <select name="board_layout" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                        <option value="default" @selected($boardSettings['board_layout'] === 'default')>Classic board — the curated snake/ladder spread</option>
                        <option value="random" @selected($boardSettings['board_layout'] === 'random')>Surprise board — randomized per attempt (validated + reproducible)</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-400">Snake Shield</label>
                    <select name="shield_enabled" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                        <option value="1" @selected($boardSettings['shield_enabled'])>On — every 3-correct streak earns a shield that blocks one snake</option>
                        <option value="0" @selected(! $boardSettings['shield_enabled'])>Off</option>
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Shields affect the board only — never the official grade.</p>
                </div>
            </div>

            <p class="rounded-lg bg-slate-50 p-3 text-xs text-slate-500">
                Reaching tile 100 early never skips questions: the board is marked complete and the game
                keeps asking until every question is answered. Finishing the questions without reaching
                tile 100 also completes the test — the final board position is just for fun.
            </p>
        </div>

        {{-- Speed Dash settings --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-5" x-show="mode === 'speed_dash'" x-cloak>
            <h2 class="text-sm font-bold text-slate-800">Speed Dash settings</h2>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-400">Starting lives (hearts)</label>
                    <select name="starting_lives" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                        @foreach([1, 2, 3, 4, 5] as $n)
                            <option value="{{ $n }}" @selected((int) $dashSettings['starting_lives'] === $n)>{{ $n }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-500">
                        Running out of hearts never ends a graded run — Recovery Mode slows the game and
                        the student still finishes every question.
                    </p>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-400">Answer submission</label>
                    <select name="instant_submit" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                        <option value="1" @selected($dashSettings['instant_submit'])>Instant — picking a gate submits</option>
                        <option value="0" @selected(! $dashSettings['instant_submit'])>Confirm — pick, then press Enter / Confirm</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-400">Power-ups</label>
                    <select name="powerups_enabled" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                        <option value="1" @selected($dashSettings['powerups_enabled'])>On — Speed Boost at 3-streak, Shield at 5-streak</option>
                        <option value="0" @selected(! $dashSettings['powerups_enabled'])>Off</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-400">Max speed bonus (game points)</label>
                    <input type="number" name="speed_bonus_max" min="0" max="50" value="{{ (int) $dashSettings['speed_bonus_max'] }}"
                           class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                    <p class="mt-1 text-xs text-slate-500">Small bonus for fast correct answers (0 disables it). Accuracy always outweighs speed.</p>
                </div>
            </div>
        </div>

        {{-- Hidden defaults so the inactive mode's fields always validate --}}
        <template x-if="mode !== 'speed_dash'">
            <span>
                <input type="hidden" name="starting_lives" value="{{ (int) $dashSettings['starting_lives'] }}">
                <input type="hidden" name="instant_submit" value="{{ $dashSettings['instant_submit'] ? 1 : 0 }}">
                <input type="hidden" name="powerups_enabled" value="{{ $dashSettings['powerups_enabled'] ? 1 : 0 }}">
                <input type="hidden" name="speed_bonus_max" value="{{ (int) $dashSettings['speed_bonus_max'] }}">
            </span>
        </template>
        <template x-if="mode !== 'snake_and_ladder'">
            <span>
                <input type="hidden" name="movement_policy" value="{{ $boardSettings['movement_policy'] }}">
                <input type="hidden" name="finish_rule" value="{{ $boardSettings['finish_rule'] }}">
                <input type="hidden" name="board_layout" value="{{ $boardSettings['board_layout'] }}">
                <input type="hidden" name="shield_enabled" value="{{ $boardSettings['shield_enabled'] ? 1 : 0 }}">
            </span>
        </template>

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
