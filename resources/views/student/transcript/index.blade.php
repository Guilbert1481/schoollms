@extends('layouts.app')

@section('content')
@php
    // Pretty label for semester numeric codes used in section headings.
    $semLabel = function ($sem) {
        return match ((int) $sem) {
            1       => '1st Semester',
            2       => '2nd Semester',
            3       => 'Summer',
            default => 'Semester '.$sem,
        };
    };
@endphp

<div class="mx-auto w-full max-w-7xl space-y-6 p-4 md:p-6">

    {{-- Header --}}
    <div class="rounded-2xl bg-gradient-to-br from-indigo-600 via-blue-700 to-sky-800 p-6 md:p-8 text-white shadow-lg">
        <div class="flex items-center gap-3">
            <i data-lucide="scroll-text" class="w-8 h-8"></i>
            <div>
                <h1 class="text-2xl md:text-3xl font-bold">Academic Transcript</h1>
                <p class="text-sm md:text-base text-indigo-100 mt-1">
                    Your complete record of subjects, grades, and earned units.
                </p>
            </div>
        </div>
    </div>

    {{-- Summary cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Program</div>
            <div class="mt-2 text-xl font-bold text-slate-900">{{ $programLabel }}</div>
            <div class="mt-1 text-xs text-slate-500">Current degree program</div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Credits</div>
            <div class="mt-2 text-xl font-bold text-slate-900">
                {{ rtrim(rtrim(number_format($totalUnits, 2, '.', ''), '0'), '.') }}
                <span class="text-slate-400 font-medium">/ {{ $requiredUnits ? rtrim(rtrim(number_format($requiredUnits, 2, '.', ''), '0'), '.') : '—' }}</span>
            </div>
            <div class="mt-1 text-xs font-semibold text-emerald-600">{{ $percentDone }}% Completed</div>
            @if ($requiredUnits > 0)
                <div class="mt-2 h-1.5 w-full rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full bg-emerald-500" style="width: {{ min(100, $percentDone) }}%;"></div>
                </div>
            @endif
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">GWA</div>
            <div class="mt-2 text-xl font-bold text-slate-900">
                {{ $gwa !== null ? number_format($gwa, 2) : '—' }}
            </div>
            <div class="mt-1 text-xs text-slate-500">General Weighted Average</div>
        </div>
    </div>

    {{-- Year → Semester groups --}}
    @if ($groups->isEmpty())
        {{-- No curriculum yet: still render the table chrome (filter, columns,
             header) so the UI is consistent. The notice is injected inside the
             tbody as a colspan row so it sits in the table body section. --}}
        <x-table.table
            tableKey="transcript_empty"
            :columns="$columns"
            :data="collect()"
            :hideActions="true"
        />
        <script>
            (function () {
                const table = document.getElementById('transcript_emptyTable');
                if (!table) return;
                const tbody = table.querySelector('tbody');
                if (!tbody) return;
                const colCount = table.querySelectorAll('thead th').length || {{ count($columns) }};
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td colspan="${colCount}" class="px-4 py-10 text-center">
                        <div class="mx-auto max-w-xl rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                            No curriculum is on file for your program yet, so the transcript cannot be displayed.
                        </div>
                    </td>`;
                tbody.appendChild(tr);
            })();
        </script>
    @else
        @foreach ($groups as $year => $semesterGroups)
            {{-- YEAR separator --}}
            <div class="flex items-center gap-3 mt-2">
                <div class="px-3 py-1 rounded-full bg-indigo-600 text-white text-xs font-bold uppercase tracking-wider">
                    Year {{ $year }}
                </div>
                <div class="flex-1 h-px bg-slate-200"></div>
            </div>

            @foreach ($semesterGroups as $sem => $rows)
                {{-- Semester sub-heading --}}
                <div class="flex items-center gap-2 mt-3 ml-1">
                    <i data-lucide="bookmark" class="w-4 h-4 text-slate-500"></i>
                    <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">
                        {{ $semLabel($sem) }}
                    </h3>
                </div>

                {{-- Shareable table with built-in filter, dynamic columns,
                     and pagination — one per Year/Semester group. --}}
                <x-table.table
                    tableKey="transcript_y{{ $year }}_s{{ $sem }}"
                    :columns="$columns"
                    :data="$rows->values()"
                    :hideActions="true"
                />
            @endforeach
        @endforeach
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide?.createIcons) window.lucide.createIcons();
    });
</script>
@endsection
