{{-- for academic student: views/student/dashboard.blade.php
     Registry-driven KPI row (config/dashboard/kpi.php + KPIRegistry) and
     live widgets from StudentDashboardService — nothing hard-coded. --}}

@extends('layouts.app')

@section('content')
@php
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');

    // Full literal class strings so Tailwind compiles them.
    $badgeTone = [
        'rose'    => 'bg-rose-50 text-rose-600 dark:bg-rose-900/30 dark:text-rose-400',
        'amber'   => 'bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400',
        'emerald' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400',
        'sky'     => 'bg-sky-50 text-sky-600 dark:bg-sky-900/30 dark:text-sky-400',
    ];
    $dotTone = [
        'rose'    => 'bg-rose-500',
        'amber'   => 'bg-amber-500',
        'emerald' => 'bg-emerald-500',
        'sky'     => 'bg-sky-500',
    ];

    $cardBox = 'bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl shadow-sm p-6';
@endphp

<div class="w-full space-y-6">

    {{-- Greeting header --}}
    <div>
        <h1 class="text-2xl font-black text-gray-900 dark:text-white">
            {{ $greeting }}, {{ $user->first_name ?? 'there' }}! 👋
        </h1>
        <p class="mt-1 text-sm text-gray-500">
            Keep going! You're <span class="font-bold text-blue-600">{{ $dash['on_track'] }}%</span>
            on track this {{ $dash['term_label'] }}.
        </p>
    </div>

    {{-- KPI row (reusable kpi-shell, registry-driven) --}}
    <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-8 gap-4">
        @foreach ($cards as $card)
            <x-kpi-row.kpi-shell
                :title="$card['title']"
                :icon="$card['icon']"
                :accent="$card['accent'] ?? null"
                :subtitle="$card['subtitle'] ?? null">
                {{ $card['value'] }}
            </x-kpi-row.kpi-shell>
        @endforeach
    </div>

    {{-- Row 2: grade trend · today's schedule · deadlines · announcements --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">

        <div class="{{ $cardBox }}">
            <h2 class="text-sm font-bold text-gray-800 dark:text-white mb-4">Grade Trend</h2>
            <x-charts.line-chart
                :labels="$dash['grade_trend']['labels']"
                :values="$dash['grade_trend']['values']"
                color="blue"
                empty="No grades posted yet." />
        </div>

        <div class="{{ $cardBox }}">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-bold text-gray-800 dark:text-white">Today's Schedule</h2>
                <a href="{{ route('student.subjects.index') }}" class="text-xs font-semibold text-blue-600 hover:underline">View subjects</a>
            </div>
            @if (empty($dash['schedule']))
                <p class="text-sm text-gray-400">No classes scheduled today.</p>
            @else
                <div class="space-y-1 text-sm">
                    @foreach ($dash['schedule'] as $row)
                        <div class="flex items-center gap-3 rounded-lg px-2 py-1.5 {{ $row['now'] ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}">
                            <span class="w-16 shrink-0 text-xs font-bold {{ $row['now'] ? 'text-blue-600' : 'text-gray-400' }}">{{ $row['time'] }}</span>
                            <span class="flex-1 truncate font-semibold text-gray-700 dark:text-gray-200">{{ $row['subject'] }}</span>
                            <span class="hidden sm:block text-xs text-gray-400">{{ $row['room'] }}</span>
                            <span class="hidden lg:block text-xs text-gray-400 truncate max-w-[7rem]">{{ $row['teacher'] }}</span>
                            @if ($row['now'])
                                <span class="text-[10px] font-black text-white bg-blue-600 rounded-full px-2 py-0.5">Now</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="{{ $cardBox }}">
            <h2 class="text-sm font-bold text-gray-800 dark:text-white mb-4">Upcoming Deadlines</h2>
            @if (empty($dash['deadline_list']))
                <p class="text-sm text-gray-400">Nothing due — you're all caught up. 🎉</p>
            @else
                <div class="space-y-3">
                    @foreach ($dash['deadline_list'] as $d)
                        <div class="flex items-start gap-3">
                            <span class="mt-1.5 w-2 h-2 rounded-full shrink-0 {{ $dotTone[$d['tone']] }}"></span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-gray-700 dark:text-gray-200 truncate">{{ $d['title'] }}</p>
                                <p class="text-xs text-gray-400">{{ $d['type'] }} · {{ $d['when'] }}</p>
                            </div>
                            <span class="text-[10px] font-bold rounded-full px-2 py-0.5 {{ $badgeTone[$d['tone']] }}">{{ $d['badge'] }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="{{ $cardBox }}">
            <h2 class="text-sm font-bold text-gray-800 dark:text-white mb-4">Announcements</h2>
            @if (empty($dash['announcements']))
                <p class="text-sm text-gray-400">No announcements right now.</p>
            @else
                <div class="space-y-3">
                    @foreach ($dash['announcements'] as $a)
                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 p-1.5 rounded-lg shrink-0 {{ $a['super'] ? 'bg-rose-50 text-rose-600' : 'bg-blue-50 text-blue-600' }}">
                                <i data-lucide="megaphone" class="w-3.5 h-3.5"></i>
                            </span>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-700 dark:text-gray-200 truncate">{{ $a['title'] }}</p>
                                <p class="text-xs text-gray-400">{{ $a['when'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Row 3: subject performance · assignments · recent grades · payments --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">

        <div class="{{ $cardBox }}">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-bold text-gray-800 dark:text-white">Subject Performance</h2>
                <a href="{{ route('student.subjects.index') }}" class="text-xs font-semibold text-blue-600 hover:underline">View all</a>
            </div>
            <x-charts.bar-list :rows="$dash['performance']" :max="100" color="blue" empty="No grades posted yet." />
        </div>

        <div class="{{ $cardBox }}">
            <h2 class="text-sm font-bold text-gray-800 dark:text-white mb-4">Assignment Overview</h2>
            <x-charts.donut-chart
                :segments="[
                    ['label' => 'Completed', 'value' => $dash['assignments']['completed'], 'color' => 'emerald'],
                    ['label' => 'Pending',   'value' => $dash['assignments']['pending'],   'color' => 'amber'],
                    ['label' => 'Overdue',   'value' => $dash['assignments']['overdue'],   'color' => 'rose'],
                ]"
                :center-value="$dash['assignments']['total']"
                center-label="Total"
                empty="No tasks assigned yet." />
        </div>

        <div class="{{ $cardBox }}">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-bold text-gray-800 dark:text-white">Recent Grades</h2>
                <a href="{{ route('student.transcript.index') }}" class="text-xs font-semibold text-blue-600 hover:underline">Transcript</a>
            </div>
            @if (empty($dash['recent_grades']))
                <p class="text-sm text-gray-400">No grades posted yet.</p>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-400 uppercase">
                            <th class="pb-2 font-semibold">Subject</th>
                            <th class="pb-2 font-semibold text-right">Grade</th>
                            <th class="pb-2 font-semibold text-right">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                        @foreach ($dash['recent_grades'] as $g)
                            <tr>
                                <td class="py-2 font-semibold text-gray-700 dark:text-gray-200 truncate max-w-[9rem]">{{ $g['subject'] }}</td>
                                <td class="py-2 text-right font-bold {{ $g['at_risk'] ? 'text-rose-600' : 'text-emerald-600' }}">{{ $g['grade'] }}</td>
                                <td class="py-2 text-right text-xs text-gray-400">{{ $g['date'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="{{ $cardBox }}">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-bold text-gray-800 dark:text-white">Payment Summary</h2>
                <a href="{{ route('student.finance.index') }}" class="text-xs font-semibold text-blue-600 hover:underline">My Finance</a>
            </div>
            <x-charts.donut-chart
                :segments="[
                    ['label' => 'Paid', 'value' => (int) round($dash['billing']['paid']), 'display' => '₱'.number_format($dash['billing']['paid'], 2), 'color' => 'emerald'],
                    ['label' => 'Outstanding', 'value' => (int) round($dash['billing']['outstanding'] - $dash['billing']['overdue']), 'display' => '₱'.number_format($dash['billing']['outstanding'] - $dash['billing']['overdue'], 2), 'color' => 'sky'],
                    ['label' => 'Overdue', 'value' => (int) round($dash['billing']['overdue']), 'display' => '₱'.number_format($dash['billing']['overdue'], 2), 'color' => 'rose'],
                ]"
                :center-value="'₱'.number_format($dash['billing']['outstanding'], 0)"
                center-label="Balance"
                empty="No billing activity yet." />
        </div>
    </div>

    {{-- Row 4: subject progress · calendar · study coach --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        <div class="{{ $cardBox }}">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-bold text-gray-800 dark:text-white">Subject Progress</h2>
                <a href="{{ route('student.subjects.index') }}" class="text-xs font-semibold text-blue-600 hover:underline">View all</a>
            </div>
            @if (empty($dash['subject_cards']))
                <p class="text-sm text-gray-400">No enrolled subjects this term.</p>
            @else
                <div class="grid grid-cols-2 gap-4">
                    @php $ring = ['indigo', 'emerald', 'violet', 'amber']; @endphp
                    @foreach ($dash['subject_cards'] as $i => $s)
                        <div class="border border-gray-100 dark:border-gray-700 rounded-xl p-4 flex flex-col items-center text-center gap-2">
                            <p class="text-xs font-bold text-gray-700 dark:text-gray-200 truncate w-full">{{ $s['name'] }}</p>
                            <x-charts.radial-progress :percent="$s['progress']" :color="$ring[$i % 4]" />
                            <p class="text-[11px] text-gray-400">Average <span class="font-bold text-gray-600 dark:text-gray-300">{{ $s['grade'] }}</span></p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="{{ $cardBox }}">
            @php
                $cal      = $dash['calendar'];
                $firstDay = \Illuminate\Support\Carbon::create($cal['year'], $cal['month'], 1);
                $offset   = $firstDay->dayOfWeek;             // 0 = Sunday
                $numDays  = $firstDay->daysInMonth;
                $today    = now()->format('Y-n') === $cal['year'].'-'.$cal['month'] ? (int) now()->format('j') : null;
                $dotColor = ['class' => 'bg-blue-500', 'assignment' => 'bg-amber-500', 'exam' => 'bg-rose-500'];
            @endphp
            <h2 class="text-sm font-bold text-gray-800 dark:text-white mb-4">Calendar · {{ $firstDay->format('F Y') }}</h2>
            <div class="grid grid-cols-7 text-center text-[11px] font-bold text-gray-400 mb-1">
                @foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $dow)
                    <span class="py-1">{{ $dow }}</span>
                @endforeach
            </div>
            <div class="grid grid-cols-7 text-center text-sm gap-y-1">
                @for ($i = 0; $i < $offset; $i++)
                    <span></span>
                @endfor
                @for ($day = 1; $day <= $numDays; $day++)
                    <div class="flex flex-col items-center py-0.5">
                        <span class="w-7 h-7 flex items-center justify-center rounded-full text-gray-600 dark:text-gray-300
                            {{ $day === $today ? 'bg-blue-600 text-white font-bold' : '' }}">{{ $day }}</span>
                        <span class="flex gap-0.5 h-1.5 mt-0.5">
                            @foreach ($cal['days'][$day] ?? [] as $type)
                                <span class="w-1.5 h-1.5 rounded-full {{ $dotColor[$type] ?? 'bg-gray-300' }}"></span>
                            @endforeach
                        </span>
                    </div>
                @endfor
            </div>
            <div class="flex gap-4 mt-3 pt-3 border-t border-gray-50 dark:border-gray-700 text-[11px] text-gray-500">
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-blue-500"></span> Class</span>
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-amber-500"></span> Assignment</span>
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-rose-500"></span> Exam</span>
            </div>
        </div>

        <div class="rounded-2xl border border-indigo-100 dark:border-indigo-900 shadow-sm p-6 bg-gradient-to-br from-indigo-50 via-sky-50 to-white dark:from-indigo-900/20 dark:via-sky-900/10 dark:to-gray-800">
            <div class="flex items-center gap-3 mb-4">
                <span class="p-2.5 rounded-xl bg-indigo-600 text-white"><i data-lucide="bot" class="w-5 h-5"></i></span>
                <div>
                    <h2 class="text-sm font-black text-gray-800 dark:text-white">Study Coach</h2>
                    <p class="text-xs text-gray-500">Your academic snapshot for today</p>
                </div>
            </div>
            <div class="space-y-2.5">
                @foreach ($dash['snapshot'] as $b)
                    <div class="flex items-start gap-2.5">
                        <span class="mt-1.5 w-2 h-2 rounded-full shrink-0 {{ $dotTone[$b['tone']] ?? 'bg-gray-300' }}"></span>
                        <p class="text-sm text-gray-600 dark:text-gray-300">{{ $b['text'] }}</p>
                    </div>
                @endforeach
            </div>
            <a href="{{ route('student.subjects.index') }}"
               class="mt-5 inline-flex items-center gap-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold px-4 py-2 transition">
                Study Now <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </a>
        </div>
    </div>

    {{-- Quick actions (real pages only) --}}
    <div class="flex flex-wrap items-center gap-3">
        <span class="text-sm font-bold text-gray-500 mr-1">Quick Actions</span>
        @foreach ([
            ['icon' => 'clipboard-list', 'label' => 'My Subjects',      'route' => route('student.subjects.index')],
            ['icon' => 'file-text',      'label' => 'My Invoices',      'route' => route('student.finance.invoices')],
            ['icon' => 'credit-card',    'label' => 'My Payments',      'route' => route('student.finance.payments')],
            ['icon' => 'wallet',         'label' => 'Finance Overview', 'route' => route('student.finance.index')],
            ['icon' => 'graduation-cap', 'label' => 'Transcript',       'route' => route('student.transcript.index')],
            ['icon' => 'folder-open',    'label' => 'My Applications',  'route' => route('student.applications.index')],
        ] as $action)
            <a href="{{ $action['route'] }}"
               class="inline-flex items-center gap-2 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm font-semibold text-gray-600 dark:text-gray-300 shadow-sm hover:shadow hover:text-blue-600 transition">
                <i data-lucide="{{ $action['icon'] }}" class="w-4 h-4"></i> {{ $action['label'] }}
            </a>
        @endforeach
    </div>

</div>
@endsection
