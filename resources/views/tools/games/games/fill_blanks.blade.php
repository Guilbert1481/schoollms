{{-- Fill-in-the-Blanks (Drag-to-Text) — game stub --}}
<div data-game="fill-blanks" class="space-y-4">
    <div class="rounded-xl border border-slate-200 bg-white p-4 text-slate-800 text-base leading-8">
        Photosynthesis converts
        <span class="inline-block min-w-[80px] mx-1 rounded border-b-2 border-emerald-500 bg-emerald-50">&nbsp;</span>
        and
        <span class="inline-block min-w-[80px] mx-1 rounded border-b-2 border-emerald-500 bg-emerald-50">&nbsp;</span>
        into
        <span class="inline-block min-w-[80px] mx-1 rounded border-b-2 border-emerald-500 bg-emerald-50">&nbsp;</span>
        and oxygen.
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-4">
        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Word bank</div>
        <div class="flex flex-wrap gap-2">
            @foreach (['water', 'carbon dioxide', 'glucose'] as $w)
                <span draggable="true" class="cursor-grab inline-flex items-center px-3 py-1.5 rounded-lg bg-emerald-100 text-emerald-800 text-sm ring-1 ring-emerald-200">{{ $w }}</span>
            @endforeach
        </div>
    </div>
    <p class="text-xs text-slate-500 italic">Stub UI — drag word-bank entries into the blanks.</p>
</div>
