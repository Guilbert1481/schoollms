{{-- Hangman — game stub --}}
<div data-game="hangman" class="space-y-4">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 flex items-center justify-center min-h-[220px]">
            <svg viewBox="0 0 200 200" class="h-48 w-48 text-slate-700" stroke="currentColor" fill="none" stroke-width="3">
                <line x1="20" y1="180" x2="100" y2="180"/>
                <line x1="60" y1="180" x2="60" y2="20"/>
                <line x1="60" y1="20" x2="140" y2="20"/>
                <line x1="140" y1="20" x2="140" y2="50"/>
                <circle cx="140" cy="65" r="15"/>
            </svg>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Word</div>
            <div class="mt-2 text-2xl tracking-[0.5em] font-mono text-slate-800">_ _ _ _ _ _</div>

            <div class="mt-4 flex flex-wrap gap-1">
                @foreach (range('A','Z') as $letter)
                    <button type="button" class="h-8 w-8 rounded bg-slate-100 hover:bg-slate-700 hover:text-white text-sm font-semibold">{{ $letter }}</button>
                @endforeach
            </div>
            <div class="mt-3 text-xs text-slate-600">Misses left: <span class="font-semibold" data-role="misses">6</span></div>
        </div>
    </div>
    <p class="text-xs text-slate-500 italic">Stub UI — feed word + hint pairs from your subject.</p>
</div>
