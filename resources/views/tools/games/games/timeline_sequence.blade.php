{{-- Timeline / Sequence Ordering — game stub --}}
<div data-game="timeline-sequence" class="space-y-4">
    <div class="rounded-xl border border-slate-200 bg-white p-4">
        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-3">Drag into chronological order</div>
        <ol class="space-y-2">
            @foreach (['World War II ends', 'Moon landing', 'Berlin Wall falls', 'Internet goes public', 'Smartphone era'] as $i => $ev)
                <li class="flex items-center gap-3 rounded-lg bg-indigo-50 ring-1 ring-indigo-200 px-3 py-2 cursor-grab">
                    <i data-lucide="grip-vertical" class="w-4 h-4 text-indigo-400"></i>
                    <span class="font-mono text-xs text-indigo-600">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                    <span class="text-sm text-slate-800">{{ $ev }}</span>
                </li>
            @endforeach
        </ol>
    </div>
    <p class="text-xs text-slate-500 italic">Stub UI — supply events; let students reorder, then verify.</p>
</div>
