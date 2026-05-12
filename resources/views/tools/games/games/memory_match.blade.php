{{-- Memory Match Tiles — game stub --}}
<div data-game="memory-match" class="space-y-4">
    <div class="grid grid-cols-4 sm:grid-cols-6 gap-2 max-w-2xl mx-auto">
        @for ($i = 0; $i < 12; $i++)
            <button type="button"
                    class="aspect-square rounded-xl bg-gradient-to-br from-pink-400 to-rose-500 text-white text-xl font-bold ring-1 ring-rose-300 shadow hover:scale-[1.02] transition">
                ?
            </button>
        @endfor
    </div>
    <div class="flex justify-center gap-6 text-sm text-slate-600">
        <div>Moves: <span class="font-semibold" data-role="moves">0</span></div>
        <div>Pairs: <span class="font-semibold" data-role="pairs">0 / 6</span></div>
    </div>
    <p class="text-xs text-slate-500 italic text-center">Stub UI — pair questions with their correct answers.</p>
</div>
