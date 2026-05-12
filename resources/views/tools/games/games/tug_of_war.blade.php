{{-- Tug-of-War (The Momentum Battle) — game stub --}}
<div data-game="tug-of-war" class="space-y-4">
    <div class="rounded-xl border border-slate-200 bg-white p-4">
        <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wide">
            <span class="text-rose-700">Team Red</span>
            <span class="text-blue-700">Team Blue</span>
        </div>
        <div class="relative mt-3 h-3 rounded-full bg-slate-100 overflow-hidden ring-1 ring-slate-200">
            <div class="absolute inset-y-0 left-0 w-1/2 bg-gradient-to-r from-rose-500 to-rose-300"></div>
            <div class="absolute inset-y-0 right-0 w-1/2 bg-gradient-to-l from-blue-500 to-blue-300"></div>
            <div class="absolute inset-y-0 left-1/2 w-0.5 -translate-x-1/2 bg-slate-700"></div>
        </div>
        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-2">
            <button type="button" class="rounded-lg bg-rose-600 text-white py-2 text-sm hover:bg-rose-500">Red answers correctly</button>
            <button type="button" class="rounded-lg bg-blue-600 text-white py-2 text-sm hover:bg-blue-500">Blue answers correctly</button>
        </div>
    </div>
    <p class="text-xs text-slate-500 italic">Stub UI — each correct answer pulls the rope toward the answering team.</p>
</div>
