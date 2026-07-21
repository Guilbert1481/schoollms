@extends('layouts.app')

@section('page-title', 'Modality Request')

@section('content')
<div class="w-full max-w-4xl space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-800">Modality Request</h1>
        <p class="text-sm text-slate-500">Ask the registrar to switch your learning modality.</p>
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

    @if (! $enrollment)
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white/60 p-10 text-center text-slate-500">
            You have no active enrollment this term, so there is nothing to change yet.
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Current modality</p>
                <p class="mt-1 text-xl font-black text-slate-800">{{ $current?->name ?? 'Not set' }}</p>
            </div>
            <div class="rounded-2xl border p-5 shadow-sm {{ $windowOpen ? 'border-emerald-200 bg-emerald-50/50' : 'border-slate-200 bg-white' }}">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Request window</p>
                @if ($windowOpen)
                    <p class="mt-1 text-xl font-black text-emerald-700">Open</p>
                    <p class="text-xs text-slate-500">Closes {{ $deadline?->format('M j, Y · g:i A') }}</p>
                @else
                    <p class="mt-1 text-xl font-black text-slate-500">Closed</p>
                    <p class="text-xs text-slate-500">Requests are accepted only within 2 weeks of enrollment.</p>
                @endif
            </div>
        </div>

        @if ($windowOpen && ! $hasPending)
            <form method="POST" action="{{ route('student.services.modality.store') }}"
                  class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
                @csrf
                <h2 class="font-bold text-slate-800">New request</h2>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Switch to</label>
                        <select name="to_modality_id" required
                                class="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                            <option value="" disabled selected>Choose a modality…</option>
                            @foreach ($options as $option)
                                @continue ((int) $option->id === (int) $enrollment->modality_id)
                                <option value="{{ $option->id }}">{{ $option->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Reason <span class="normal-case text-slate-400">(optional)</span></label>
                        <input type="text" name="reason" maxlength="255" placeholder="Why are you requesting this change?"
                               class="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <button type="submit"
                        class="inline-flex rounded-lg bg-indigo-600 px-5 py-2 text-sm font-bold text-white hover:bg-indigo-700">
                    Submit request
                </button>
            </form>
        @elseif ($hasPending)
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-700">
                You have a pending request — the registrar will review it soon.
            </div>
        @endif

        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-3 font-bold text-slate-800">My requests</div>
            @if ($requests->isEmpty())
                <p class="px-5 py-8 text-center text-sm text-slate-400">No modality requests yet.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[560px] text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <th class="px-5 py-3">Requested</th>
                                <th class="px-5 py-3">Change</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3">Remarks</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($requests as $req)
                                <tr>
                                    <td class="px-5 py-3 text-slate-600">{{ $req->created_at->format('M j, Y') }}</td>
                                    <td class="px-5 py-3 font-semibold text-slate-700">
                                        {{ $req->fromModality?->name ?? '—' }} → {{ $req->toModality?->name }}
                                    </td>
                                    <td class="px-5 py-3">
                                        @php
                                            $badge = match ($req->status) {
                                                'approved' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                                'denied' => 'bg-rose-50 text-rose-700 ring-rose-200',
                                                default => 'bg-amber-50 text-amber-700 ring-amber-200',
                                            };
                                        @endphp
                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $badge }}">{{ ucfirst($req->status) }}</span>
                                    </td>
                                    <td class="px-5 py-3 text-slate-500">{{ $req->decision_remarks ?? $req->reason ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
