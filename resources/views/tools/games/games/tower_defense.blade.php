{{-- Tower Defense / Base Builder — game stub --}}
<div data-game="tower-defense" class="space-y-4">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 rounded-xl border border-slate-200 bg-emerald-50 p-4 min-h-[260px] relative">
            <div class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Battlefield</div>
            <div class="grid grid-cols-8 gap-1 mt-3">
                @for ($i = 0; $i < 32; $i++)
                    <div class="aspect-square rounded bg-white/70 ring-1 ring-emerald-200"></div>
                @endfor
            </div>
        </div>
        <aside class="rounded-xl border border-slate-200 bg-white p-4 space-y-3">
            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Wave</div>
                <div class="text-2xl font-bold text-slate-800" data-role="wave">1</div>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Coins</div>
                <div class="text-2xl font-bold text-emerald-600" data-role="coins">100</div>
            </div>
            <button type="button" class="w-full rounded-lg bg-emerald-600 text-white text-sm py-2 hover:bg-emerald-500">Answer to Build Tower</button>
        </aside>
    </div>
    <p class="text-xs text-slate-500 italic">Stub UI — each correct answer awards coins to place a tower.</p>
</div>
