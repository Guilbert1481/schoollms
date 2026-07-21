@extends('layouts.app')

@section('page-title', 'Assessments')

@section('content')
<div class="w-full space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-800">Assessments</h1>
        <p class="text-sm text-slate-500">Every online test assigned to your classes.</p>
    </div>

    @php
        $badge = [
            'pending'   => ['label' => 'Pending',      'class' => 'bg-sky-50 text-sky-700 ring-sky-200'],
            'completed' => ['label' => 'Completed',    'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
            'grading'   => ['label' => 'Being graded', 'class' => 'bg-amber-50 text-amber-700 ring-amber-200'],
            'upcoming'  => ['label' => 'Upcoming',     'class' => 'bg-slate-50 text-slate-500 ring-slate-200'],
            'missed'    => ['label' => 'Missed',        'class' => 'bg-rose-50 text-rose-700 ring-rose-200'],
        ];
    @endphp

    @if ($rows->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white/60 p-12 text-center text-slate-500">
            You have no online tests yet. When a teacher publishes one for your class, it appears here.
        </div>
    @else
        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-[880px] w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <th class="px-4 py-3">Title</th>
                        <th class="px-4 py-3">Lesson</th>
                        <th class="px-4 py-3 text-center">Items</th>
                        <th class="px-4 py-3">Start Date</th>
                        <th class="px-4 py-3">End Date</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($rows as $row)
                        @php
                            $state = $row['state'];
                            $isPending   = $state === 'pending';
                            $isCompleted = $state === 'completed';
                            $badgeKey = ($isCompleted && $row['needsManual']) ? 'grading' : $state;

                            $rowUrl = $isPending
                                ? route('student.assessments.start', $row['id'])
                                : ($isCompleted ? route('student.assessments.result', $row['id']) : null);
                        @endphp
                        <tr class="{{ $rowUrl ? 'cursor-pointer hover:bg-slate-50' : '' }} transition"
                            @if ($rowUrl) onclick="window.location='{{ $rowUrl }}'" @endif>

                            {{-- Title + competency --}}
                            <td class="px-4 py-3 align-top">
                                <div class="font-semibold text-slate-800">{{ $row['title'] }}</div>
                                <div class="text-xs text-slate-500">
                                    {{ $row['competency'] ?? $row['subject'] ?? 'Assessment' }}
                                </div>
                            </td>

                            {{-- Lesson --}}
                            <td class="px-4 py-3 align-top text-slate-600">
                                {{ $row['lesson'] ?? '—' }}
                            </td>

                            {{-- Items --}}
                            <td class="px-4 py-3 align-top text-center font-medium text-slate-700">
                                {{ $row['items'] }}
                            </td>

                            {{-- Start Date --}}
                            <td class="px-4 py-3 align-top">
                                @if ($row['opensAt'])
                                    <div class="text-slate-700">{{ $row['opensAt']->format('M j, Y') }}</div>
                                    <div class="text-xs text-slate-400">{{ $row['opensAt']->format('g:i A') }}</div>
                                @else
                                    <span class="text-xs text-slate-400">On publish</span>
                                @endif
                            </td>

                            {{-- End Date --}}
                            <td class="px-4 py-3 align-top">
                                @if ($row['closesAt'])
                                    <div class="text-slate-700">{{ $row['closesAt']->format('M j, Y') }}</div>
                                    <div class="text-xs text-slate-400">{{ $row['closesAt']->format('g:i A') }}</div>
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>

                            {{-- Status --}}
                            <td class="px-4 py-3 align-top">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $badge[$badgeKey]['class'] }}">
                                    {{ $badge[$badgeKey]['label'] }}
                                </span>
                            </td>

                            {{-- Action --}}
                            <td class="px-4 py-3 align-top text-right">
                                @if ($isPending)
                                    <a href="{{ route('student.assessments.start', $row['id']) }}"
                                       onclick="event.stopPropagation()"
                                       class="inline-flex rounded-lg bg-sky-600 px-4 py-1.5 text-sm font-bold text-white hover:bg-sky-700">
                                        {{ $row['hasActive'] ? 'Resume' : 'Start' }}
                                    </a>
                                @elseif ($isCompleted)
                                    <a href="{{ route('student.assessments.result', $row['id']) }}"
                                       onclick="event.stopPropagation()"
                                       class="inline-flex rounded-lg border border-slate-200 px-4 py-1.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                        View result
                                    </a>
                                @elseif ($state === 'upcoming')
                                    <span class="text-xs text-slate-400">Not open yet</span>
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <p class="text-xs text-slate-400">Tip: click a pending row to start the test.</p>
    @endif
</div>
@endsection
