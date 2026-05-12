{{-- Programs Tab --}}
<div>
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="text-lg font-bold text-slate-800">Programs</h2>
            <p class="text-xs text-slate-500">Select a program to view all its subjects.</p>
        </div>
        <select x-model="selectedProgram" class="border rounded px-2 py-1.5 text-sm">
            <option value="">All Programs</option>
            <template x-for="program in programs" :key="program.id">
                <option :value="program.id" x-text="program.code + ' — ' + program.name"></option>
            </template>
        </select>
    </div>
    <div x-show="selectedProgram">
        <h3 class="text-md font-semibold text-slate-700 mb-2">Subjects for <span x-text="programs.find(p => p.id == selectedProgram)?.name"></span></h3>
        <x-table.table
            tableKey="program_subjects"
            :columns="[ ['label' => 'Subject', 'key' => 'name'], ['label' => 'Code', 'key' => 'code'], ['label' => 'Units', 'key' => 'units'], ['label' => 'Status', 'key' => 'is_active'] ]"
            :data="$programSubjects"
            :actions="[]"
        />
    </div>
</div>
