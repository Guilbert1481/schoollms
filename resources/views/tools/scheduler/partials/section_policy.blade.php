<div class="rounded-xl border border-slate-200 bg-white p-4">
    <button type="button" @click="toggleAccordion('sectionPolicy')" class="flex w-full items-center justify-between gap-3 text-left">
        <h2 class="text-base font-semibold text-slate-800">Section Policy</h2>
        <i data-lucide="chevron-down" class="h-4 w-4 text-slate-500 transition-transform duration-200" :class="accordionIconClass('sectionPolicy')"></i>
    </button>
    <div x-show="accordion.sectionPolicy" x-transition x-cloak class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
        <label class="block">
            <span class="text-slate-600">Minimum Daily Hours</span>
            <input type="number" step="0.5" min="0" x-model.number="section_policy.min_hours_per_day"
                class="w-full rounded-lg border border-slate-300 px-2 py-1.5">
        </label>
        <label class="block">
            <span class="text-slate-600">Maximum Daily Hours</span>
            <input type="number" step="0.5" min="0" x-model.number="section_policy.max_hours_per_day"
                class="w-full rounded-lg border border-slate-300 px-2 py-1.5">
        </label>
        <label class="block">
            <span class="text-slate-600">Min Subjects/Day</span>
            <input type="number" min="0" x-model.number="section_policy.min_subjects_per_day"
                class="w-full rounded-lg border border-slate-300 px-2 py-1.5">
        </label>
        <label class="block">
            <span class="text-slate-600">Max Subjects/Day</span>
            <input type="number" min="1" x-model.number="section_policy.max_subjects_per_day"
                class="w-full rounded-lg border border-slate-300 px-2 py-1.5">
        </label>
        <label class="block">
            <span class="text-slate-600">Min Days/Week (per Section)</span>
            <input type="number" min="1" max="7" x-model.number="section_policy.min_days_per_week"
                class="w-full rounded-lg border border-slate-300 px-2 py-1.5">
        </label>
        <label class="block">
            <span class="text-slate-600">Max Days/Week (per Section)</span>
            <input type="number" min="1" max="7" x-model.number="section_policy.max_days_per_week"
                class="w-full rounded-lg border border-slate-300 px-2 py-1.5">
        </label>
        <label class="block">
            <span class="text-slate-600">Max Allowed Gap (min/day)</span>
            <input type="number" step="5" min="0" x-model.number="section_policy.max_allowed_gap"
                class="w-full rounded-lg border border-slate-300 px-2 py-1.5">
        </label>
        <label class="inline-flex items-center gap-2 mt-2 sm:col-span-3">
            <input type="checkbox" x-model="section_policy.allow_gaps" class="rounded border-slate-300">
            <span class="text-slate-700">Allow gaps between sessions</span>
        </label>
    </div>
</div>
