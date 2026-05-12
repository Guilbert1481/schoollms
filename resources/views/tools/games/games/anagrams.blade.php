{{-- Scrambled Words / Anagrams — game stub --}}
<div data-game="anagrams" class="space-y-4">
    <div class="rounded-xl border border-slate-200 bg-white p-4">
        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Unscramble the word</div>
        <div class="mt-2 flex flex-wrap gap-2">
            @foreach (str_split('SYNATHHOTSEPI') as $ch)
                <span class="inline-flex items-center justify-center h-10 w-10 rounded-lg bg-rose-100 text-rose-700 font-bold text-lg ring-1 ring-rose-200">{{ $ch }}</span>
            @endforeach
        </div>
        <div class="mt-3 flex gap-2">
            <input type="text" placeholder="Type your guess…"
                   class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-400">
            <button type="button" class="rounded-lg bg-rose-600 text-white px-4 py-2 text-sm hover:bg-rose-500">Submit</button>
        </div>
    </div>
    <p class="text-xs text-slate-500 italic">Stub UI — feed shuffled subject keywords as challenges.</p>
</div>
