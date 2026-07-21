@extends('layouts.app')

@section('page-title', 'Clearance')

@section('content')
<div class="w-full max-w-4xl space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('registrar.clearances.index') }}" class="text-xs font-semibold text-indigo-600 hover:underline">← All clearances</a>
            <h1 class="text-2xl font-bold text-slate-800">
                {{ trim(($clearance->student?->user?->first_name ?? '').' '.($clearance->student?->user?->last_name ?? '')) ?: 'Student #'.$clearance->student_id }}
            </h1>
            <p class="text-sm text-slate-500">{{ $clearance->purpose }} · started {{ $clearance->created_at->format('M j, Y') }}
                @if ($clearance->note) · “{{ $clearance->note }}” @endif
            </p>
        </div>
        @php
            $badge = match ($clearance->status) {
                'completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                'in_progress' => 'bg-sky-50 text-sky-700 ring-sky-200',
                default => 'bg-amber-50 text-amber-700 ring-amber-200',
            };
        @endphp
        <span class="inline-flex rounded-full px-3 py-1 text-sm font-bold ring-1 ring-inset {{ $badge }}">
            {{ str_replace('_', ' ', ucfirst($clearance->status)) }}
        </span>
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
            {{ session('success') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <ul class="list-disc pl-4">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <th class="px-4 py-3">Signatory</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Acted</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($clearance->items as $item)
                        <tr class="align-top">
                            <td class="px-4 py-3">
                                <p class="font-semibold text-slate-700">{{ $item->label }}</p>
                                @if ($item->remarks)
                                    <p class="text-xs text-slate-400">{{ $item->remarks }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $badge = match ($item->status) {
                                        'cleared' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                        'hold' => 'bg-rose-50 text-rose-700 ring-rose-200',
                                        default => 'bg-amber-50 text-amber-700 ring-amber-200',
                                    };
                                @endphp
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $badge }}">
                                    {{ $item->status === 'hold' ? 'On hold' : ucfirst($item->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-500">
                                {{ $item->acted_at?->format('M j, g:i A') ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <form method="POST" action="{{ route('registrar.clearances.items.update', [$clearance, $item]) }}"
                                      class="flex items-center justify-end gap-2">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" name="remarks" maxlength="255" placeholder="Remarks (optional)"
                                           class="w-36 rounded-lg border border-slate-200 bg-slate-50 px-2 py-1.5 text-xs">
                                    @if ($item->status !== 'cleared')
                                        <button name="action" value="cleared"
                                                class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-emerald-700">Clear</button>
                                    @endif
                                    @if ($item->status !== 'hold')
                                        <button name="action" value="hold"
                                                class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-bold text-rose-600 hover:bg-rose-50">Hold</button>
                                    @endif
                                    @if ($item->status !== 'pending')
                                        <button name="action" value="pending"
                                                class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">Reset</button>
                                    @endif
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
