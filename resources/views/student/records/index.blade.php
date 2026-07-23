@extends('layouts.app')

@section('page-title', 'Records')

@section('content')
<div class="w-full space-y-6" x-data="{ riskOpen: false, viewOpen: false, sel: {} }">

    <div>
        <h1 class="text-2xl font-bold text-slate-800">Records</h1>
        <p class="text-sm text-slate-500">Your grade details for every graded activity, computed with your school's grading scheme.</p>
    </div>

    {{-- KPIs --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:max-w-2xl">
        <x-kpi-row.kpi-shell title="Average" icon="trending-up" accent="indigo"
            subtitle="Running average — grading scheme, attendance excluded" size="compact">
            {{ $average !== null ? number_format($average, 2) : '—' }}
        </x-kpi-row.kpi-shell>

        @if (! empty($atRisk))
            <button type="button" @click="riskOpen = true" title="View subjects at risk"
                    class="w-full text-left cursor-pointer rounded-2xl focus:outline-none focus:ring-2 focus:ring-rose-300">
                <x-kpi-row.kpi-shell title="Subjects at Risk" icon="alert-triangle" accent="rose"
                    subtitle="Click for the breakdown and how to pass" size="compact">
                    {{ count($atRisk) }}
                </x-kpi-row.kpi-shell>
            </button>
        @else
            <x-kpi-row.kpi-shell title="Subjects at Risk" icon="alert-triangle" accent="emerald"
                subtitle="No subjects below passing — keep it up" size="compact">
                0
            </x-kpi-row.kpi-shell>
        @endif
    </div>

    {{-- Filter bar --}}
    <form method="GET" action="{{ route('student.records.index') }}"
          class="flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-500">Search</label>
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Title, subject, competency…"
                       class="w-56 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-500">Subject</label>
                <select name="subject_id" onchange="this.form.submit()"
                        class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">All subjects</option>
                    @foreach ($subjects as $s)
                        <option value="{{ $s->id }}" @selected(($filters['subject_id'] ?? null) == $s->id)>
                            {{ $s->name }}{{ $s->code ? ' ('.$s->code.')' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            @if ($isBasicEd)
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-500">Quarter</label>
                    <select name="period" onchange="this.form.submit()"
                            class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">All quarters</option>
                        @foreach ([1, 2, 3, 4] as $q)
                            <option value="{{ $q }}" @selected(($filters['period'] ?? null) == $q)>Quarter {{ $q }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-500">Semester</label>
                    <select name="term_id" onchange="this.form.submit()"
                            class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">All semesters</option>
                        @foreach ($terms as $t)
                            <option value="{{ $t->id }}" @selected(($filters['term_id'] ?? null) == $t->id)>{{ $t->label }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>

        <div class="ml-auto flex flex-wrap items-end gap-3">
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-500">From</label>
                <input type="date" name="from" value="{{ $filters['from'] ?? '' }}"
                       class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-500">To</label>
                <input type="date" name="to" value="{{ $filters['to'] ?? '' }}"
                       class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <button type="submit"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white hover:bg-indigo-700">Filter</button>
            @if (array_filter($filters ?? []))
                <a href="{{ route('student.records.index') }}"
                   class="py-2 text-sm font-bold text-slate-400 hover:text-slate-600">Reset</a>
            @endif
        </div>
    </form>

    {{-- Records table --}}
    @if ($rows->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white/60 p-12 text-center text-slate-500">
            No graded activities yet{{ array_filter($filters ?? []) ? ' for these filters' : '' }}.
            Scores appear here once your teachers grade your tests and homework.
        </div>
    @else
        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-[960px] w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <th class="px-4 py-3">Subject</th>
                        <th class="px-4 py-3">Competency</th>
                        <th class="px-4 py-3">Activity Type</th>
                        <th class="px-4 py-3">Date Assigned</th>
                        <th class="px-4 py-3 text-center">Raw Score</th>
                        <th class="px-4 py-3 text-center">Percentage</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($rows as $row)
                        @php
                            $passing = \App\Services\Dashboard\StudentDashboardService::PASSING_GRADE;
                            $detail = [
                                'title' => $row['title'],
                                'subject' => $row['subject'].($row['subject_code'] ? ' ('.$row['subject_code'].')' : ''),
                                'competency' => $row['competency'] ?? '—',
                                'lesson' => $row['lesson'] ?? '—',
                                'type' => $row['type'],
                                'source' => $row['source'],
                                'component' => $row['component'] ?? '—',
                                'assigned' => $row['date']?->format('M j, Y') ?? '—',
                                'taken' => $row['taken_at']?->format('M j, Y g:i A') ?? '—',
                                'raw' => $row['raw'] !== null && $row['max'] !== null
                                    ? rtrim(rtrim(number_format($row['raw'], 2, '.', ''), '0'), '.').' / '.rtrim(rtrim(number_format($row['max'], 2, '.', ''), '0'), '.')
                                    : '—',
                                'pct' => $row['pct'] !== null ? number_format($row['pct'], 2).'%' : '—',
                                'status' => $row['status'] === 'provisional' ? 'Provisional — essays still being graded' : 'Graded',
                            ];
                        @endphp
                        <tr class="cursor-pointer transition hover:bg-slate-50"
                            data-detail="{{ json_encode($detail) }}"
                            @click="sel = JSON.parse($el.dataset.detail); viewOpen = true">

                            {{-- Subject + activity title --}}
                            <td class="px-4 py-3 align-top">
                                <div class="font-semibold text-slate-800">{{ $row['subject'] }}</div>
                                <div class="text-xs text-slate-500">{{ $row['title'] }}</div>
                            </td>

                            {{-- Competency --}}
                            <td class="px-4 py-3 align-top text-slate-600">
                                <div>{{ $row['competency'] ?? '—' }}</div>
                                @if ($row['lesson'])
                                    <div class="text-xs text-slate-400">{{ $row['lesson'] }}</div>
                                @endif
                            </td>

                            {{-- Activity type --}}
                            <td class="px-4 py-3 align-top">
                                <span class="inline-flex items-center rounded-full bg-slate-50 px-2.5 py-0.5 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-200">
                                    {{ $row['type'] }}
                                </span>
                                @if ($row['component'])
                                    <div class="mt-1 text-xs text-slate-400">{{ $row['component'] }}</div>
                                @endif
                            </td>

                            {{-- Date assigned --}}
                            <td class="px-4 py-3 align-top">
                                @if ($row['date'])
                                    <div class="text-slate-700">{{ $row['date']->format('M j, Y') }}</div>
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>

                            {{-- Raw score --}}
                            <td class="px-4 py-3 align-top text-center font-medium text-slate-700">
                                {{ $detail['raw'] }}
                            </td>

                            {{-- Percentage --}}
                            <td class="px-4 py-3 align-top text-center">
                                @if ($row['pct'] !== null)
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold ring-1 ring-inset
                                        {{ $row['pct'] >= $passing ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-rose-50 text-rose-700 ring-rose-200' }}">
                                        {{ number_format($row['pct'], 2) }}%
                                    </span>
                                    @if ($row['status'] === 'provisional')
                                        <div class="mt-1 text-[10px] font-semibold uppercase tracking-wide text-amber-600">Provisional</div>
                                    @endif
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>

                            {{-- Action --}}
                            <td class="px-4 py-3 align-top text-right">
                                <button type="button"
                                        @click.stop="sel = JSON.parse($el.closest('tr').dataset.detail); viewOpen = true"
                                        class="inline-flex rounded-lg border border-slate-200 px-4 py-1.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                    View
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <p class="text-xs text-slate-400">Tip: click any row to see the full details of that activity.</p>
    @endif

    {{-- Row detail modal --}}
    <div x-show="viewOpen" @keydown.escape.window="viewOpen = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none">
        <div class="absolute inset-0 bg-black/40" @click="viewOpen = false"></div>
        <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-2xl">
            <div class="flex items-start justify-between gap-3 border-b border-slate-100 p-5">
                <div>
                    <h3 class="text-lg font-black text-slate-900" x-text="sel.title"></h3>
                    <p class="mt-0.5 text-xs text-slate-500" x-text="sel.subject"></p>
                </div>
                <button @click="viewOpen = false" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>
            <dl class="grid grid-cols-2 gap-x-4 gap-y-3 p-5 text-sm">
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Activity Type</dt><dd class="mt-0.5 text-slate-700" x-text="sel.type"></dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Mode</dt><dd class="mt-0.5 text-slate-700" x-text="sel.source"></dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Competency</dt><dd class="mt-0.5 text-slate-700" x-text="sel.competency"></dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Lesson</dt><dd class="mt-0.5 text-slate-700" x-text="sel.lesson"></dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Grade Component</dt><dd class="mt-0.5 text-slate-700" x-text="sel.component"></dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Status</dt><dd class="mt-0.5 text-slate-700" x-text="sel.status"></dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Date Assigned</dt><dd class="mt-0.5 text-slate-700" x-text="sel.assigned"></dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Taken / Submitted</dt><dd class="mt-0.5 text-slate-700" x-text="sel.taken"></dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Raw Score</dt><dd class="mt-0.5 font-bold text-slate-800" x-text="sel.raw"></dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Percentage</dt><dd class="mt-0.5 font-bold text-slate-800" x-text="sel.pct"></dd></div>
            </dl>
            <div class="border-t border-slate-100 p-4 text-right">
                <button @click="viewOpen = false"
                        class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200">Close</button>
            </div>
        </div>
    </div>

    {{-- Subjects at Risk — breakdown modal (mirrors the dashboard modal) --}}
    <div x-show="riskOpen" @keydown.escape.window="riskOpen = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none">
        <div class="absolute inset-0 bg-black/40" @click="riskOpen = false"></div>

        <div class="relative flex w-full max-w-3xl flex-col rounded-2xl bg-white shadow-2xl" style="max-height:85vh">
            <div class="flex items-start justify-between gap-3 border-b border-slate-100 p-5">
                <div>
                    <h3 class="flex items-center gap-2 text-lg font-black text-slate-900">
                        <i data-lucide="alert-triangle" class="h-5 w-5 text-rose-500"></i> Subjects at Risk
                    </h3>
                    <p class="mt-0.5 text-xs text-slate-500">
                        Below the passing mark ({{ (int) \App\Services\Dashboard\StudentDashboardService::PASSING_GRADE }}) — why, and how to at least pass.
                    </p>
                </div>
                <button @click="riskOpen = false" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>

            <div class="overflow-auto">
                <table class="w-full text-sm" style="min-width:680px">
                    <thead class="sticky top-0 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Subject</th>
                            <th class="px-5 py-3 text-center">Average</th>
                            <th class="px-5 py-3">Why at risk</th>
                            <th class="px-5 py-3">Recommendation</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($atRisk as $r)
                            <tr class="align-top">
                                <td class="px-5 py-3">
                                    <div class="font-semibold text-slate-800">{{ $r['subject'] }}</div>
                                    @if ($r['code'])<div class="text-xs text-slate-400">{{ $r['code'] }}</div>@endif
                                </td>
                                <td class="px-5 py-3 text-center">
                                    <span class="inline-flex rounded-full bg-rose-50 px-2.5 py-0.5 text-sm font-bold text-rose-600 ring-1 ring-inset ring-rose-200">{{ $r['average'] }}</span>
                                </td>
                                <td class="px-5 py-3">
                                    <ul class="space-y-1">
                                        @foreach ($r['reasons'] as $reason)
                                            <li class="flex items-start gap-1.5 text-slate-600">
                                                <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-rose-400"></span>
                                                <span>{{ $reason }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </td>
                                <td class="px-5 py-3 text-slate-600">{{ $r['recommendation'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-100 p-4 text-right">
                <button @click="riskOpen = false"
                        class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection
