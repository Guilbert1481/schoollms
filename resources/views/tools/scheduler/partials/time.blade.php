<div class="rounded-xl border border-slate-200 bg-white p-4">
    <button type="button" @click="toggleAccordion('time')" class="flex w-full items-center justify-between gap-3 text-left">
        <h2 class="text-base font-semibold text-slate-800">Time Settings</h2>
        <i data-lucide="chevron-down" class="h-4 w-4 text-slate-500 transition-transform duration-200" :class="accordionIconClass('time')"></i>
    </button>
    <div x-show="accordion.time" x-transition x-cloak class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
        <div class="sm:col-span-3">
            <span class="text-slate-600 block mb-1">Days of Week</span>
            <div class="flex flex-wrap gap-2">
                <template x-for="d in allDays" :key="d">
                    <label class="inline-flex items-center gap-1 rounded border border-slate-200 px-2 py-1 text-xs capitalize">
                        <input type="checkbox" :value="d" x-model="time.days_of_week"> <span x-text="d"></span>
                    </label>
                </template>
            </div>
        </div>
        <label class="block">
            <span class="text-slate-600">Start Time</span>
            <input type="time" x-model="time.start_time" class="w-full rounded-lg border border-slate-300 px-2 py-1.5">
        </label>
        <label class="block">
            <span class="text-slate-600">End Time</span>
            <input type="time" x-model="time.end_time" class="w-full rounded-lg border border-slate-300 px-2 py-1.5">
        </label>
        <label class="block">
            <span class="text-slate-600">Slot Duration (min)</span>
            <input type="number" min="15" step="15" x-model.number="time.slot_duration" class="w-full rounded-lg border border-slate-300 px-2 py-1.5">
        </label>
        <label class="block">
            <span class="text-slate-600">Break Start</span>
            <input type="time" x-model="time.break_time.start" class="w-full rounded-lg border border-slate-300 px-2 py-1.5">
        </label>
        <label class="block">
            <span class="text-slate-600">Break End</span>
            <input type="time" x-model="time.break_time.end" class="w-full rounded-lg border border-slate-300 px-2 py-1.5">
        </label>
    </div>
</div>
