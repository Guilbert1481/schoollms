{{-- Subjects Tab --}}
<div x-data="{ selected: [], openAssign: false }">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="text-lg font-bold text-slate-800">All Subjects</h2>
            <p class="text-xs text-slate-500">All subjects offered by the school. Filter by program or search below.</p>
        </div>
        <div class="flex gap-2">
            <x-table.table
                tableKey="subjects"
                :columns="[ ['label' => 'Subject', 'key' => 'name'], ['label' => 'Code', 'key' => 'code'], ['label' => 'Units', 'key' => 'units'], ['label' => 'Status', 'key' => 'is_active'] ]"
                :data="$subjects"
                :actions="[]"
            />
            <button class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded shadow text-sm font-semibold disabled:opacity-50" :disabled="selected.length === 0" @click="openAssign = true">
                Assign
            </button>
        </div>
    </div>
    <div class="mb-4">
        <label class="text-sm font-semibold text-slate-700 mr-2">Filter by Program:</label>
        <select class="border rounded px-2 py-1.5 text-sm">
            <option value="">All Programs</option>
            @foreach ($programs as $p)
                <option value="{{ $p->id }}">{{ $p->code }} — {{ $p->name }}</option>
            @endforeach
        </select>
    </div>
    {{-- Table --}}
    <form>
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 border-b text-slate-600 text-xs uppercase tracking-wide">
            <tr>
                <th class="px-2 py-2"><input type="checkbox" @click="selected = $event.target.checked ? {{ $subjects->pluck('id') }} : []"></th>
                <th class="px-4 py-2 text-left">Subject</th>
                <th class="px-4 py-2 text-left">Code</th>
                <th class="px-4 py-2 text-right">Unit</th>
                <th class="px-4 py-2 text-center">Status</th>
                <th class="px-4 py-2 text-center">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($subjects as $s)
            <tr>
                <td class="px-2 py-2 text-center"><input type="checkbox" value="{{ $s->id }}" @change="if($event.target.checked){selected.push({{ $s->id }});}else{selected = selected.filter(i => i !== {{ $s->id }});}"></td>
                <td class="px-4 py-2">{{ $s->name }}</td>
                <td class="px-4 py-2">{{ $s->code }}</td>
                <td class="px-4 py-2 text-right">{{ $s->units ?? '' }}</td>
                <td class="px-4 py-2 text-center">
                    @if ($s->is_active)
                        <span class="inline-block px-2 py-1 text-xs font-semibold bg-green-100 text-green-800 rounded">Active</span>
                    @else
                        <span class="inline-block px-2 py-1 text-xs font-semibold bg-red-100 text-red-800 rounded">Inactive</span>
                    @endif
                </td>
                <td class="px-4 py-2 text-center">
                    <!-- Actions placeholder: add buttons/links here if needed -->
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    </form>
    {{-- Assign Modal --}}
    <div x-show="openAssign" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
        <div class="bg-white rounded shadow-lg p-8 w-full max-w-md relative">
            <button @click="openAssign = false" class="absolute top-2 right-2 text-gray-400 hover:text-gray-600">✕</button>
            <h3 class="text-lg font-bold mb-4">Assign Subjects to Program</h3>
            <form>
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-1">Program</label>
                    <select class="w-full border rounded px-2 py-1.5">
                        @foreach ($programs as $p)
                            <option value="{{ $p->id }}">{{ $p->code }} — {{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-1">Semester</label>
                    <select class="w-full border rounded px-2 py-1.5">
                        <option value="1">1st</option>
                        <option value="2">2nd</option>
                        <option value="3">3rd</option>
                        <option value="4">4th</option>
                        <option value="S">Summer</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-1">Year</label>
                    <select class="w-full border rounded px-2 py-1.5">
                        @for ($y = 1; $y <= 5; $y++)
                            <option value="{{ $y }}">{{ $y }}{{ $y == 1 ? 'st' : ($y == 2 ? 'nd' : ($y == 3 ? 'rd' : 'th')) }} Year</option>
                        @endfor
                    </select>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="openAssign = false" class="px-4 py-2 border rounded">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded">Assign</button>
                </div>
            </form>
        </div>
    </div>
</div>
