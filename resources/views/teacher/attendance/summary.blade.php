@extends('layouts.app')

@section('content')
<div class="w-full space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-800">Attendance</h1>
        <p class="text-sm text-slate-500">Historical attendance for the sections you advise or teach.</p>
    </div>

    @include('teacher.attendance._tabs', ['active' => 'summary'])

    @if (! $selected)
        <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">
            <p class="text-sm text-slate-500">
                You are not assigned as an adviser to any section and have no classes yet,
                so there is nothing to summarise.
            </p>
        </div>
    @else

        {{-- Filters --}}
        <form method="GET" action="{{ route('teacher.attendance.summary') }}"
            class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Academic year</label>
                    <select name="academic_year_id" onchange="this.form.submit()"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @foreach ($years as $y)
                            <option value="{{ $y->id }}" @selected($selected->academic_year_id === $y->id)>{{ $y->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Grade level</label>
                    <select name="year_level" onchange="this.form.submit()"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @foreach ($levels as $l)
                            <option value="{{ $l->value }}" @selected($selected->year_level === $l->value)>{{ $l->label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Section</label>
                    <select name="section_id" onchange="this.form.submit()"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @foreach ($sectionOptions as $s)
                            <option value="{{ $s->id }}" @selected((int) $selected->section->id === (int) $s->id)>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Subject</label>
                    <select name="source" onchange="this.form.submit()"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @foreach ($sources as $src)
                            <option value="{{ $src->value }}" @selected($selected->source === (string) $src->value)>{{ $src->label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Duration</label>
                    <select name="duration" onchange="this.form.submit()"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @foreach ($durations as $value => $label)
                            <option value="{{ $value }}" @selected($selected->duration === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <noscript>
                <button type="submit" class="mt-4 rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white">Apply</button>
            </noscript>
        </form>

        @if (! $summary['has_data'])
            <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">
                <p class="text-sm font-medium text-slate-700">No attendance recorded yet for this selection.</p>
                <p class="mt-1 text-sm text-slate-500">
                    Marks taken on the <a href="{{ route('teacher.attendance.index') }}" class="font-medium text-indigo-600 hover:underline">Daily</a>
                    tab will appear here.
                </p>
            </div>
        @else

            {{-- Section trend --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex flex-wrap items-baseline justify-between gap-2">
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-400">Section trend</h2>
                    <p class="text-sm text-slate-500">
                        {{ $summary['section_total']['marked'] }} {{ $summary['unit'] }} marked ·
                        overall <span class="font-semibold text-slate-800">{{ $summary['section_total']['rate'] }}%</span>
                    </p>
                </div>

                <div class="flex gap-3 overflow-x-auto pb-2">
                    @foreach ($summary['trend'] as $t)
                        <div class="flex-shrink-0 rounded-xl border border-slate-200 p-3" style="min-width: 7rem;">
                            <div class="text-xs font-medium text-slate-500">{{ $t['label'] }}</div>
                            <div class="mt-1 text-lg font-bold text-slate-800">
                                {{ $t['rate'] === null ? '—' : $t['rate'].'%' }}
                            </div>
                            <div class="mt-2 h-1.5 w-full rounded-full bg-slate-100">
                                <div class="h-1.5 rounded-full bg-emerald-500" style="width: {{ (float) ($t['rate'] ?? 0) }}%"></div>
                            </div>
                            <div class="mt-1 text-xs text-slate-400">{{ $t['marked'] }} marked</div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Per-student breakdown --}}
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="text-lg font-semibold text-slate-800">
                        {{ $selected->section->name }} · {{ $selected->section->level_label }}
                    </h2>
                    <p class="text-sm text-slate-500">
                        {{ $summary['window']['start']->format('M j, Y') }} – {{ $summary['window']['end']->format('M j, Y') }}
                        · rate is credit over {{ $summary['unit'] }} actually marked
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wider text-slate-400">
                                <th class="px-5 py-3">Student</th>
                                @foreach ($summary['buckets'] as $b)
                                    <th class="whitespace-nowrap px-3 py-3 text-center" title="{{ $b['range'] }}">{{ $b['label'] }}</th>
                                @endforeach
                                <th class="px-3 py-3 text-center">Present</th>
                                <th class="px-3 py-3 text-center">Late</th>
                                <th class="px-3 py-3 text-center">Absent</th>
                                <th class="px-3 py-3 text-center">Excused</th>
                                <th class="px-3 py-3 text-center">Half</th>
                                <th class="px-5 py-3 text-center">Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($summary['rows'] as $row)
                                <tr class="border-b border-slate-100">
                                    <td class="px-5 py-3">
                                        <div class="font-medium text-slate-800">{{ $row['name'] }}</div>
                                        <div class="text-xs text-slate-400">{{ $row['number'] }}</div>
                                    </td>

                                    @foreach ($summary['buckets'] as $b)
                                        @php($cell = $row['cells'][$b['key']])
                                        <td class="px-3 py-3 text-center">
                                            @if ($cell['rate'] === null)
                                                <span class="text-slate-300">—</span>
                                            @else
                                                <span class="font-medium {{ $cell['rate'] >= 95 ? 'text-emerald-600' : ($cell['rate'] >= 80 ? 'text-amber-600' : 'text-rose-600') }}">
                                                    {{ $cell['rate'] }}%
                                                </span>
                                            @endif
                                        </td>
                                    @endforeach

                                    <td class="px-3 py-3 text-center text-slate-700">{{ $row['total']['present'] }}</td>
                                    <td class="px-3 py-3 text-center text-slate-700">{{ $row['total']['late'] }}</td>
                                    <td class="px-3 py-3 text-center text-slate-700">{{ $row['total']['absent'] }}</td>
                                    <td class="px-3 py-3 text-center text-slate-700">{{ $row['total']['excused'] }}</td>
                                    <td class="px-3 py-3 text-center text-slate-700">{{ $row['total']['half_day'] }}</td>
                                    <td class="px-5 py-3 text-center">
                                        @if ($row['total']['rate'] === null)
                                            <span class="text-slate-300">—</span>
                                        @else
                                            <span class="font-semibold {{ $row['total']['rate'] >= 95 ? 'text-emerald-600' : ($row['total']['rate'] >= 80 ? 'text-amber-600' : 'text-rose-600') }}">
                                                {{ $row['total']['rate'] }}%
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <p class="border-t border-slate-200 px-5 py-3 text-xs text-slate-500">
                    Excused absences count as attended and do not lower the rate — the same policy the gradebook applies.
                </p>
            </div>
        @endif
    @endif
</div>
@endsection
