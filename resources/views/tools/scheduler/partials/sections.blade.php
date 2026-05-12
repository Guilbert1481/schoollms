<div class="rounded-xl border border-slate-200 bg-white p-4">
    <button type="button" @click="toggleAccordion('sections')" class="flex w-full items-center justify-between gap-3 text-left">
        <div class="flex items-center gap-3">
            <h2 class="text-base font-semibold text-slate-800">Sections, Subjects &amp; Resources</h2>
            <span class="text-xs text-slate-500">
                <span x-text="sections.length"></span> sections ·
                <span x-text="teachers.length"></span> teachers ·
                <span x-text="rooms.length"></span> rooms
            </span>
        </div>
        <i data-lucide="chevron-down" class="h-4 w-4 text-slate-500 transition-transform duration-200" :class="accordionIconClass('sections')"></i>
    </button>

    <div x-show="accordion.sections" x-transition x-cloak class="mt-3">
        <div x-show="sections.length === 0" class="text-sm text-slate-500 italic">
            No active sections found. Create sections first.
        </div>

        <div class="space-y-3 max-h-[36rem] overflow-y-auto pr-1">
            <template x-for="section in sections" :key="section.id">
                <div class="rounded-lg border border-slate-200 p-3">
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="flex-1 min-w-[200px]">
                            <div class="font-semibold text-slate-800" x-text="section.name"></div>
                            <div class="text-xs text-slate-500">
                                Size: <input type="number" min="1" x-model.number="section.size" class="w-16 rounded border border-slate-300 px-1 text-xs"> students
                            </div>
                            <div class="mt-1 inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700">
                                <span>Total Weekly Hours:</span>
                                <span x-text="totalWeeklyHoursForSection(section) + ' hr'"></span>
                            </div>
                            <div x-show="sectionCapacityWarning(section)" x-cloak class="mt-1 flex items-start gap-1 rounded bg-rose-50 px-2 py-1 text-xs text-rose-700">
                                <i data-lucide="alert-triangle" class="h-3.5 w-3.5 mt-0.5 flex-shrink-0"></i>
                                <span x-text="sectionCapacityWarning(section)"></span>
                            </div>
                        </div>
                        <label class="text-sm">
                            <span class="text-slate-600 mr-1">Block:</span>
                            <select x-model="section.block" class="rounded border-slate-300 px-2 py-1 text-sm">
                                <option value="auto">Auto</option>
                                <option value="morning">Morning</option>
                                <option value="afternoon">Afternoon</option>
                            </select>
                        </label>
                    </div>

                    <div class="mt-2">
                        <div class="text-xs text-slate-600 mb-1">Subjects (input hours, leave blank to auto-compute from units)</div>
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                            <template x-for="(sub, sIdx) in section.subjects" :key="sub.id">
                                <div class="flex items-center gap-2 rounded border border-slate-200 px-2 py-1 text-xs">
                                    <span class="flex-1">
                                        <span x-text="sub.name"></span>
                                        <span x-show="sub.is_lab" class="ml-1 inline-flex items-center rounded bg-indigo-100 px-1.5 text-[10px] text-indigo-700">LAB</span>
                                        <span x-show="sub.source === 'program_subjects'" class="ml-1 inline-flex items-center rounded bg-sky-100 px-1.5 text-[10px] text-sky-700" title="Auto-loaded from program_subjects">AUTO</span>
                                        <span x-show="sub.preferred_room_id" class="ml-1 inline-flex items-center rounded bg-purple-100 px-1.5 text-[10px] text-purple-700"
                                              :title="'Preferred room: ' + (roomName(sub.preferred_room_id) || sub.preferred_room_id)">
                                            ROOM: <span class="ml-1" x-text="roomName(sub.preferred_room_id) || ('#' + sub.preferred_room_id)"></span>
                                        </span>
                                    </span>
                                    <input type="number" step="0.5" min="0" placeholder="hrs" x-model.number="sub.hours" class="w-16 rounded border border-slate-300 px-1 py-0.5">
                                    <button type="button" class="text-rose-600" @click="section.subjects.splice(sIdx, 1)">x</button>
                                </div>
                            </template>
                        </div>

                        <div class="mt-2 flex items-center gap-2">
                            <select x-model="section._addSubjectId" class="rounded border-slate-300 px-2 py-1 text-xs">
                                <option value="">+ add subject</option>
                                <template x-for="s in availableSubjectsFor(section)" :key="s.id">
                                    <option :value="s.id" x-text="(s.code ? s.code + ' · ' : '') + s.name"></option>
                                </template>
                            </select>
                            <button type="button" class="rounded bg-slate-100 px-2 py-1 text-xs" @click="addSubjectToSection(section)">Add</button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
