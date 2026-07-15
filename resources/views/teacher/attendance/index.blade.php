@extends('layouts.app')

@section('content')
<div class="w-full space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Attendance</h1>
            <p class="text-sm text-slate-500">Take daily homeroom attendance for an advisory section, or per-subject attendance for a class.</p>
        </div>
    </div>

    @if (session('status'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    {{-- Pickers --}}
    <div class="grid gap-4 md:grid-cols-2">
        {{-- Advisory (daily) --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wider text-slate-400">Advisory &middot; Daily</h2>
            @if ($sections->isEmpty())
                <p class="text-sm text-slate-500">You are not assigned as an adviser to any section.</p>
            @else
                <form method="GET" action="{{ route('teacher.attendance.index') }}" class="space-y-3">
                    <input type="hidden" name="type" value="daily">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Section</label>
                        <select name="section_id" required
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option value="">Select a section…</option>
                            @foreach ($sections as $s)
                                <option value="{{ $s->id }}"
                                    @selected(($context['type'] ?? null) === 'daily' && ($context['section']->id ?? null) === $s->id)>
                                    {{ $s->name }}@if ($s->year_level) &middot; Grade {{ $s->year_level }}@endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Date</label>
                        <input type="date" name="date" value="{{ $date }}"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <button type="submit"
                        class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
                        Load roster
                    </button>
                </form>
            @endif
        </div>

        {{-- Class (session) --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wider text-slate-400">Class &middot; Per subject</h2>
            @if ($classes->isEmpty())
                <p class="text-sm text-slate-500">You have no classes assigned this term.</p>
            @else
                <form method="GET" action="{{ route('teacher.attendance.index') }}" class="space-y-3">
                    <input type="hidden" name="type" value="session">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Class</label>
                        <select name="class_id" required
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option value="">Select a class…</option>
                            @foreach ($classes as $c)
                                <option value="{{ $c->id }}"
                                    @selected(($context['type'] ?? null) === 'session' && ($context['class']->id ?? null) === $c->id)>
                                    {{ $c->subject->name ?? $c->code }}@if ($c->section) &middot; {{ $c->section->name }}@endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Date</label>
                        <input type="date" name="date" value="{{ $date }}"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <button type="submit"
                        class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
                        Load roster
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Roster --}}
    @if ($context)
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-800">
                        @if ($context['type'] === 'daily')
                            {{ $context['section']->name }} — Homeroom
                        @else
                            {{ $context['class']->subject->name ?? $context['class']->code }}
                            @if ($context['class']->section) — {{ $context['class']->section->name }} @endif
                        @endif
                    </h2>
                    <p class="text-sm text-slate-500">{{ \Illuminate\Support\Carbon::parse($date)->format('l, F j, Y') }}</p>
                </div>
                <span class="text-sm text-slate-500">{{ $roster->count() }} student(s)</span>
            </div>

            @if ($roster->isEmpty())
                <p class="px-5 py-8 text-center text-sm text-slate-500">No enrolled students on this roster.</p>
            @else
                <form method="POST" action="{{ route('teacher.attendance.store') }}">
                    @csrf
                    <input type="hidden" name="type" value="{{ $context['type'] }}">
                    @if ($context['type'] === 'daily')
                        <input type="hidden" name="section_id" value="{{ $context['section']->id }}">
                    @else
                        <input type="hidden" name="class_id" value="{{ $context['class']->id }}">
                    @endif
                    <input type="hidden" name="date" value="{{ $date }}">

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wider text-slate-400">
                                    <th class="px-5 py-3">#</th>
                                    <th class="px-5 py-3">Student</th>
                                    <th class="px-5 py-3">Status</th>
                                    <th class="px-5 py-3">Time in</th>
                                    <th class="px-5 py-3">Time out</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($roster as $i => $row)
                                    <tr class="border-b border-slate-100">
                                        <td class="px-5 py-3 text-slate-400">{{ $i + 1 }}</td>
                                        <td class="px-5 py-3">
                                            <div class="font-medium text-slate-800">{{ $row['name'] }}</div>
                                            <div class="text-xs text-slate-400">{{ $row['number'] }}</div>
                                            <input type="hidden" name="marks[{{ $i }}][student_id]" value="{{ $row['student_id'] }}">
                                        </td>
                                        <td class="px-5 py-3">
                                            <select name="marks[{{ $i }}][status]"
                                                class="rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
                                                @foreach ($statuses as $status)
                                                    <option value="{{ $status }}"
                                                        @selected(($row['status'] ?? 'present') === $status)>
                                                        {{ ucwords(str_replace('_', ' ', $status)) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="px-5 py-3">
                                            <input type="time" name="marks[{{ $i }}][time_in]"
                                                value="{{ $row['time_in'] ? \Illuminate\Support\Str::substr($row['time_in'], 0, 5) : '' }}"
                                                class="rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
                                        </td>
                                        <td class="px-5 py-3">
                                            <input type="time" name="marks[{{ $i }}][time_out]"
                                                value="{{ $row['time_out'] ? \Illuminate\Support\Str::substr($row['time_out'], 0, 5) : '' }}"
                                                class="rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="flex justify-end border-t border-slate-200 px-5 py-4">
                        <button type="submit"
                            class="rounded-lg bg-emerald-600 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-500">
                            Save attendance
                        </button>
                    </div>
                </form>
            @endif
        </div>
    @endif
</div>
@endsection
