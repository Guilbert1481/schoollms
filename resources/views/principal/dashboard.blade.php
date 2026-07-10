{{-- Principal — executive dashboard (basic education oversight).
     Data: App\Services\Dashboard\PrincipalDashboardService ($cards + $dash).
     Layout: row 1 = 8 compact KPIs full-width; below, LEFT panel (filters →
     charts → tables) and RIGHT rail (alerts / schedule / deadlines / quick
     actions) sit in ONE grid row, so both columns get equal height; the rail's
     three list cards flex-stretch with internally scrollable lists and the
     tables cap at ~5 visible rows (scrollable) — bottoms stay aligned.
     Widgets without a data source yet (attendance, expenses, evaluations)
     render explicit "not tracked yet" states — never fabricated numbers. --}}

@extends('layouts.app')

@section('content')

<style>
    /* Scoped layout mechanics (real CSS — immune to Tailwind purge).       */
    /* Right rail: fill the grid row; 3 list cards share the extra height.  */
    .pd-rail { display: flex; flex-direction: column; gap: 1.5rem; }
    .pd-rail-card { display: flex; flex-direction: column; overflow: hidden; }
    .pd-rail-list { overflow-y: auto; min-height: 0; }
    @media (max-width: 1279.98px) {
        .pd-rail-list { max-height: 14rem; }
    }
    @media (min-width: 1280px) {
        .pd-rail { height: 100%; }
        .pd-rail-card { flex: 1 1 0%; min-height: 11rem; }
        .pd-rail-card.pd-rail-fixed { flex: 0 0 auto; min-height: 0; }
        .pd-rail-list { flex: 1 1 0%; }
    }
    /* Bottom tables: ~5 visible rows, scroll for the rest, sticky headers. */
    .pd-tables .overflow-x-auto { max-height: 15.5rem; overflow-y: auto; }
    .pd-tables thead th { position: sticky; top: 0; background: #fff; z-index: 5; }
</style>

@php
    // Literal class strings only (JIT/purge-safe convention, see student dashboard).
    $cardBox   = 'bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl shadow-sm p-6';
    $railBox   = 'bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl shadow-sm p-5';
    $cardTitle = 'text-sm font-extrabold text-slate-800';
    $viewLink  = 'text-xs font-bold text-blue-600 hover:text-blue-700';

    $pill = fn (string $text, string $classes) =>
        '<span class="inline-flex rounded-full px-2 py-0.5 text-xs font-bold '.$classes.'">'.e($text).'</span>';
    $dash404 = '<span class="text-slate-400">—</span>';

    // ------- Students Requiring Immediate Attention -------
    $attnCols = [
        ['key' => 'student', 'label' => 'Student',       'width' => '150px'],
        ['key' => 'grade',   'label' => 'Grade/Section', 'width' => '110px'],
        ['key' => 'concern', 'label' => 'Concern',       'width' => '150px'],
        ['key' => 'risk',    'label' => 'Risk Level',    'width' => '100px', 'raw' => true],
        ['key' => 'last',    'label' => 'Last Action',   'width' => '110px'],
    ];
    $attnRows = collect($dash['attention_students'])->map(fn ($r, $i) => [
        'id'      => $i + 1,
        'student' => $r['student'],
        'grade'   => $r['grade'],
        'concern' => $r['concern'],
        'risk'    => $r['risk'] === 'High'
            ? $pill('High', 'bg-red-100 text-red-700')
            : $pill('Moderate', 'bg-amber-100 text-amber-700'),
        'last'    => $r['last'],
    ])->values()->all();

    // ------- Teacher & Staffing Overview -------
    $staffCols = [
        ['key' => 'teacher',    'label' => 'Teacher',           'width' => '150px'],
        ['key' => 'department', 'label' => 'Department',        'width' => '120px'],
        ['key' => 'attendance', 'label' => 'Attendance',        'width' => '100px', 'raw' => true],
        ['key' => 'submission', 'label' => 'Submission Status', 'width' => '130px', 'raw' => true],
        ['key' => 'evaluation', 'label' => 'Evaluation',        'width' => '110px', 'raw' => true],
    ];
    $staffRows = collect($dash['staffing'])->map(fn ($r, $i) => [
        'id'         => $i + 1,
        'teacher'    => $r['teacher'],
        'department' => $r['department'],
        'attendance' => $r['attendance'] !== null ? e($r['attendance'].'%') : $dash404,
        'submission' => $r['submission'] !== null
            ? $pill($r['submission'], 'bg-emerald-100 text-emerald-700')
            : $dash404,
        'evaluation' => $r['evaluation'] !== null
            ? $pill($r['evaluation'], 'bg-emerald-100 text-emerald-700')
            : $dash404,
    ])->values()->all();

    // ------- Pending Financial Approvals -------
    $finCols = [
        ['key' => 'request',    'label' => 'Request',    'width' => '160px'],
        ['key' => 'department', 'label' => 'Department', 'width' => '110px'],
        ['key' => 'amount',     'label' => 'Amount',     'width' => '100px'],
        ['key' => 'status',     'label' => 'Status',     'width' => '90px', 'raw' => true],
        ['key' => 'date',       'label' => 'Date',       'width' => '110px'],
    ];
    $finRows = collect($dash['pending_approvals'])->map(fn ($r, $i) => [
        'id'         => $i + 1,
        'request'    => $r['request'],
        'department' => $r['department'],
        'amount'     => $r['amount'],
        'status'     => $r['status'] === 'New'
            ? $pill('New', 'bg-blue-100 text-blue-700')
            : $pill('Pending', 'bg-amber-100 text-amber-700'),
        'date'       => $r['date'],
    ])->values()->all();

    // Right-rail alert tones (literal classes).
    $alertTone = [
        'rose'  => 'bg-rose-100 text-rose-600',
        'amber' => 'bg-amber-100 text-amber-600',
        'sky'   => 'bg-sky-100 text-sky-600',
    ];
@endphp

<div class="w-full space-y-6">

    {{-- ======================= ROW 1 — 8 KPIs (full width) ======================= --}}
    <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-8 gap-3">
        @foreach ($cards as $card)
            <x-kpi-row.kpi-shell
                size="compact"
                :title="$card['title']"
                :icon="$card['icon']"
                :accent="$card['accent'] ?? null"
                :subtitle="$card['subtitle'] ?? null"
                :delta="$card['delta'] ?? null"
                :delta-up="$card['delta_up'] ?? null">
                {{ $card['value'] }}
            </x-kpi-row.kpi-shell>
        @endforeach
    </div>

    {{-- ================== PANELS (one grid row → equal heights) ================== --}}
    <div class="grid grid-cols-1 xl:grid-cols-4 gap-6">

        {{-- ============================ LEFT PANEL ============================ --}}
        <div class="xl:col-span-3 space-y-6">

            {{-- Filter bar (display filters; School Year options are real basic-ed AYs) --}}
            <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl shadow-sm p-4">
                <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">School Year</label>
                        <select class="w-full rounded-lg border border-slate-200 px-2 py-1.5 text-sm font-semibold text-slate-700">
                            @forelse($dash['school_years'] as $sy)
                                <option>SY {{ $sy }}</option>
                            @empty
                                <option>— No school year —</option>
                            @endforelse
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Quarter</label>
                        <select class="w-full rounded-lg border border-slate-200 px-2 py-1.5 text-sm font-semibold text-slate-700">
                            <option>All Quarters</option>
                            <option>1st Quarter</option>
                            <option>2nd Quarter</option>
                            <option>3rd Quarter</option>
                            <option>4th Quarter</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Grade Level</label>
                        <select class="w-full rounded-lg border border-slate-200 px-2 py-1.5 text-sm font-semibold text-slate-700">
                            <option>All</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Department</label>
                        <select class="w-full rounded-lg border border-slate-200 px-2 py-1.5 text-sm font-semibold text-slate-700">
                            <option>All</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Date Range</label>
                        <div class="flex items-center gap-2 rounded-lg border border-slate-200 px-2 py-1.5 text-sm font-semibold text-slate-700">
                            <i data-lucide="calendar" class="w-4 h-4 text-slate-400"></i>
                            Full school year
                        </div>
                    </div>
                </div>
            </div>

            {{-- Charts row 1 --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="{{ $cardBox }}">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="{{ $cardTitle }}">Enrollment Trend</h3>
                        <a href="#" class="{{ $viewLink }}">View Report</a>
                    </div>
                    <x-charts.line-chart
                        :labels="$dash['enrollment_trend']['labels']"
                        :values="$dash['enrollment_trend']['values']"
                        color="blue"
                        :y-min="0"
                        empty="No enrollment data yet." />
                </div>

                <div class="{{ $cardBox }}">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="{{ $cardTitle }}">Revenue vs Expenses</h3>
                        <a href="#" class="{{ $viewLink }}">View Report</a>
                    </div>
                    <x-charts.grouped-bars
                        :labels="$dash['revenue_expenses']['labels']"
                        :series="[
                            ['label' => 'Revenue (₱)',  'color' => 'blue', 'values' => $dash['revenue_expenses']['revenue']],
                            ['label' => 'Expenses (₱)', 'color' => 'red',  'values' => $dash['revenue_expenses']['expenses']],
                        ]"
                        empty="No verified payments yet." />
                    <p class="mt-2 text-[11px] font-semibold text-slate-400">Expense tracking is not yet available.</p>
                </div>
            </div>

            {{-- Charts row 2 --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="{{ $cardBox }}">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="{{ $cardTitle }}">Attendance by Grade Level</h3>
                        <a href="#" class="{{ $viewLink }}">View Report</a>
                    </div>
                    <x-charts.bar-list
                        :rows="$dash['attendance_grades']"
                        :max="100"
                        color="emerald"
                        empty="Attendance tracking is not yet available." />
                </div>

                <div class="{{ $cardBox }}">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="{{ $cardTitle }}">Teacher Performance &amp; Compliance</h3>
                        <a href="#" class="{{ $viewLink }}">View Report</a>
                    </div>
                    <div class="space-y-3">
                        @foreach ($dash['teacher_compliance'] as $metric)
                            <div>
                                <div class="flex items-center justify-between text-xs font-semibold text-slate-600 mb-1">
                                    <span>{{ $metric['label'] }}</span>
                                    <span>
                                        @if($metric['value'] !== null)
                                            {{ number_format($metric['value'], 1) }}%
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </span>
                                </div>
                                <div class="h-2 rounded-full" style="background-color:#f1f5f9;">
                                    @if($metric['value'] !== null)
                                        <div class="h-2 rounded-full"
                                             style="background-color:#2563eb; width: {{ max(0, min(100, $metric['value'])) }}%;"></div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <p class="mt-3 text-[11px] font-semibold text-slate-400">Only grade submission is tracked today.</p>
                </div>

                <div class="{{ $cardBox }}">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="{{ $cardTitle }}">Financial Health</h3>
                        <a href="#" class="{{ $viewLink }}">View Report</a>
                    </div>
                    <x-charts.donut-chart
                        :segments="[
                            ['label' => 'Revenue Collected',    'value' => $dash['financial_health']['collected'],
                             'color' => 'blue', 'display' => '₱'.number_format($dash['financial_health']['collected'], 0)],
                            ['label' => 'Outstanding Balances', 'value' => $dash['financial_health']['outstanding'],
                             'color' => 'rose', 'display' => '₱'.number_format($dash['financial_health']['outstanding'], 0)],
                        ]"
                        :center-value="'₱'.number_format($dash['financial_health']['total'], 0)"
                        center-label="Total Funds"
                        empty="No financial activity yet." />
                </div>
            </div>

            {{-- Tables row (~5 visible rows each; lists scroll, headers stick) --}}
            <div class="pd-tables grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <div class="flex items-center justify-between mb-2 px-1">
                        <h3 class="{{ $cardTitle }}">Students Requiring Immediate Attention</h3>
                        <a href="#" class="{{ $viewLink }}">View All</a>
                    </div>
                    <x-table.table
                        tableKey="pdAttn"
                        :columns="$attnCols"
                        :data="$attnRows"
                        :hideActions="true"
                        :hideToolbar="true"
                        emptyMessage="No students flagged — no failing grades posted." />
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2 px-1">
                        <h3 class="{{ $cardTitle }}">Teacher &amp; Staffing Overview</h3>
                        <a href="#" class="{{ $viewLink }}">View All</a>
                    </div>
                    <x-table.table
                        tableKey="pdStaff"
                        :columns="$staffCols"
                        :data="$staffRows"
                        :hideActions="true"
                        :hideToolbar="true"
                        emptyMessage="No active teaching staff records." />
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2 px-1">
                        <h3 class="{{ $cardTitle }}">Pending Financial Approvals</h3>
                        <a href="#" class="{{ $viewLink }}">View All</a>
                    </div>
                    <x-table.table
                        tableKey="pdFin"
                        :columns="$finCols"
                        :data="$finRows"
                        :hideActions="true"
                        :hideToolbar="true"
                        emptyMessage="No pending payment submissions." />
                </div>
            </div>

        </div>

        {{-- ============================ RIGHT RAIL ============================ --}}
        <div class="pd-rail">

            {{-- Critical Alerts (stretches; list scrolls) --}}
            <div class="pd-rail-card {{ $railBox }}">
                <div class="flex items-center justify-between mb-3 shrink-0">
                    <h3 class="{{ $cardTitle }} flex items-center gap-2">
                        <i data-lucide="shield-alert" class="w-4 h-4" style="color:#dc2626;"></i>
                        Critical Alerts
                    </h3>
                    <a href="#" class="{{ $viewLink }}">View All</a>
                </div>
                <div class="pd-rail-list">
                    @forelse ($dash['alerts'] as $alert)
                        <div class="flex items-start gap-3 py-2">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $alertTone[$alert['tone']] ?? 'bg-slate-100 text-slate-600' }}">
                                <i data-lucide="{{ $alert['icon'] }}" class="w-4 h-4"></i>
                            </span>
                            <p class="text-xs font-semibold text-slate-600 leading-snug pt-1.5">{{ $alert['text'] }}</p>
                        </div>
                    @empty
                        <p class="text-xs font-semibold text-slate-400 py-4 text-center">All clear — no critical alerts.</p>
                    @endforelse
                </div>
            </div>

            {{-- Today's Schedule (stretches; list scrolls) --}}
            <div class="pd-rail-card {{ $railBox }}">
                <div class="flex items-center justify-between mb-3 shrink-0">
                    <h3 class="{{ $cardTitle }} flex items-center gap-2">
                        <i data-lucide="calendar-clock" class="w-4 h-4" style="color:#4f46e5;"></i>
                        Today's Schedule
                    </h3>
                    <a href="#" class="{{ $viewLink }}">View All</a>
                </div>
                <div class="pd-rail-list">
                    @forelse ($dash['schedule_today'] as $item)
                        <div class="flex items-center gap-3 py-2">
                            <span class="inline-flex shrink-0 rounded-md bg-indigo-50 px-2 py-1 text-[11px] font-bold text-indigo-600">{{ $item['time'] }}</span>
                            <p class="text-xs font-semibold text-slate-600 truncate">{{ $item['title'] }}</p>
                        </div>
                    @empty
                        <p class="text-xs font-semibold text-slate-400 py-4 text-center">No sessions or events scheduled today.</p>
                    @endforelse
                </div>
            </div>

            {{-- Upcoming Deadlines (stretches; list scrolls) --}}
            <div class="pd-rail-card {{ $railBox }}">
                <div class="flex items-center justify-between mb-3 shrink-0">
                    <h3 class="{{ $cardTitle }} flex items-center gap-2">
                        <i data-lucide="alarm-clock" class="w-4 h-4" style="color:#d97706;"></i>
                        Upcoming Deadlines
                    </h3>
                    <a href="#" class="{{ $viewLink }}">View All</a>
                </div>
                <div class="pd-rail-list">
                    @forelse ($dash['deadlines'] as $d)
                        <div class="flex items-center gap-3 py-2">
                            <div class="flex h-10 w-10 shrink-0 flex-col items-center justify-center rounded-lg bg-red-50">
                                <span class="text-[10px] font-black text-red-500 leading-none">{{ $d['month'] }}</span>
                                <span class="text-sm font-black text-red-600 leading-tight">{{ $d['day'] }}</span>
                            </div>
                            <p class="text-xs font-semibold text-slate-600 leading-snug">{{ $d['title'] }}</p>
                        </div>
                    @empty
                        <p class="text-xs font-semibold text-slate-400 py-4 text-center">No upcoming deadlines.</p>
                    @endforelse
                </div>
            </div>

            {{-- Quick Actions (fixed height — anchors the aligned bottom) --}}
            <div class="pd-rail-card pd-rail-fixed {{ $railBox }}">
                <h3 class="{{ $cardTitle }} flex items-center gap-2 mb-3">
                    <i data-lucide="zap" class="w-4 h-4" style="color:#7c3aed;"></i>
                    Quick Actions
                </h3>
                <div class="space-y-2">
                    <a href="{{ route('communication.chat.index') }}"
                       class="flex items-center gap-2 w-full rounded-xl px-4 py-2.5 text-sm font-bold text-white transition hover:opacity-90"
                       style="background-color:#2563eb;">
                        <i data-lucide="megaphone" class="w-4 h-4"></i> Send Announcement
                    </a>
                    <a href="#"
                       class="flex items-center gap-2 w-full rounded-xl px-4 py-2.5 text-sm font-bold text-white transition hover:opacity-90"
                       style="background-color:#059669;">
                        <i data-lucide="clipboard-check" class="w-4 h-4"></i> Review Attendance
                    </a>
                    <a href="#"
                       class="flex items-center gap-2 w-full rounded-xl px-4 py-2.5 text-sm font-bold text-white transition hover:opacity-90"
                       style="background-color:#7c3aed;">
                        <i data-lucide="users-2" class="w-4 h-4"></i> Review Teachers
                    </a>
                    <a href="#"
                       class="flex items-center gap-2 w-full rounded-xl px-4 py-2.5 text-sm font-bold text-white transition hover:opacity-90"
                       style="background-color:#d97706;">
                        <i data-lucide="bar-chart-3" class="w-4 h-4"></i> View Finance Report
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
