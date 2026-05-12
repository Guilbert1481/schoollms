{{-- Labeling Diagram Map — game stub --}}
<div data-game="labeling-diagram" class="space-y-4">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 rounded-xl border border-slate-200 bg-teal-50 p-4 min-h-[260px] relative">
            <div class="absolute top-4 left-8 h-3 w-3 rounded-full bg-teal-600 ring-2 ring-white shadow"></div>
            <div class="absolute top-16 right-12 h-3 w-3 rounded-full bg-teal-600 ring-2 ring-white shadow"></div>
            <div class="absolute bottom-10 left-1/2 h-3 w-3 rounded-full bg-teal-600 ring-2 ring-white shadow"></div>
            <div class="text-xs font-semibold uppercase tracking-wide text-teal-700">Diagram (replace with image)</div>
        </div>
        <aside class="rounded-xl border border-slate-200 bg-white p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Labels</div>
            <div class="flex flex-wrap gap-2">
                @foreach (['Nucleus', 'Membrane', 'Mitochondria', 'Ribosome'] as $lbl)
                    <span draggable="true" class="cursor-grab inline-flex items-center px-2.5 py-1 rounded bg-teal-100 text-teal-800 text-sm ring-1 ring-teal-200">{{ $lbl }}</span>
                @endforeach
            </div>
        </aside>
    </div>
    <p class="text-xs text-slate-500 italic">Stub UI — drag labels onto matching hotspots.</p>
</div>
