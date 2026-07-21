@extends('layouts.app')

@section('page-title', 'Student Requests')

@section('content')
<div class="w-full space-y-6" x-data="{ tab: @js($tab) }">

    <div>
        <h1 class="text-2xl font-bold text-slate-800">Student Requests</h1>
        <p class="text-sm text-slate-500">Modality changes and document requests awaiting action.</p>
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
        <button @click="tab = 'modality'"
                :class="tab === 'modality' ? 'bg-white shadow text-slate-800' : 'text-slate-500'"
                class="rounded-lg px-5 py-2 text-sm font-bold transition">
            Modality
            @if ($pending = $modalityRequests->where('status', 'pending')->count())
                <span class="ml-1 rounded-full bg-amber-100 px-1.5 text-xs text-amber-700">{{ $pending }}</span>
            @endif
        </button>
        <button @click="tab = 'documents'"
                :class="tab === 'documents' ? 'bg-white shadow text-slate-800' : 'text-slate-500'"
                class="rounded-lg px-5 py-2 text-sm font-bold transition">
            Documents
            @if ($pendingDocs = $documentRequests->whereIn('status', ['pending', 'processing', 'ready'])->count())
                <span class="ml-1 rounded-full bg-amber-100 px-1.5 text-xs text-amber-700">{{ $pendingDocs }}</span>
            @endif
        </button>
    </div>

    {{-- Modality tab --}}
    <div x-show="tab === 'modality'" x-cloak class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        @if ($modalityRequests->isEmpty())
            <p class="px-5 py-10 text-center text-sm text-slate-400">No modality requests.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[860px] text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <th class="px-4 py-3">Student</th>
                            <th class="px-4 py-3">Change</th>
                            <th class="px-4 py-3">Reason</th>
                            <th class="px-4 py-3">Requested</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($modalityRequests as $req)
                            <tr class="align-top">
                                <td class="px-4 py-3 font-semibold text-slate-700">
                                    {{ trim(($req->student?->user?->first_name ?? '').' '.($req->student?->user?->last_name ?? '')) ?: 'Student #'.$req->student_id }}
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ $req->fromModality?->name ?? '—' }} → <span class="font-semibold">{{ $req->toModality?->name }}</span>
                                </td>
                                <td class="px-4 py-3 text-slate-500">{{ $req->reason ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ $req->created_at->format('M j, g:i A') }}</td>
                                <td class="px-4 py-3">
                                    @php
                                        $badge = match ($req->status) {
                                            'approved' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                            'denied' => 'bg-rose-50 text-rose-700 ring-rose-200',
                                            default => 'bg-amber-50 text-amber-700 ring-amber-200',
                                        };
                                    @endphp
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $badge }}">{{ ucfirst($req->status) }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($req->isPending())
                                        <form method="POST" action="{{ route('registrar.requests.modality.decide', $req) }}"
                                              class="flex items-center justify-end gap-2">
                                            @csrf
                                            @method('PUT')
                                            <input type="text" name="remarks" maxlength="255" placeholder="Remarks (optional)"
                                                   class="w-40 rounded-lg border border-slate-200 bg-slate-50 px-2 py-1.5 text-xs">
                                            <button name="action" value="approve"
                                                    class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-emerald-700">Approve</button>
                                            <button name="action" value="deny"
                                                    class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-bold text-rose-600 hover:bg-rose-50">Deny</button>
                                        </form>
                                    @else
                                        <p class="text-right text-xs text-slate-400">{{ $req->decision_remarks ?? '' }}</p>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Documents tab --}}
    <div x-show="tab === 'documents'" x-cloak class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        @if ($documentRequests->isEmpty())
            <p class="px-5 py-10 text-center text-sm text-slate-400">No document requests.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[860px] text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <th class="px-4 py-3">Student</th>
                            <th class="px-4 py-3">Document</th>
                            <th class="px-4 py-3 text-center">Copies</th>
                            <th class="px-4 py-3">Purpose</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($documentRequests as $req)
                            <tr class="align-top">
                                <td class="px-4 py-3 font-semibold text-slate-700">
                                    {{ trim(($req->student?->user?->first_name ?? '').' '.($req->student?->user?->last_name ?? '')) ?: 'Student #'.$req->student_id }}
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $req->document?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-center text-slate-600">{{ $req->copies }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ $req->purpose }}</td>
                                <td class="px-4 py-3">
                                    @php
                                        $badge = match ($req->status) {
                                            'released' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                            'ready' => 'bg-sky-50 text-sky-700 ring-sky-200',
                                            'processing' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
                                            'denied' => 'bg-rose-50 text-rose-700 ring-rose-200',
                                            default => 'bg-amber-50 text-amber-700 ring-amber-200',
                                        };
                                    @endphp
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $badge }}">{{ ucfirst($req->status) }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    @php
                                        $next = \App\Models\DocumentRequest::TRANSITIONS[$req->status] ?? [];
                                    @endphp
                                    @if (! empty($next))
                                        <form method="POST" action="{{ route('registrar.requests.documents.transition', $req) }}"
                                              class="flex items-center justify-end gap-2">
                                            @csrf
                                            @method('PUT')
                                            @foreach ($next as $action)
                                                <button name="action" value="{{ $action }}"
                                                        class="{{ $action === 'denied'
                                                            ? 'rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-bold text-rose-600 hover:bg-rose-50'
                                                            : 'rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-indigo-700' }}">
                                                    {{ ['processing' => 'Start processing', 'ready' => 'Mark ready', 'released' => 'Release', 'denied' => 'Deny'][$action] }}
                                                </button>
                                            @endforeach
                                        </form>
                                    @else
                                        <p class="text-right text-xs text-slate-400">{{ $req->released_at?->format('M j, g:i A') ?? '' }}</p>
                                    @endif
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
