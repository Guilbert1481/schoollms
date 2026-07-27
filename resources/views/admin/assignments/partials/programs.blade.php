<div class="bg-white p-6 rounded-xl border">

    <h2 class="text-lg font-bold mb-4">Program Assignment</h2>

    @php
        $programsConfig  = config('tables.tables.programs');
        $programsColumns = $programsConfig['columns'] ?? [];
        $programsLabels  = $programsConfig['labels']  ?? [];
    @endphp

    <x-table.table
        tableKey="programs"
        :columns="$programsColumns"
        :data="$programs"
        :actions="config('tables.table-actions.programs')"
        createModal="programCreateModal"
        createLabel="+ New Program"
        deleteRoute="admin.assignments.destroyProgram"
    />

    <script>
        window.__PROGRAMS__ = @json($programs);
        window.__EDUCATION_NODES__ = @json($educationNodes ?? []);

        // Educational Level cascade + Program picker over the Master Data
        // education tree. The deepest pick becomes education_node_id; picking a
        // node in the Program select auto-fills the (still editable) name field
        // unless the admin already customized it.
        function programNodePicker({ initial, initialName }) {
            return {
                nodes: window.__EDUCATION_NODES__ || [],
                selected: [],
                name: '',
                lastAutoName: '',
                init() { this.applyInitial(initial ?? null, initialName ?? ''); },
                applyInitial(id, name) {
                    this.name = name ?? '';
                    this.lastAutoName = id ? this.nodeName(id) : '';
                    if (!id) { this.selected = []; return; }
                    const chain = [];
                    let cur = this.nodes.find(n => n.id == id);
                    while (cur) {
                        chain.unshift(cur.id);
                        cur = cur.parent_id ? this.nodes.find(n => n.id == cur.parent_id) : null;
                    }
                    this.selected = chain;
                },
                childrenOf(id) {
                    return this.nodes.filter(n => n.parent_id === id);
                },
                rootOptions() {
                    return this.nodes.filter(n => n.parent_id === null);
                },
                nodeName(id) {
                    const n = this.nodes.find(x => x.id === id);
                    return n ? n.name : '';
                },
                get finalId() {
                    return this.selected.length ? this.selected[this.selected.length - 1] : '';
                },
                // Chain position rendered as the Program select: the deepest
                // pick when it is a childless non-root, else the next unpicked
                // depth. -1 = nothing picked yet.
                get programDepth() {
                    if (!this.selected.length) return -1;
                    const last = this.selected[this.selected.length - 1];
                    if (this.selected.length > 1 && this.childrenOf(last).length === 0) {
                        return this.selected.length - 1;
                    }
                    return this.selected.length;
                },
                get cascadeLevels() {
                    const depth = this.programDepth === -1 ? 1 : this.programDepth;
                    const out = [];
                    for (let d = 0; d < depth; d++) {
                        out.push({
                            options: d === 0 ? this.rootOptions() : this.childrenOf(this.selected[d - 1]),
                            value: this.selected[d] || '',
                        });
                    }
                    return out;
                },
                get programOptions() {
                    const d = this.programDepth;
                    if (d < 1) return [];
                    return this.childrenOf(this.selected[d - 1]);
                },
                get programValue() {
                    return this.selected[this.programDepth] || '';
                },
                get programHint() {
                    if (!this.selected.length) {
                        return 'Pick an educational level to load its programs from the tree, or type the program name directly.';
                    }
                    if (!this.programOptions.length) {
                        return 'This level has no entries under it in the Education Structure Tree yet - type the program name.';
                    }
                    return 'Picking from the tree fills the name; you can still edit it (e.g. expand to the full title).';
                },
                onCascadeChange(depth, value) {
                    const next = this.selected.slice(0, depth);
                    if (value) next.push(parseInt(value));
                    this.selected = next;
                },
                onProgramPick(value) {
                    const next = this.selected.slice(0, this.programDepth);
                    if (value) {
                        const id = parseInt(value);
                        next.push(id);
                        const picked = this.nodeName(id);
                        if (picked && (this.name === '' || this.name === this.lastAutoName)) {
                            this.name = picked;
                        }
                        if (picked) this.lastAutoName = picked;
                    }
                    this.selected = next;
                },
            };
        }
        window.programNodePicker = programNodePicker;

        function findProgram(id) {
            return (window.__PROGRAMS__ || []).find(p => p.id == id);
        }

        function openProgramModal(modalId, id) {
            const program = findProgram(id);
            if (!program) return;

            if (modalId === 'programEditModal') {
                document.getElementById('programEditForm').action =
                    "{{ url('admin/assignments/programs/info') }}/" + id;
                document.getElementById('programEdit_code').value = program.code ?? '';
                const collegeSel = document.getElementById('programEdit_college_id');
                if (collegeSel) collegeSel.value = program.college_id ?? '';
                // Name is owned by the picker component (auto-fill logic), so it
                // travels with the node id instead of a direct .value write.
                window.dispatchEvent(new CustomEvent('category-set', {
                    detail: { target: 'edit', id: program.education_node_id ?? null, name: program.name ?? '' }
                }));
            }

            if (modalId === 'programAssignModal') {
                document.getElementById('programAssign_program_id').value = id;
                const sel = document.getElementById('programAssign_program_head_id');
                if (sel) sel.value = program.program_head_id ?? '';
                document.getElementById('programAssign_title').textContent =
                    'Assign Program Head to ' + (program.name ?? '');
            }

            openModal(modalId);
        }

        document.addEventListener('DOMContentLoaded', function () {
            const assignUrl = "{{ route('admin.assignments.storeProgramhead') }}";
            const assignForm = document.getElementById('programAssignForm');
            if (assignForm) {
                assignForm.addEventListener('submit', function () {
                    assignForm.action = assignUrl;
                });
            }
        });
    </script>
