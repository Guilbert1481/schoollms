@extends('layouts.app')

@section('page-title', 'Assessments')

@section('content')
<div class="w-full space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-800">Assessments</h1>
        <p class="text-sm text-slate-500">Your online tests across all your classes.</p>
    </div>

    @php
        $groups = [
            ['key' => 'open',      'label' => 'Open now',  'tone' => 'sky',    'empty' => 'Nothing to take right now.'],
            ['key' => 'upcoming',  'label' => 'Upcoming',  'tone' => 'amber',  'empty' => 'No upcoming tests.'],
            ['key' => 'submitted', 'label' => 'Submitted', 'tone' => 'emerald','empty' => 'Nothing submitted yet.'],
            ['key' => 'missed',    'label' => 'Missed',    'tone' => 'rose',    'empty' => null],
        ];
        $tone = fn ($t) => [
            'sky'     => 'border-sky-200 bg-sky-50 text-sky-700',
            'amber'   => 'border-amber-200 bg-amber-50 text-amber-700',
            'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            'rose'    => 'border-rose-200 bg-rose-50 text-rose-700',
        ][$t];
    @endphp

    @php $anything = collect($buckets)->flatten(1)->isNotEmpty(); @endphp

    @unless ($anything)
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white/60 p-12 text-center text-slate-500">
            You have no online tests yet. When a teacher publishes one for your class, it appears here.
        </div>
    @endunless

    @foreach ($groups as $g)
        @php $items = $buckets[$g['key']] ?? []; @endphp
        @continue (count($items) === 0 && ($g['key'] === 'missed' || ! $anything))

        <section class="space-y-3">
            <div class="flex items-center gap-2">
                <h2 class="text-sm font-bold uppercase tracking-wide text-slate-500">{{ $g['label'] }}</h2>
                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold {{ $tone($g['tone']) }}">{{ count($items) }}</span>
            </div>

            @if (count($items) === 0)
                <p class="text-sm text-slate-400">{{ $g['empty'] }}</p>
            @else
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($items as $row)
                        @php $test = $row['test']; @endphp
                        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="font-semibold text-slate-800">{{ $test->title }}</div>
                                    <div class="text-xs text-slate-500">{{ $test->subject?->name ?? 'Assessment' }}</div>
                                </div>
                                @if ($test->settings?->duration_minutes ?? $test->settings?->timer_minutes)
                                    <span class="whitespace-nowrap text-xs text-slate-400">{{ $test->settings->duration_minutes ?? $test->settings->timer_minutes }} min</span>
                                @endif
                            </div>

                            <div class="mt-3 flex items-center justify-between gap-2">
                                @if ($g['key'] === 'open')
                                    <span class="text-xs text-slate-500">
                                        @if ($row['closesAt']) Closes {{ $row['closesAt']->format('M j, g:i A') }} @else Open @endif
                                    </span>
                                    <a href="{{ route('student.assessments.start', $test) }}"
                                       class="inline-flex rounded-lg bg-sky-600 px-4 py-2 text-sm font-bold text-white hover:bg-sky-700">
                                        {{ $row['hasActive'] ? 'Resume' : 'Take test' }}
                                    </a>
                                @elseif ($g['key'] === 'upcoming')
                                    <span class="text-xs text-amber-700">Opens {{ $row['opensAt']?->format('M j, Y · g:i A') }}</span>
                                    <span class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-400">Not open yet</span>
                                @elseif ($g['key'] === 'submitted')
                                    <span class="text-xs text-slate-500">
                                        @if ($row['attempt']?->needs_manual) Being graded @else Done @endif
                                    </span>
                                    <a href="{{ route('student.assessments.result', $test) }}"
                                       class="inline-flex rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                        View result
                                    </a>
                                @else
                                    <span class="text-xs text-rose-600">Closed {{ $row['closesAt']?->format('M j, g:i A') }}</span>
                                    <span class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-400">Missed</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    @endforeach
</div>
@endsection
