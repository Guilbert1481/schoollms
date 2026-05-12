{{-- Card Battler — game stub --}}
<div data-game="card-battler" class="space-y-4">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="rounded-xl border border-slate-200 bg-rose-50 p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-rose-700">Opponent · HP <span data-role="opp-hp">100</span></div>
            <div class="mt-3 grid grid-cols-3 gap-2">
                @for ($i = 0; $i < 3; $i++)
                    <div class="aspect-[3/4] rounded-lg bg-rose-200 ring-1 ring-rose-300"></div>
                @endfor
            </div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-indigo-50 p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-indigo-700">You · HP <span data-role="you-hp">100</span></div>
            <div class="mt-3 grid grid-cols-3 gap-2">
                @for ($i = 0; $i < 3; $i++)
                    <button type="button" class="aspect-[3/4] rounded-lg bg-white ring-1 ring-indigo-300 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">
                        Answer Card {{ $i + 1 }}
                    </button>
                @endfor
            </div>
        </div>
    </div>
    <p class="text-xs text-slate-500 italic">Stub UI — answering correctly plays the card and deals damage.</p>
</div>
