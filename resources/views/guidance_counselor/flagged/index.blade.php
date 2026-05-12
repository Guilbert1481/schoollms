@extends('layouts.app')

@section('content')
<div class="mx-auto w-full max-w-7xl space-y-6 p-4 md:p-6">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="shield-alert" class="w-7 h-7 text-rose-600"></i>
                Flagged Interventions
            </h1>
            <p class="text-sm text-slate-600 mt-1">
                Every flagged communication routed to the guidance team for review.
            </p>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="flex flex-wrap items-center gap-2">
        <input type="text" name="q" value="{{ $search }}"
               placeholder="Search message…"
               class="w-full sm:w-72 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-400">

        <select name="level"
                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-400"
                onchange="this.form.submit()">
            @foreach (['all' => 'All severities', 'warning' => 'Warning', 'critical' => 'Critical'] as $val => $label)
                <option value="{{ $val }}" @selected($currentLevel === $val)>{{ $label }}</option>
            @endforeach
        </select>

        <button type="submit"
                class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-500">
            Apply
        </button>

        @if ($search || $currentLevel !== 'all')
            <a href="{{ route('guidance_counselor.flagged.index') }}"
               class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                Reset
            </a>
        @endif

        <span class="ml-auto text-xs text-slate-500">{{ $items->count() }} item(s)</span>
    </form>

    {{-- Table --}}
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="px-4 py-3">Message</th>
                        <th class="px-4 py-3 w-32">Sent Date</th>
                        <th class="px-4 py-3 w-28">Sent Time</th>
                        <th class="px-4 py-3 w-48">Created By</th>
                        <th class="px-4 py-3 w-28">Severity</th>
                        <th class="px-4 py-3 w-20"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($items as $item)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 text-slate-800">
                                <button type="button"
                                        class="text-left hover:text-teal-700 hover:underline"
                                        data-flagged-open
                                        data-source="{{ $item['source'] }}"
                                        data-id="{{ $item['id'] }}">
                                    {{ $item['preview'] }}
                                </button>
                                <div class="mt-0.5 text-[10px] uppercase tracking-wide text-slate-400">
                                    {{ $item['source_label'] }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-700">{{ $item['sent_date'] }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $item['sent_time'] }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $item['created_by'] }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $tone = $item['level'] === 'critical'
                                        ? 'bg-rose-100 text-rose-700 ring-rose-200'
                                        : 'bg-amber-100 text-amber-700 ring-amber-200';
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold ring-1 capitalize {{ $tone }}">
                                    {{ $item['level'] ?? 'flagged' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button type="button"
                                        class="inline-flex items-center gap-1 text-xs font-medium text-teal-700 hover:text-teal-800"
                                        data-flagged-open
                                        data-source="{{ $item['source'] }}"
                                        data-id="{{ $item['id'] }}">
                                    View
                                    <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-sm text-slate-500">
                                No flagged communications.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal --}}
<div id="flaggedModal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4"
     role="dialog" aria-modal="true">
    <div class="w-full max-w-2xl rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-3">
            <h2 class="text-base font-semibold text-slate-800 flex items-center gap-2">
                <i data-lucide="shield-alert" class="w-5 h-5 text-rose-600"></i>
                Flagged Message
            </h2>
            <button type="button" data-flagged-close
                    class="rounded p-1 text-slate-500 hover:bg-slate-100">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div class="px-5 py-4 space-y-3">
            <div class="grid grid-cols-2 gap-3 text-xs">
                <div>
                    <div class="font-semibold text-slate-500 uppercase tracking-wide">Created By</div>
                    <div class="text-sm text-slate-800" data-bind="created_by">—</div>
                </div>
                <div>
                    <div class="font-semibold text-slate-500 uppercase tracking-wide">Severity</div>
                    <div class="text-sm text-slate-800 capitalize" data-bind="level">—</div>
                </div>
                <div>
                    <div class="font-semibold text-slate-500 uppercase tracking-wide">Sent Date</div>
                    <div class="text-sm text-slate-800" data-bind="sent_date">—</div>
                </div>
                <div>
                    <div class="font-semibold text-slate-500 uppercase tracking-wide">Sent Time</div>
                    <div class="text-sm text-slate-800" data-bind="sent_time">—</div>
                </div>
            </div>

            <div>
                <div class="font-semibold text-slate-500 uppercase tracking-wide text-xs">Full Message</div>
                <div class="mt-1 max-h-72 overflow-y-auto whitespace-pre-wrap rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-800"
                     data-bind="message">—</div>
            </div>
        </div>
        <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-3">
            <button type="button" data-flagged-close
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Close
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const modal      = document.getElementById('flaggedModal');
    if (!modal) return;
    const showUrl    = "{{ url('guidance-counselor/flagged') }}";

    const open = async (source, id) => {
        try {
            const res = await fetch(`${showUrl}/${source}/${id}`, {
                headers: { 'Accept': 'application/json' },
            });
            if (!res.ok) throw new Error('Request failed');
            const data = await res.json();

            modal.querySelectorAll('[data-bind]').forEach(el => {
                const key = el.dataset.bind;
                el.textContent = data[key] ?? '—';
            });

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        } catch (err) {
            console.error(err);
            alert('Unable to load flagged message.');
        }
    };

    const close = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };

    document.querySelectorAll('[data-flagged-open]').forEach(btn => {
        btn.addEventListener('click', () => open(btn.dataset.source, btn.dataset.id));
    });

    modal.querySelectorAll('[data-flagged-close]').forEach(btn => {
        btn.addEventListener('click', close);
    });

    modal.addEventListener('click', e => { if (e.target === modal) close(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') close(); });

    if (window.lucide?.createIcons) lucide.createIcons();
})();
</script>
@endpush
@endsection
