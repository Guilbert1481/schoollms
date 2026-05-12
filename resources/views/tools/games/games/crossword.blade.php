{{-- Crossword Puzzle — game stub --}}
<div data-game="crossword" class="space-y-4">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 rounded-xl border border-slate-200 bg-white p-4 overflow-x-auto">
            <div class="grid grid-cols-10 gap-0.5 w-fit">
                @for ($i = 0; $i < 100; $i++)
                    @php $blocked = in_array($i % 7, [3, 5]); @endphp
                    <div class="h-8 w-8 flex items-center justify-center text-xs font-bold {{ $blocked ? 'bg-slate-800' : 'bg-blue-50 ring-1 ring-blue-200' }}"></div>
                @endfor
            </div>
        </div>
        <aside class="rounded-xl border border-slate-200 bg-white p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Clues</div>
            <div class="text-sm">
                <div class="font-semibold text-slate-700">Across</div>
                <ol class="list-decimal pl-5 text-slate-600 space-y-0.5">
                    <li>Sample across clue</li>
                </ol>
                <div class="font-semibold text-slate-700 mt-3">Down</div>
                <ol class="list-decimal pl-5 text-slate-600 space-y-0.5">
                    <li>Sample down clue</li>
                </ol>
            </div>
        </aside>
    </div>
    <p class="text-xs text-slate-500 italic">Stub UI — feed terms + clues from your subject vocabulary.</p>
</div>
