{{-- Who Wants to Be a Millionaire? — game stub --}}
<div data-game="millionaire" class="space-y-4">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Question 1 of 15</div>
            <div class="mt-2 text-lg font-semibold text-slate-800">
                Sample question prompt — replace with quiz content from your subject.
            </div>
            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-2">
                <button type="button" class="text-left rounded-lg border border-slate-300 bg-white px-3 py-2 hover:bg-amber-50 hover:border-amber-300">A. Option A</button>
                <button type="button" class="text-left rounded-lg border border-slate-300 bg-white px-3 py-2 hover:bg-amber-50 hover:border-amber-300">B. Option B</button>
                <button type="button" class="text-left rounded-lg border border-slate-300 bg-white px-3 py-2 hover:bg-amber-50 hover:border-amber-300">C. Option C</button>
                <button type="button" class="text-left rounded-lg border border-slate-300 bg-white px-3 py-2 hover:bg-amber-50 hover:border-amber-300">D. Option D</button>
            </div>
        </div>
        <aside class="rounded-xl border border-slate-200 bg-white p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Prize Ladder</div>
            <ol class="space-y-1 text-sm">
                @foreach (['₱1,000,000','₱500,000','₱250,000','₱125,000','₱64,000','₱32,000','₱16,000','₱8,000','₱4,000','₱2,000','₱1,000','₱500','₱300','₱200','₱100'] as $i => $amt)
                    <li class="flex items-center justify-between rounded px-2 py-1 {{ $i === 14 ? 'bg-amber-50 ring-1 ring-amber-200 font-semibold' : '' }}">
                        <span>Q{{ 15 - $i }}</span><span>{{ $amt }}</span>
                    </li>
                @endforeach
            </ol>
            <div class="mt-3 flex gap-2">
                <button type="button" class="flex-1 rounded-lg border border-slate-300 bg-white px-2 py-1 text-xs">50:50</button>
                <button type="button" class="flex-1 rounded-lg border border-slate-300 bg-white px-2 py-1 text-xs">Audience</button>
                <button type="button" class="flex-1 rounded-lg border border-slate-300 bg-white px-2 py-1 text-xs">Phone</button>
            </div>
        </aside>
    </div>
    <p class="text-xs text-slate-500 italic">Stub UI — wire to your quiz bank to begin play.</p>
</div>
