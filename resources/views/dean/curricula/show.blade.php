@extends('layouts.app')

@php
    // Pretty term labels.
    $termsPerYear = (int) $curriculum->terms_per_year;
    $hasSummer = $curriculum->has_summer_term || $rows->contains(fn($s) => in_array(strtoupper($s->semester), ['S','SUMMER']));
    $termLabels = $termsPerYear === 3
        ? [1 => '1st Trimester', 2 => '2nd Trimester', 3 => '3rd Trimester']
        : [1 => '1st Semester',  2 => '2nd Semester'];
    if ($hasSummer) $termLabels['S'] = 'Summer';

    $yearLabels = [
        1 => '1st Year', 2 => '2nd Year', 3 => '3rd Year',
        4 => '4th Year', 5 => '5th Year',
    ];
@endphp

@section('content')
<div class="container mx-auto px-6 py-6">

    {{-- Header --}}
    <div class="mb-6">
        <a href="{{ route('dean.curricula.index') }}" class="text-sm text-indigo-600">← Back to curricula</a>
        <div class="flex items-start justify-between mt-2">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">{{ $curriculum->name }}</h1>
                <p class="text-sm text-slate-500 mt-1">
                    {{ $curriculum->program?->code }} — {{ $curriculum->program?->name }}
                    · v{{ $curriculum->version }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                @if ($termsPerYear === 3)
                    <span class="px-3 py-1 rounded bg-purple-100 text-purple-800 text-xs font-bold">Trimester (3)</span>
                @else
                    <span class="px-3 py-1 rounded bg-blue-100 text-blue-800 text-xs font-bold">Bi-semester (2)</span>
                @endif
                <a href="{{ route('dean.curricula.edit', $curriculum->id) }}"
                   class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-sm">Edit</a>
            </div>
        </div>
    </div>

    {{-- Summary strip --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white border rounded p-4">
            <div class="text-xs text-slate-500 uppercase">Total Subjects</div>
            <div class="text-2xl font-bold text-slate-800 mt-1">{{ $rows->count() }}</div>
        </div>
        <div class="bg-white border rounded p-4">
            <div class="text-xs text-slate-500 uppercase">Total Units</div>
            <div class="text-2xl font-bold text-slate-800 mt-1">{{ rtrim(rtrim(number_format($totalUnits, 2), '0'), '.') }}</div>
        </div>
        <div class="bg-white border rounded p-4">
            <div class="text-xs text-slate-500 uppercase">Years</div>
            <div class="text-2xl font-bold text-slate-800 mt-1">{{ $grouped->keys()->count() }}</div>
        </div>
        <div class="bg-white border rounded p-4">
            <div class="text-xs text-slate-500 uppercase">Terms/Year</div>
            <div class="text-2xl font-bold text-slate-800 mt-1">{{ $termsPerYear }}</div>
        </div>
    </div>

    @if ($rows->isEmpty())
        <div class="bg-white border rounded p-10 text-center text-slate-500">
            No subjects mapped to this curriculum yet.
        </div>
    @else
        {{-- Year-by-year, term-by-term --}}
        @foreach ($grouped->sortKeys() as $year => $byTerm)
            <div class="mb-8">
                <h2 class="text-lg font-bold text-slate-800 mb-3">
                    {{ $yearLabels[$year] ?? "Year {$year}" }}
                </h2>

                <div class="grid grid-cols-1 lg:grid-cols-{{ $termsPerYear + ($hasSummer ? 1 : 0) }} gap-4">
                    @foreach (array_merge(array_keys($termLabels)) as $term)
                        @php $subjects = $byTerm[$term] ?? collect(); $termUnits = $subjects->sum('units'); @endphp

                        <div class="bg-white border rounded shadow-sm overflow-hidden">
                            <div class="bg-slate-50 border-b px-4 py-2 flex items-center justify-between">
                                <span class="font-semibold text-sm text-slate-700">
                                    {{ $termLabels[$term] ?? (is_numeric($term) ? "Term {$term}" : ucfirst($term)) }}
                                </span>
                                <span class="text-xs text-slate-500">
                                    {{ $subjects->count() }} subjects ·
                                    <strong>{{ rtrim(rtrim(number_format($termUnits, 2), '0'), '.') }} u</strong>
                                </span>
                            </div>

                            <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="text-xs uppercase text-slate-500 bg-white border-b">
                                    <tr>
                                        <th class="px-3 py-2 text-left">Code</th>
                                        <th class="px-3 py-2 text-left">Subject</th>
                                        <th class="px-3 py-2 text-right">Units</th>
                                        <th class="px-3 py-2 text-center">Type</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($subjects as $s)
                                        <tr class="border-b last:border-b-0 hover:bg-slate-50">
                                            <td class="px-3 py-2 font-mono text-xs text-slate-700">{{ $s->code }}</td>
                                            <td class="px-3 py-2">{{ $s->name }}</td>
                                            <td class="px-3 py-2 text-right">{{ rtrim(rtrim(number_format($s->units, 2), '0'), '.') }}</td>
                                            <td class="px-3 py-2 text-center">
                                                @if ($s->is_elective)
                                                    <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-800 text-xs">Elective</span>
                                                @elseif ($s->is_core)
                                                    <span class="px-2 py-0.5 rounded bg-green-100 text-green-800 text-xs">Core</span>
                                                @else
                                                    <span class="text-xs text-slate-400">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    @endif
</div>
@endsection