</div>

{{-- CREATE MODAL --}}
<x-modal.form id="programCreateModal" title="Add New Program" widthClass="w-[480px]">
    <form method="POST" action="{{ route('admin.assignments.createProgram') }}">
        @csrf

        <div class="mb-3">
            <label class="block text-sm mb-1">{{ $programsLabels['code'] ?? 'Code' }}</label>
            <input type="text" name="code" required class="border p-2 w-full rounded">
        </div>

        <div class="mb-3">
            <label class="block text-sm mb-1">{{ $programsLabels['college_id'] ?? 'College' }}</label>
            <select name="college_id" required class="border p-2 w-full rounded">
                <option value="">-- Select College --</option>
                @foreach($colleges as $college)
                    <option value="{{ $college->id }}">{{ $college->name }}</option>
                @endforeach
            </select>
        </div>

        <div x-data="programNodePicker({ initial: null, initialName: '' })" x-init="init()">
            <div class="mb-3">
                <label class="block text-sm mb-1">Educational Level</label>
                <template x-for="(level, idx) in cascadeLevels" :key="idx">
                    <select class="border p-2 w-full rounded mb-2"
                            :value="level.value"
                            @change="onCascadeChange(idx, $event.target.value)">
                        <option value="" x-text="idx === 0 ? '-- Select Educational Level --' : '-- Select --'"></option>
                        <template x-for="opt in level.options" :key="opt.id">
                            <option :value="opt.id" :selected="opt.id == level.value" x-text="opt.name"></option>
                        </template>
                    </select>
                </template>
                <p class="text-xs text-gray-500 mt-1">From Master Data &gt; Education Levels (e.g. Undergraduate, or Basic Education &gt; Senior High School).</p>
            </div>

            <div class="mb-3">
                <label class="block text-sm mb-1">{{ $programsLabels['name'] ?? 'Program' }}</label>
                <template x-if="programOptions.length">
                    <select class="border p-2 w-full rounded mb-2"
                            :value="programValue"
                            @change="onProgramPick($event.target.value)">
                        <option value="">-- Select Program --</option>
                        <template x-for="opt in programOptions" :key="opt.id">
                            <option :value="opt.id" :selected="opt.id == programValue" x-text="opt.name"></option>
                        </template>
                    </select>
                </template>
                <input type="text" name="name" x-model="name" required class="border p-2 w-full rounded">
                <p class="text-xs text-gray-500 mt-1" x-text="programHint"></p>
            </div>

            <input type="hidden" name="education_node_id" :value="finalId">
        </div>
    </form>
