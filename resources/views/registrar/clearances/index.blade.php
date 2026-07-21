@extends('layouts.app')

@section('page-title', 'Clearances')

@section('content')
<div class="w-full space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-800">Clearances</h1>
        <p class="text-sm text-slate-500">Student clearances and their sign-off progress.</p>
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        @if ($clearances->isEmpty())
            <p class="px-5 py-10 text-center text-sm text-slate-400">No clearances yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <th class="px-4 py-3">Student</th>
                            <th class="px-4 py-3">Purpose</th>
                            <th class="px-4 py-3">Started</th>
                            <th class="px-4 py-3">Progress</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($clearances as $clearance)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-slate-700">
                                    {{ trim(($clearance->student?->user?->first_name ?? '').' '.($clearance->student?->user?->last_name ?? '')) ?: 'Student #'.$clearance->student_id }}
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $clearance->purpose }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ $clearance->created_at->format('M j, Y') }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="h-1.5 w-24 overflow-hidden rounded-full bg-slate-100">
                                            <div class="h-full rounded-full bg-emerald-500"
                                                 style="width: {{ $clearance->items_count ? (int) round($clearance->cleared_items_count / $clearance->items_count * 100) : 0 }}%"></div>
                                        </div>
                                        <span class="text-xs text-slate-500">{{ $clearance->cleared_items_count }}/{{ $clearance->items_count }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    @php
                                        $badge = match ($clearance->status) {
                                            'completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                            'in_progress' => 'bg-sky-50 text-sky-700 ring-sky-200',
                                            default => 'bg-amber-50 text-amber-700 ring-amber-200',
                                        };
                                    @endphp
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $badge }}">
                                        {{ str_replace('_', ' ', ucfirst($clearance->status)) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('registrar.clearances.show', $clearance) }}"
                                       class="inline-flex rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                        Manage
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
