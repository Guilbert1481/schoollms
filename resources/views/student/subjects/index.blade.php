@extends('layouts.app')

@section('content')
<div id="student-subjects" class="mx-auto w-full max-w-7xl space-y-6 p-4 md:p-6">

    {{-- Header --}}
    <div class="rounded-2xl bg-gradient-to-br from-indigo-600 via-blue-700 to-sky-800 p-6 md:p-8 text-white shadow-lg">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <i data-lucide="book-open" class="w-8 h-8"></i>
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold">My Subjects</h1>
                    <p class="text-sm md:text-base text-indigo-100 mt-1">
                        Subjects you are enrolled in for the active term.
                    </p>
                </div>
            </div>

            <div class="flex flex-col items-end text-sm">
                <div class="font-semibold">
                    {{ $activeYear?->name ?? $activeYear?->label ?? 'No active academic year' }}
                </div>
                <div class="text-indigo-100">
                    {{ $activeTerm?->name ?? $activeTerm?->term ?? 'No active term' }}
                </div>
                @if ($enrollment?->program)
                    <div class="mt-1 text-xs text-indigo-100">
                        {{ $enrollment->program->code }} · {{ $enrollment->program->name }}
                        @if ($enrollment->section)
                            <span class="opacity-80"> · Section {{ $enrollment->section->name }}</span>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="flex flex-wrap items-center gap-2">
        <div class="relative flex-1 min-w-[220px] max-w-md">
            <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
            <input type="text"
                   data-subjects-search
                   placeholder="Search subject name, code, schedule…"
                   class="w-full pl-9 pr-3 py-2 rounded-lg border border-slate-300 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
        </div>

        <div class="inline-flex rounded-lg border border-slate-300 overflow-hidden text-xs font-medium">
            <button type="button" data-view-toggle="cards"
                    class="px-3 py-2 flex items-center gap-1">
                <i data-lucide="layout-grid" class="w-4 h-4"></i> Cards
            </button>
            <button type="button" data-view-toggle="table"
                    class="px-3 py-2 flex items-center gap-1 border-l border-slate-300">
                <i data-lucide="table-2" class="w-4 h-4"></i> Table
            </button>
        </div>

        <span class="ml-auto text-xs text-slate-500">
            <span data-subjects-count>{{ $subjects->count() }}</span> of {{ $subjects->count() }} subject(s)
        </span>
    </div>

    {{-- No active enrollment --}}
    @if ($subjects->isEmpty())
        <div data-subjects-empty
             class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center">
            <div class="inline-flex items-center justify-center h-12 w-12 rounded-full bg-slate-100 mb-3">
                <i data-lucide="inbox" class="w-6 h-6 text-slate-400"></i>
            </div>
            <h2 class="text-base font-semibold text-slate-700">No enrolled subjects</h2>
            <p class="mt-1 text-sm text-slate-500">
                @if (! $activeYear)
                    There is no active academic year configured yet.
                @elseif (! $enrollment)
                    You don't have an active enrollment for {{ $activeYear?->name }}{{ $activeTerm ? ' · '.($activeTerm->name ?? $activeTerm->term) : '' }}.
                @else
                    Your enrollment has no subjects assigned yet.
                @endif
            </p>
        </div>
    @else
        {{-- Cards view --}}
        <div data-view="cards" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($subjects as $s)
                <div data-subject-card
                     data-search="{{ strtolower(($s['code'] ?? '').' '.($s['name'] ?? '').' '.($s['schedule'] ?? '').' '.($s['section'] ?? '').' '.($s['class_code'] ?? '')) }}"
                     class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:shadow-md transition">
                    <div class="flex items-start justify-between gap-2">
                        <div class="inline-flex items-center justify-center h-10 w-10 rounded-xl bg-indigo-50 ring-1 ring-indigo-200">
                            <i data-lucide="book" class="w-5 h-5 text-indigo-600"></i>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold ring-1 bg-emerald-50 text-emerald-700 ring-emerald-200 capitalize">
                            {{ $s['status'] }}
                        </span>
                    </div>

                    <div class="mt-3">
                        <div class="text-xs font-mono text-slate-500">{{ $s['code'] }}</div>
                        <h3 class="text-base font-semibold text-slate-800 leading-snug">
                            {{ $s['name'] }}
                        </h3>
                    </div>

                    <dl class="mt-3 space-y-1 text-xs text-slate-600">
                        @if ($s['class_code'])
                            <div class="flex items-center gap-1.5">
                                <i data-lucide="hash" class="w-3.5 h-3.5 text-slate-400"></i>
                                <span>Class {{ $s['class_code'] }}</span>
                            </div>
                        @endif
                        @if ($s['section'])
                            <div class="flex items-center gap-1.5">
                                <i data-lucide="users" class="w-3.5 h-3.5 text-slate-400"></i>
                                <span>Section {{ $s['section'] }}</span>
                            </div>
                        @endif
                        @if ($s['schedule'])
                            <div class="flex items-center gap-1.5">
                                <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400"></i>
                                <span>{{ $s['schedule'] }}</span>
                            </div>
                        @endif
                        @if ($s['room'])
                            <div class="flex items-center gap-1.5">
                                <i data-lucide="map-pin" class="w-3.5 h-3.5 text-slate-400"></i>
                                <span>Room {{ $s['room'] }}</span>
                            </div>
                        @endif
                    </dl>
                </div>
            @endforeach
        </div>

        {{-- Table view --}}
        <div data-view="table" class="hidden bg-white border border-slate-200 rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-600">
                        <tr>
                            <th class="px-4 py-3 w-32">Code</th>
                            <th class="px-4 py-3">Subject</th>
                            <th class="px-4 py-3">Section</th>
                            <th class="px-4 py-3">Schedule</th>
                            <th class="px-4 py-3 w-24">Room</th>
                            <th class="px-4 py-3 w-28">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($subjects as $s)
                            <tr data-subject-row
                                data-search="{{ strtolower(($s['code'] ?? '').' '.($s['name'] ?? '').' '.($s['schedule'] ?? '').' '.($s['section'] ?? '').' '.($s['class_code'] ?? '')) }}"
                                class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-mono text-xs text-slate-700">{{ $s['code'] }}</td>
                                <td class="px-4 py-3 text-slate-800">
                                    <div class="font-medium">{{ $s['name'] }}</div>
                                    @if ($s['class_code'])
                                        <div class="text-xs text-slate-500">Class {{ $s['class_code'] }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-700">{{ $s['section'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $s['schedule'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $s['room'] ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold ring-1 bg-emerald-50 text-emerald-700 ring-emerald-200 capitalize">
                                        {{ $s['status'] }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div data-subjects-empty class="hidden rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">
            No subjects match your search.
        </div>
    @endif
</div>

@push('scripts')
    <script src="{{ asset('js/student/subjects.js') }}" defer></script>
    <script>
        if (window.lucide?.createIcons) lucide.createIcons();
    </script>
@endpush
@endsection
