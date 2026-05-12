<div class="rounded-xl border border-slate-200 bg-white p-4">
    <button type="button" @click="toggleAccordion('teacherConstraints')" class="flex w-full items-center justify-between gap-3 text-left">
        <h2 class="text-base font-semibold text-slate-800">Teacher Constraints</h2>
        <i data-lucide="chevron-down" class="h-4 w-4 text-slate-500 transition-transform duration-200" :class="accordionIconClass('teacherConstraints')"></i>
    </button>
    <div x-show="accordion.teacherConstraints" x-transition x-cloak class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
        <div class="sm:col-span-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Full Time</div>
        <label class="block">
            <span class="text-slate-600">Total Teaching Hours per Week</span>
            <input type="number" min="1" x-model.number="teacher_constraints.max_hours_per_week"
                class="w-full rounded-lg border border-slate-300 px-2 py-1.5">
        </label>
        <label class="block">
            <span class="text-slate-600">Max Teaching Hours per Day</span>
            <input type="number" min="1" x-model.number="teacher_constraints.max_hours_per_day"
                class="w-full rounded-lg border border-slate-300 px-2 py-1.5">
        </label>
        <label class="block">
            <span class="text-slate-600">Work Days per Week</span>
            <input type="number" min="1" max="7" x-model.number="teacher_constraints.work_days_per_week"
                class="w-full rounded-lg border border-slate-300 px-2 py-1.5">
        </label>
        <label class="block">
            <span class="text-slate-600">Minimum Teaching Hours per Day</span>
            <input type="number" min="1" step="0.5" x-model.number="teacher_constraints.min_hours_per_day"
                class="w-full rounded-lg border border-slate-300 px-2 py-1.5">
        </label>
        <label class="inline-flex items-center gap-2 mt-2 sm:col-span-3">
            <input type="checkbox" x-model="teacher_constraints.prioritize_full_time" class="rounded border-slate-300">
            <span class="text-slate-700">Prioritize Full-Time Teachers</span>
        </label>
        <div class="mt-2 sm:col-span-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Part Time</div>
        <label class="block">
            <span class="text-slate-600">Part Time Teachers: Minimum hours per day</span>
            <input type="number" min="1" step="0.5" x-model.number="teacher_constraints.part_time_min_hours_per_day"
                class="w-full rounded-lg border border-slate-300 px-2 py-1.5">
        </label>
    </div>
</div>
