<div class="rounded-xl border border-slate-200 bg-white p-4">
    <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
        <h2 class="text-base font-semibold text-slate-800">Timetable</h2>
        <div class="flex items-center gap-3 text-xs">
            <span class="inline-flex items-center gap-1"><span class="inline-block w-3 h-3 rounded bg-emerald-50 border border-emerald-200"></span> Valid</span>
            <span class="inline-flex items-center gap-1"><span class="inline-block w-3 h-3 rounded bg-amber-100 border border-amber-300"></span> Adjusted</span>
            <span class="inline-flex items-center gap-1"><span class="inline-block w-3 h-3 rounded bg-rose-100 border border-rose-300"></span> Conflict</span>
        </div>
        <div class="flex items-center gap-2">
            <select x-model="viewMode" class="rounded border-slate-300 px-2 py-1 text-sm">
                <option value="section">Section View</option>
                <option value="teacher">Teacher View</option>
                <option value="room">Room View</option>
            </select>
            <select x-model="viewEntityId" class="rounded border-slate-300 px-2 py-1 text-sm">
                <option value="">All</option>
                <template x-for="ent in viewEntities()" :key="ent.id">
                    <option :value="ent.id" x-text="ent.name"></option>
                </template>
            </select>
            <button type="button" @click="autoFix()" class="rounded border border-amber-300 bg-amber-50 px-3 py-1 text-sm text-amber-700">Auto-Fix</button>
        </div>
    </div>

    <div x-show="!activeSchedule" class="text-sm text-slate-500 italic">No schedule applied. Generate and apply one from the Create tab.</div>

    <div x-show="activeSchedule" class="overflow-x-auto">
        <table class="min-w-full border border-slate-200 text-xs">
            <thead class="bg-slate-50">
                <tr>
                    <th class="border border-slate-200 p-2">Time</th>
                    <template x-for="d in time.days_of_week" :key="d">
                        <th class="border border-slate-200 p-2 capitalize" x-text="d"></th>
                    </template>
                </tr>
            </thead>
            <tbody>
                <template x-for="row in gridRows()" :key="row.label">
                    <tr style="height: 38px;">
                        <td class="border border-slate-200 p-2 font-mono text-slate-600 whitespace-nowrap" x-text="row.label"></td>
                        <template x-for="d in time.days_of_week" :key="d">
                            <td class="border border-slate-200 p-1 align-top relative" style="min-width: 180px;">
                                <template x-for="(cell, ci) in cellsAt(row, d)" :key="cell.section_id+'-'+cell.subject_id+'-'+cell.start_time">
                                    <div class="rounded p-1 overflow-hidden absolute z-10"
                                         :style="'height: ' + (cell.__span * 38 - 4) + 'px; top: 2px; left: ' + (2 + ci * 4) + 'px; right: ' + (2 + ci * 4) + 'px;'"
                                         :class="cell.status==='conflict' ? 'bg-rose-100 border border-rose-300' :
                                                 cell.status==='adjusted' ? 'bg-amber-100 border border-amber-300' :
                                                                            'bg-emerald-50 border border-emerald-200'"
                                         :title="(cell.conflict_reasons||[]).join(', ')">
                                        <div class="font-semibold text-slate-800 truncate flex items-center gap-1">
                                            <span class="truncate" x-text="cell.subject_name || cell.subject_id"></span>
                                            <template x-if="cell.teacher_id && cell.teacher_is_regular">
                                                <span class="shrink-0 text-[9px] font-bold px-1 rounded bg-sky-200 text-sky-800">FT</span>
                                            </template>
                                        </div>
                                        <div class="text-[10px] text-slate-600 truncate" x-text="(cell.section_name||'') + ' · ' + (cell.teacher_name||'—') + ' · ' + (cell.room_name||'—')"></div>
                                        <div class="text-[10px] text-slate-500" x-text="cell.start_time + '-' + cell.end_time"></div>
                                    </div>
                                </template>
                            </td>
                        </template>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</div>
