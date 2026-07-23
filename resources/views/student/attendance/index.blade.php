@extends('layouts.app')

@section('page-title', 'My Attendance')

@section('content')
<div class="w-full space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-800">My Attendance</h1>
        <p class="text-sm text-slate-500">Your attendance records for {{ $month->format('F Y') }}.</p>
    </div>

    @php
        $badge = [
            'present'  => ['label' => 'Present',  'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
            'late'     => ['label' => 'Late',     'class' => 'bg-amber-50 text-amber-700 ring-amber-200'],
            'absent'   => ['label' => 'Absent',   'class' => 'bg-rose-50 text-rose-700 ring-rose-200'],
            'excused'  => ['label' => 'Excused',  'class' => 'bg-sky-50 text-sky-700 ring-sky-200'],
            'half_day' => ['label' => 'Half Day', 'class' => 'bg-slate-50 text-slate-600 ring-slate-200'],
        ];
    @endphp

    {{-- Month summary --}}
    <div class="flex flex-wrap items-center gap-3">
        <span class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm shadow-sm">
            <span class="font-black text-slate-800">{{ $rate !== null ? $rate.'%' : '—' }}</span>
            <span class="text-slate-500">attendance rate</span>
        </span>
        @foreach ($badge as $key => $b)
            @if (($counts[$key] ?? 0) > 0)
                <span class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm shadow-sm">
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $b['class'] }}">{{ $b['label'] }}</span>
                    <span class="font-bold text-slate-700">{{ $counts[$key] }}</span>
                </span>
            @endif
        @endforeach
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('student.attendance.index') }}"
          class="flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div>
            <label class="mb-1 block text-xs font-semibold text-slate-500">Month</label>
            <input type="month" name="month" value="{{ $month->format('Y-m') }}" onchange="this.form.submit()"
                   class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold text-slate-500">Status</label>
            <select name="status" onchange="this.form.submit()"
                    class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">All statuses</option>
                @foreach ($badge as $key => $b)
                    <option value="{{ $key }}" @selected($status === $key)>{{ $b['label'] }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit"
                class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white hover:bg-indigo-700">Filter</button>
        @if ($status || request('month'))
            <a href="{{ route('student.attendance.index') }}"
               class="py-2 text-sm font-bold text-slate-400 hover:text-slate-600">Reset</a>
        @endif
    </form>

    {{-- Records --}}
    @if ($records->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white/60 p-12 text-center text-slate-500">
            No attendance records for {{ $month->format('F Y') }}{{ $status ? ' with that status' : '' }}.
        </div>
    @else
        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-[720px] w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Class</th>
                        <th class="px-4 py-3">Time In</th>
                        <th class="px-4 py-3">Time Out</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Remarks</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($records as $rec)
                        <tr>
                            <td class="px-4 py-3 align-top">
                                <div class="font-semibold text-slate-800">{{ \Illuminate\Support\Carbon::parse($rec->attendance_date)->format('M j, Y') }}</div>
                                <div class="text-xs text-slate-400">{{ \Illuminate\Support\Carbon::parse($rec->attendance_date)->format('l') }}</div>
                            </td>
                            <td class="px-4 py-3 align-top text-slate-700">
                                @if ($rec->class && $rec->class->subject)
                                    {{ $rec->class->subject->name }}
                                @elseif ($rec->section)
                                    Homeroom — {{ $rec->section->name }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top text-slate-600">
                                {{ $rec->time_in ? \Illuminate\Support\Carbon::parse($rec->time_in)->format('g:i A') : '—' }}
                            </td>
                            <td class="px-4 py-3 align-top text-slate-600">
                                {{ $rec->time_out ? \Illuminate\Support\Carbon::parse($rec->time_out)->format('g:i A') : '—' }}
                            </td>
                            <td class="px-4 py-3 align-top">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $badge[$rec->status]['class'] ?? 'bg-slate-50 text-slate-600 ring-slate-200' }}">
                                    {{ $badge[$rec->status]['label'] ?? ucfirst($rec->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 align-top text-slate-500">{{ $rec->remarks ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