</x-modal.form>

{{-- EDIT MODAL --}}
<x-modal.form id="programEditModal" title="Edit Program" widthClass="w-[480px]">
    <form id="programEditForm" method="POST" action="{{ url('admin/assignments/programs/info/0') }}">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="block text-sm mb-1">{{ $programsLabels['code'] ?? 'Code' }}</label>
            <input type="text" id="programEdit_code" name="code" required class="border p-2 w-full rounded">
        </div>

        <div class="mb-3">
            <label class="block text-sm mb-1">{{ $programsLabels['college_id'] ?? 'College' }}</label>
            <select id="programEdit_college_id" name="college_id" required class="border p-2 w-full rounded">
                <option value="">-- Select College --</option>
                @foreach($colleges as $college)
                    <option value="{{ $college->id }}">{{ $college->name }}</option>
                @endforeach
            </select>
        </div>

        <div id="programEdit_category_wrap"
             x-data="programNodePicker({ initial: null, initialName: '' })"
             x-init="init()"
             @category-set.window="if ($event.detail.target === 'edit') applyInitial($event.detail.id, $event.detail.name)">
            <div class="mb-3">
                <label class="block text-sm mb-1">Educational Level</label>
                <template x-for="(level, idx) in cascadeLevels" :key="idx">
                    <select class="border p-2 w-full rounded mb-2"
                            :value="level.value"
                            @change="onCascadeChange(idx, $event.target.value)">
                        <option value="" x-text="idx === 0 ? '-- Select Educational Level --' : '-- Select --'"></option>
                        <template x-for="opt in level.options" :key="opt.id">
                            <option :value="opt.id" :selected="opt.id == level.value" x-text="opt.name"></option>
                        </template>
                    </select>
                </template>
                <p class="text-xs text-gray-500 mt-1">From Master Data &gt; Education Levels (e.g. Undergraduate, or Basic Education &gt; Senior High School).</p>
            </div>

            <div class="mb-3">
                <label class="block text-sm mb-1">{{ $programsLabels['name'] ?? 'Program' }}</label>
                <template x-if="programOptions.length">
                    <select class="border p-2 w-full rounded mb-2"
                            :value="programValue"
                            @change="onProgramPick($event.target.value)">
                        <option value="">-- Select Program --</option>
                        <template x-for="opt in programOptions" :key="opt.id">
                            <option :value="opt.id" :selected="opt.id == programValue" x-text="opt.name"></option>
                        </template>
                    </select>
                </template>
                <input type="text" name="name" x-model="name" required class="border p-2 w-full rounded">
                <p class="text-xs text-gray-500 mt-1" x-text="programHint"></p>
            </div>

            <input type="hidden" name="education_node_id" :value="finalId">
        </div>
    </form>
</x-modal.form>

{{-- ASSIGN PROGRAM HEAD MODAL --}}
<x-modal.form id="programAssignModal" title="Assign Program Head" widthClass="w-[420px]">
    <h2 id="programAssign_title" class="text-base font-semibold mb-3"></h2>

    <form id="programAssignForm" method="POST" action="{{ route('admin.assignments.storeProgramhead') }}">
        @csrf
        <input type="hidden" id="programAssign_program_id" name="program_id">

        <div class="mb-3">
            <label class="block text-sm mb-1">{{ $programsLabels['program_head_id'] ?? 'Program Head' }}</label>
            <select id="programAssign_program_head_id" name="program_head_id" class="border p-2 w-full rounded">
                <option value="">-- Unassign --</option>
                @foreach($programHeads as $head)
                    <option value="{{ $head->id }}">{{ $head->name }}</option>
                @endforeach
            </select>
        </div>
    </form>
</x-modal.form>
