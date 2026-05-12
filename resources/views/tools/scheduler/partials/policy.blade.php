<div class="rounded-xl border border-slate-200 bg-white p-4">
    <button type="button" @click="toggleAccordion('policy')" class="flex w-full items-center justify-between gap-3 text-left">
        <h2 class="text-base font-semibold text-slate-800">Subject Policy</h2>
        <i data-lucide="chevron-down" class="h-4 w-4 text-slate-500 transition-transform duration-200" :class="accordionIconClass('policy')"></i>
    </button>
    <div x-show="accordion.policy" x-transition x-cloak class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
        <label class="block">
            <span class="text-slate-600">Min Daily Session Hour</span>
            <input type="number" step="0.5" min="0.5" x-model.number="policy.min_session_hours" class="w-full rounded-lg border border-slate-300 px-2 py-1.5">
        </label>
        <label class="block">
            <span class="text-slate-600">Max Daily Session Hour</span>
            <input type="number" step="0.5" min="0.5" x-model.number="policy.max_session_hours" class="w-full rounded-lg border border-slate-300 px-2 py-1.5">
        </label>
    </div>
</div>
