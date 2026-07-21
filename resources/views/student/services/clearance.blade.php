@extends('layouts.app')

@section('page-title', 'Clearance')

@section('content')
<div class="w-full max-w-4xl space-y-6" x-data="{ tab: @js($tab) }">

    <div>
        <h1 class="text-2xl font-bold text-slate-800">Clearance</h1>
        <p class="text-sm text-slate-500">Start a clearance and follow each office's sign-off.</p>
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

    {{-- Tabs --}}
    <div class="flex gap-1 rounded-xl bg-slate-100 p-1 w-fit">
        <button @click="tab = 'request'"
                :class="tab === 'request' ? 'bg-white shadow text-slate-800' : 'text-slate-500'"
                class="rounded-lg px-5 py-2 text-sm font-bold transition">Request</button>
        <button @click="tab = 'status'"
                :class="tab === 'status' ? 'bg-white shadow text-slate-800' : 'text-slate-500'"
                class="rounded-lg px-5 py-2 text-sm font-bold transition">Status</button>
    </div>

    {{-- Request tab --}}
    <div x-show="tab === 'request'" x-cloak>
        @if (! $enrollment)
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white/60 p-10 text-center text-slate-500">
                You have no active enrollment this term.
            </div>
        @elseif ($open)
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-700">
                A clearance is already in progress — check the Status tab.
            </div>
        @else
            <form method="POST" action="{{ route('student.services.clearance.store') }}"
                  class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
                @csrf
                <h2 class="font-bold text-slate-800">Start a clearance</h2>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Purpose</label>
                        <select name="purpose" required
                                class="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                            <option value="" disabled selected>Choose…</option>
                            @foreach ($purposes as $purpose)
                                <option value="{{ $purpose }}">{{ $purpose }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Note <span class="normal-case text-slate-400">(optional)</span></label>
                        <input type="text" name="note" maxlength="255"
                               class="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <button type="submit"
                        class="inline-flex rounded-lg bg-indigo-600 px-5 py-2 text-sm font-bold text-white hover:bg-indigo-700">
                    Start clearance
                </button>
            </form>
        @endif
    </div>

    {{-- Status tab --}}
    <div x-show="tab === 'status'" x-cloak class="space-y-4">
        @if ($clearances->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white/60 p-10 text-center text-slate-500">
                No clearance yet — start one from the Request tab.
            </div>
        @else
            @foreach ($clearances as $clearance)
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-5 py-3">
                        <div>
                            <span class="font-bold text-slate-800">{{ $clearance->purpose }}</span>
                            <span class="ml-2 text-xs text-slate-400">{{ $clearance->created_at->format('M j, Y') }}</span>
                        </div>
                        @php
                            $cleared = $clearance->items->where('status', 'cleared')->count();
                            $total = $clearance->items->count();
                            $badge = match ($clearance->status) {
                                'completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                'in_progress' => 'bg-sky-50 text-sky-700 ring-sky-200',
                                default => 'bg-amber-50 text-amber-700 ring-amber-200',
                            };
                        @endphp
                        <span class="inline-flex items-center gap-2 rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $badge }}">
                            {{ str_replace('_', ' ', ucfirst($clearance->status)) }} · {{ $cleared }}/{{ $total }}
                        </span>
                    </div>
                    <ul class="divide-y divide-slate-100">
                        @foreach ($clearance->items as $item)
                            <li class="flex items-center justify-between gap-3 px-5 py-2.5">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-700">{{ $item->label }}</p>
                                    @if ($item->remarks)
                                        <p class="text-xs text-slate-400">{{ $item->remarks }}</p>
                                    @endif
                                </div>
                                @if ($item->status === 'cleared')
                                    <span class="inline-flex shrink-0 items-center gap-1 text-xs font-bold text-emerald-600">
                                        <i data-lucide="check-circle" class="h-4 w-4"></i> Cleared
                                    </span>
                                @elseif ($item->status === 'hold')
                                    <span class="inline-flex shrink-0 items-center gap-1 text-xs font-bold text-rose-600">
                                        <i data-lucide="pause-circle" class="h-4 w-4"></i> On hold
                                    </span>
                                @else
                                    <span class="shrink-0 text-xs font-semibold text-slate-400">Pending</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        @endif
    </div>
</div>
@endsection
