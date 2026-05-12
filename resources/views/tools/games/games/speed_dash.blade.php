{{-- The Endless Runner / Speed Dash — game stub --}}
<div data-game="speed-dash" class="space-y-4">
    <div class="rounded-xl border border-slate-200 bg-gradient-to-b from-sky-50 to-white p-4">
        <div class="flex items-center justify-between text-xs">
            <span class="font-semibold text-slate-600">Distance: <span data-role="distance">0</span> m</span>
            <span class="font-semibold text-slate-600">Score: <span data-role="score">0</span></span>
            <span class="font-semibold text-slate-600">Time: <span data-role="time">30</span>s</span>
        </div>
        <div class="relative mt-3 h-32 overflow-hidden rounded-lg bg-gradient-to-r from-cyan-200 to-sky-300">
            <div class="absolute bottom-2 left-4 h-10 w-10 rounded-full bg-white shadow ring-2 ring-cyan-500 flex items-center justify-center">
                <i data-lucide="user" class="w-5 h-5 text-cyan-700"></i>
            </div>
        </div>
        <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2">
            <button type="button" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm hover:bg-cyan-50">Answer A</button>
            <button type="button" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm hover:bg-cyan-50">Answer B</button>
        </div>
    </div>
    <p class="text-xs text-slate-500 italic">Stub UI — connect to a question bank and increment speed per correct answer.</p>
</div>
