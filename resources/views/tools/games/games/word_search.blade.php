{{-- Word Search — game stub --}}
<div data-game="word-search" class="space-y-4">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 rounded-xl border border-slate-200 bg-white p-4 overflow-x-auto">
            <div class="grid grid-cols-12 gap-0.5 w-fit text-center font-mono text-sm">
                @php $letters = str_split('ZQRTPHOTOSYNTHESISKAERTHMITOCHONDRIAONUCLEUSCYTOPLASMRIBOSOMEDLYSOSOMEACHLOROPLAST'); @endphp
                @for ($i = 0; $i < 144; $i++)
                    <div class="h-7 w-7 flex items-center justify-center bg-emerald-50 ring-1 ring-emerald-100">
                        {{ $letters[$i % count($letters)] }}
                    </div>
                @endfor
            </div>
        </div>
        <aside class="rounded-xl border border-slate-200 bg-white p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Find these words</div>
            <ul class="space-y-1 text-sm">
                <li>PHOTOSYNTHESIS</li>
                <li>MITOCHONDRIA</li>
                <li>NUCLEUS</li>
                <li>CYTOPLASM</li>
                <li>RIBOSOME</li>
            </ul>
        </aside>
    </div>
    <p class="text-xs text-slate-500 italic">Stub UI — replace word list with subject keywords.</p>
</div>
