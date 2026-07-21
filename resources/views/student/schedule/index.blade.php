@extends('layouts.app')

@section('page-title', 'My Schedule')

@section('content')
<div class="w-full space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-800">My Schedule</h1>
        <p class="text-sm text-slate-500">Your weekly class timetable.</p>
    </div>

    @php
        $days  = $schedule['days'];
        $today = now()->format('l');
        // Show Mon–Sat always; include Sunday only when it actually has classes.
        $visible = collect($days)->filter(fn ($rows, $day) => $day !== 'Sunday' || count($rows) > 0);
    @endphp

    @if (! $schedule['has_any'])
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white/60 p-12 text-center text-slate-500">
            No classes are scheduled for you yet. Your weekly timetable appears here once your classes have set meeting times.
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($visible as $day => $rows)
                <div class="rounded-2xl border bg-white shadow-sm {{ $day === $today ? 'border-blue-300 ring-1 ring-blue-200' : 'border-slate-200' }}">
                    <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                        <h2 class="font-bold text-slate-800">{{ $day }}</h2>
                        @if ($day === $today)
                            <span class="rounded-full bg-blue-600 px-2 py-0.5 text-[10px] font-black text-white">Today</span>
                        @endif
                    </div>

                    @if (count($rows) === 0)
                        <p class="px-4 py-6 text-center text-sm text-slate-400">No classes</p>
                    @else
                        <ul class="divide-y divide-slate-100">
                            @foreach ($rows as $c)
                                <li class="flex items-start gap-3 px-4 py-3 {{ $c['now'] ? 'bg-blue-50' : '' }}">
                                    <div class="w-20 shrink-0 text-xs font-bold {{ $c['now'] ? 'text-blue-600' : 'text-slate-400' }}">
                                        {{ $c['time'] }}
                                        <div class="font-normal text-slate-400">{{ $c['end'] }}</div>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="truncate font-semibold text-slate-800">{{ $c['subject'] }}</div>
                                        <div class="text-xs text-slate-500">{{ $c['room'] }} · {{ $c['teacher'] }}</div>
                                    </div>
                                    @if ($c['now'])
                                        <span class="rounded-full bg-blue-600 px-2 py-0.5 text-[10px] font-black text-white">Now</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
