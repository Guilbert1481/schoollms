{{-- Cryptogram — game stub --}}
<div data-game="cryptogram" class="space-y-4">
    <div class="rounded-xl border border-slate-200 bg-white p-4">
        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Cipher</div>
        <div class="mt-2 font-mono text-lg tracking-wider text-slate-800">XLI UYMGO FVSAR JSB</div>
        <div class="mt-3 grid grid-cols-13 gap-1 max-w-2xl">
            @foreach (range('A','Z') as $i => $letter)
                <div class="text-center">
                    <input type="text" maxlength="1"
                           class="w-7 h-7 rounded border border-slate-300 text-center text-sm uppercase focus:outline-none focus:ring-1 focus:ring-violet-400">
                    <div class="text-[10px] text-slate-500 mt-0.5">{{ $letter }}</div>
                </div>
            @endforeach
        </div>
    </div>
    <p class="text-xs text-slate-500 italic">Stub UI — substitution-cipher message decoder.</p>
</div>
