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

        function categoryCascade({ initial }) {
            return {
                nodes: window.__EDUCATION_NODES__ || [],
                selected: [],
                init() { this.applyInitial(initial ?? null); },
                applyInitial(id) {
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
                onChange(depth, value) {
                    const next = this.selected.slice(0, depth);
                    if (value) next.push(parseInt(value));
                    this.selected = next;
                },
                get finalId() {
                    return this.selected.length ? this.selected[this.selected.length - 1] : '';
                },
                get levels() {
                    const out = [{ options: this.rootOptions(), value: this.selected[0] || '' }];
                    for (let i = 0; i < this.selected.length; i++) {
                        const kids = this.childrenOf(this.selected[i]);
                        if (kids.length === 0) break;
                        out.push({ options: kids, value: this.selected[i + 1] || '' });
                    }
                    return out;
                },
            };
        }
        window.categoryCascade = categoryCascade;

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
                document.getElementById('programEdit_name').value = program.name ?? '';
                const collegeSel = document.getElementById('programEdit_college_id');
                if (collegeSel) collegeSel.value = program.college_id ?? '';
                window.dispatchEvent(new CustomEvent('category-set', {
                    detail: { target: 'edit', id: program.education_node_id ?? null }
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
            <label class="block text-sm mb-1">{{ $programsLabels['name'] ?? 'Program' }}</label>
            <input type="text" name="name" required class="border p-2 w-full rounded">
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

        <div class="mb-3"
             x-data="categoryCascade({ initial: null })"
             x-init="init()">
            <label class="block text-sm mb-1">Program Type / Category</label>
            <template x-for="(level, idx) in levels" :key="idx">
                <select class="border p-2 w-full rounded mb-2"
                        :value="level.value"
                        @change="onChange(idx, $event.target.value)">
                    <option value="" x-text="idx === 0 ? '-- Select Category --' : '-- Select --'"></option>
                    <template x-for="opt in level.options" :key="opt.id">
                        <option :value="opt.id" :selected="opt.id == level.value" x-text="opt.name"></option>
                    </template>
                </select>
            </template>
            <input type="hidden" name="education_node_id" :value="finalId">
            <p class="text-xs text-gray-500 mt-1">Pick the most specific category (e.g. Basic Education &gt; Senior High School &gt; Academic Track &gt; STEM).</p>
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
            <label class="block text-sm mb-1">{{ $programsLabels['name'] ?? 'Program' }}</label>
            <input type="text" id="programEdit_name" name="name" required class="border p-2 w-full rounded">
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

        <div class="mb-3"
             id="programEdit_category_wrap"
             x-data="categoryCascade({ initial: null })"
             x-init="init()"
             @category-set.window="if ($event.detail.target === 'edit') applyInitial($event.detail.id)">
            <label class="block text-sm mb-1">Program Type / Category</label>
            <template x-for="(level, idx) in levels" :key="idx">
                <select class="border p-2 w-full rounded mb-2"
                        :value="level.value"
                        @change="onChange(idx, $event.target.value)">
                    <option value="" x-text="idx === 0 ? '-- Select Category --' : '-- Select --'"></option>
                    <template x-for="opt in level.options" :key="opt.id">
                        <option :value="opt.id" :selected="opt.id == level.value" x-text="opt.name"></option>
                    </template>
                </select>
            </template>
            <input type="hidden" name="education_node_id" :value="finalId">
            <p class="text-xs text-gray-500 mt-1">Pick the most specific category (e.g. Basic Education &gt; Senior High School &gt; Academic Track &gt; STEM).</p>
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
