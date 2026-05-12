{{-- Drag and Drop Category Sorter — game stub --}}
<div data-game="category-sorter" class="space-y-4">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="rounded-xl border-2 border-dashed border-amber-300 bg-amber-50 p-4 min-h-[180px]">
            <div class="text-xs font-semibold uppercase tracking-wide text-amber-700">Category A</div>
        </div>
        <div class="rounded-xl border-2 border-dashed border-orange-300 bg-orange-50 p-4 min-h-[180px]">
            <div class="text-xs font-semibold uppercase tracking-wide text-orange-700">Category B</div>
        </div>
        <div class="rounded-xl border-2 border-dashed border-yellow-300 bg-yellow-50 p-4 min-h-[180px]">
            <div class="text-xs font-semibold uppercase tracking-wide text-yellow-700">Category C</div>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4">
        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Items to sort</div>
        <div class="flex flex-wrap gap-2">
            @foreach (['Apple', 'Carrot', 'Salmon', 'Banana', 'Broccoli', 'Tuna'] as $item)
                <span draggable="true" class="cursor-grab inline-flex items-center px-3 py-1.5 rounded-lg bg-amber-100 text-amber-800 text-sm ring-1 ring-amber-200">{{ $item }}</span>
            @endforeach
        </div>
    </div>
    <p class="text-xs text-slate-500 italic">Stub UI — drag items into the correct category.</p>
</div>
