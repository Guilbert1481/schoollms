@extends('layouts.app')

@section('page-title', 'Document Request')

@section('content')
<div class="w-full max-w-4xl space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-800">Document Request</h1>
        <p class="text-sm text-slate-500">Request school documents and track them until release.</p>
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

    <form method="POST" action="{{ route('student.services.documents.store') }}"
          class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
        @csrf
        <h2 class="font-bold text-slate-800">New request</h2>

        @if ($catalog->isEmpty())
            <p class="text-sm text-slate-400">No documents are available for request yet — ask your registrar.</p>
        @else
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Document</label>
                    <select name="document_id" required
                            class="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="" disabled selected>Choose a document…</option>
                        @foreach ($catalog as $doc)
                            <option value="{{ $doc->id }}">{{ $doc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Purpose</label>
                    <input type="text" name="purpose" required maxlength="255" placeholder="e.g. Scholarship application"
                           class="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Copies</label>
                    <input type="number" name="copies" value="1" min="1" max="10" required
                           class="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            <button type="submit"
                    class="inline-flex rounded-lg bg-indigo-600 px-5 py-2 text-sm font-bold text-white hover:bg-indigo-700">
                Submit request
            </button>
        @endif
    </form>

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-3 font-bold text-slate-800">My requests</div>
        @if ($requests->isEmpty())
            <p class="px-5 py-8 text-center text-sm text-slate-400">No document requests yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[620px] text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <th class="px-5 py-3">Requested</th>
                            <th class="px-5 py-3">Document</th>
                            <th class="px-5 py-3 text-center">Copies</th>
                            <th class="px-5 py-3">Purpose</th>
                            <th class="px-5 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($requests as $req)
                            <tr>
                                <td class="px-5 py-3 text-slate-600">{{ $req->created_at->format('M j, Y') }}</td>
                                <td class="px-5 py-3 font-semibold text-slate-700">{{ $req->document?->name ?? '—' }}</td>
                                <td class="px-5 py-3 text-center text-slate-600">{{ $req->copies }}</td>
                                <td class="px-5 py-3 text-slate-500">{{ $req->purpose }}</td>
                                <td class="px-5 py-3">
                                    @php
                                        $badge = match ($req->status) {
                                            'released' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                            'ready' => 'bg-sky-50 text-sky-700 ring-sky-200',
                                            'processing' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
                                            'denied' => 'bg-rose-50 text-rose-700 ring-rose-200',
                                            default => 'bg-amber-50 text-amber-700 ring-amber-200',
                                        };
                                        $label = $req->status === 'ready' ? 'Ready for pickup' : ucfirst($req->status);
                                    @endphp
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $badge }}">{{ $label }}</span>
                                    @if ($req->remarks)
                                        <div class="mt-1 text-xs text-slate-400">{{ $req->remarks }}</div>
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
