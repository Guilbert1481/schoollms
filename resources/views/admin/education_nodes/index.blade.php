@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto p-6 space-y-6">

    @include('school.settings.partials.master-data._header')

    @include('partials.tabs', [
        'tabs' => config('tabs.tabs.master_data')
    ])

    @if (session('status'))
        <div class="rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-2 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- LEFT — TREE --}}
        <div class="lg:col-span-2 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Education Structure Tree</h2>
                    <p class="text-xs text-slate-500">
                        <span class="inline-block w-3 h-3 align-middle rounded-sm bg-emerald-500"></span>
                        Checked = Offered by the school
                    </p>
                </div>
                <div class="flex gap-2">
                    <button type="button" data-action="collapse-all"
                        class="px-3 py-1.5 text-xs rounded-lg border border-slate-300 bg-white hover:bg-slate-50">
                        Collapse All
                    </button>
                    <button type="button" data-action="expand-all"
                        class="px-3 py-1.5 text-xs rounded-lg border border-slate-300 bg-white hover:bg-slate-50">
                        Expand All
                    </button>
                    <button type="button" data-action="add-root"
                        class="px-3 py-1.5 text-xs rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">
                        + Add Root
                    </button>
                </div>
            </div>

            <ul class="space-y-1">
                @foreach ($tree as $node)
                    @include('admin.education_nodes.partials.node', ['node' => $node, 'depth' => 0, 'programsByNode' => $programsByNode ?? collect()])
                @endforeach
            </ul>

            @if ($tree->isEmpty())
                <div class="text-center text-sm text-slate-500 py-10">
                    No nodes yet. Click <strong>+ Add Root</strong> to begin.
                </div>
            @endif
        </div>

        {{-- RIGHT — ABOUT --}}
        <aside class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm h-fit">
            <div class="flex items-center gap-2 mb-3">
                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold">i</span>
                <h3 class="font-bold text-slate-900">About</h3>
            </div>
            <p class="text-sm text-slate-600 mb-4">
                Organize all educational levels and program types in a hierarchical structure.
                Check the box if the school offers the item. Only checked items will appear in
                the enrollment form.
            </p>

            <h4 class="text-sm font-bold text-slate-900 mb-2">Node Types</h4>
            <ul class="space-y-2 text-xs text-slate-600">
                <li class="flex gap-2"><span class="px-2 py-0.5 rounded bg-blue-100 text-blue-700 font-mono">level</span><span>– Main educational level (e.g., Basic Education)</span></li>
                <li class="flex gap-2"><span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-700 font-mono">stage</span><span>– Sub level or stage (e.g., Elementary, JHS)</span></li>
                <li class="flex gap-2"><span class="px-2 py-0.5 rounded bg-amber-100 text-amber-700 font-mono">track</span><span>– Track or specialization (e.g., TVL Track)</span></li>
                <li class="flex gap-2"><span class="px-2 py-0.5 rounded bg-violet-100 text-violet-700 font-mono">strand</span><span>– Strand under a track (e.g., ICT, STEM)</span></li>
                <li class="flex gap-2"><span class="px-2 py-0.5 rounded bg-teal-100 text-teal-700 font-mono">program_type</span><span>– Program or degree type (e.g., Master’s Degree)</span></li>
            </ul>
        </aside>
    </div>

    {{-- CREATE / EDIT MODAL --}}
    <div id="nodeModal" class="hidden fixed inset-0 bg-black/40 items-center justify-center z-50"
         data-action="modal-backdrop">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-5"
             data-modal-box>
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-slate-900" id="modalTitle">Add New Node</h3>
                <button type="button" data-action="close-modal" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>

            <div class="space-y-3">
                <div id="parentRow" class="hidden">
                    <label class="block text-xs font-medium text-slate-700 mb-1">Parent</label>
                    <input type="text" id="parentInput" disabled
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-slate-50 text-sm">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Name <span class="text-red-500">*</span></label>
                    <input type="text" id="nameInput" placeholder="Enter node name"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Node Type <span class="text-red-500">*</span></label>
                    <select id="nodeTypeInput"
                            class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white">
                        <option value="">Select node type</option>
                        @foreach ($nodeTypes as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Order Index</label>
                    <input type="number" id="orderInput" value="0"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-5">
                <button type="button" data-action="close-modal"
                        class="px-4 py-2 rounded-lg border border-slate-300 text-sm hover:bg-slate-50">
                    Cancel
                </button>
                <button type="button" data-action="save"
                        class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">
                    Save
                </button>
            </div>
        </div>
    </div>

    {{-- ADD PROGRAM MODAL (creates a row in the `programs` table) --}}
    <div id="programModal" class="hidden fixed inset-0 bg-black/40 items-center justify-center z-50"
         data-action="program-backdrop">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-5" data-modal-box>
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-slate-900">Add Program</h3>
                <button type="button" data-action="close-program" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>

            <div class="space-y-3">
                <p class="text-xs text-slate-500">Under <span class="font-semibold text-slate-700" id="programParentName">—</span></p>

                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Program Code <span class="text-red-500">*</span></label>
                    <input type="text" id="progCode" placeholder="e.g. BSED - Math"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Program Name <span class="text-red-500">*</span></label>
                    <input type="text" id="progName" placeholder="e.g. Bachelor of Secondary Education major in Mathematics"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">College <span class="text-red-500">*</span></label>
                    <select id="progCollege"
                            class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white">
                        @foreach ($colleges as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                    <button type="button" data-action="toggle-add-college"
                            class="mt-1 text-xs font-semibold text-indigo-600 hover:underline">+ New college</button>

                    {{-- Inline college creation — auto-shown when the school has no college yet. --}}
                    <div id="addCollegeRow" class="mt-2 hidden space-y-2 rounded-lg border border-slate-200 bg-slate-50 p-3">
                        <p class="text-xs text-slate-500">No college yet — add one to attach this program to.</p>
                        <input type="text" id="newCollegeCode" placeholder="College code (e.g. CTE)"
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                        <input type="text" id="newCollegeName" placeholder="College name (e.g. College of Teacher Education)"
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                        <button type="button" data-action="save-college"
                                class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700">
                            Add College
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-5">
                <button type="button" data-action="close-program"
                        class="px-4 py-2 rounded-lg border border-slate-300 text-sm hover:bg-slate-50">Cancel</button>
                <button type="button" data-action="save-program"
                        class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">Save</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const base       = "{{ url('admin/education-nodes') }}";
    const progBase   = base + '/programs';
    const modal      = document.getElementById('nodeModal');
    const modalTitle = document.getElementById('modalTitle');
    const parentRow  = document.getElementById('parentRow');
    const parentInp  = document.getElementById('parentInput');
    const nameInp    = document.getElementById('nameInput');
    const typeInp    = document.getElementById('nodeTypeInput');
    const orderInp   = document.getElementById('orderInput');

    // Add Program modal elements.
    const progModal      = document.getElementById('programModal');
    const progCodeInp    = document.getElementById('progCode');
    const progNameInp    = document.getElementById('progName');
    const progCollege    = document.getElementById('progCollege');
    const progParentEl   = document.getElementById('programParentName');
    const addCollegeRow  = document.getElementById('addCollegeRow');
    const newCollegeCode = document.getElementById('newCollegeCode');
    const newCollegeName = document.getElementById('newCollegeName');

    let state = { isEdit: false, editingId: null, programCode: '' };

    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content
        || document.querySelector('input[name="_token"]')?.value;

    async function req(url, method, body) {
        const res = await fetch(url, {
            method,
            headers: {
                'Content-Type':     'application/json',
                'Accept':           'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN':     csrf(),
            },
            body: body ? JSON.stringify(body) : null,
        });
        if (!res.ok) throw new Error(await res.text());
        return res.json();
    }

    function openModal() {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function openCreate(parentId, parentName) {
        state = { isEdit: false, editingId: null, programCode: '' };
        modalTitle.textContent = 'Add New Node';
        if (parentName) {
            parentRow.classList.remove('hidden');
            parentInp.value = parentName;
        } else {
            parentRow.classList.add('hidden');
            parentInp.value = '';
        }
        nameInp.value  = '';
        typeInp.value  = '';
        orderInp.value = 0;
        // Stash parent id on the modal so save() can read it.
        modal.dataset.parentId = parentId == null ? '' : String(parentId);
        openModal();
    }

    function openEdit(id, name, nodeType, orderIndex) {
        state = { isEdit: true, editingId: id, programCode: '' };
        modalTitle.textContent = 'Edit Node';
        parentRow.classList.add('hidden');
        parentInp.value = '';
        nameInp.value   = name || '';
        typeInp.value   = nodeType || '';
        orderInp.value  = orderIndex || 0;
        modal.dataset.parentId = '';
        openModal();
    }

    function openEditProgram(id, name, code) {
        state = { isEdit: true, editingId: 'program:' + id, programCode: code || '' };
        modalTitle.textContent = 'Edit Program';
        parentRow.classList.add('hidden');
        parentInp.value = '';
        nameInp.value   = name || '';
        typeInp.value   = 'program_type';
        orderInp.value  = 0;
        modal.dataset.parentId = '';
        openModal();
    }

    async function save() {
        const name = nameInp.value.trim();
        const type = typeInp.value;
        if (!name || !type) { alert('Name and Node Type are required.'); return; }

        const order = parseInt(orderInp.value, 10) || 0;

        try {
            if (state.isEdit && typeof state.editingId === 'string' && state.editingId.startsWith('program:')) {
                const pid = state.editingId.slice('program:'.length);
                await req(`${progBase}/${pid}`, 'PUT', { name, code: state.programCode });
            } else if (state.isEdit) {
                await req(`${base}/${state.editingId}`, 'PUT', {
                    name, node_type: type, order_index: order,
                });
            } else {
                const parentId = modal.dataset.parentId
                    ? parseInt(modal.dataset.parentId, 10)
                    : null;
                await req(base, 'POST', {
                    name, parent_id: parentId, node_type: type, order_index: order,
                });
            }
            location.reload();
        } catch (e) {
            console.error(e);
            alert('Failed to save.');
        }
    }

    async function toggleOffered(id, checked) {
        try { await req(`${base}/${id}/toggle-offered`, 'POST', { is_offered: checked }); }
        catch (e) { console.error(e); alert('Failed to update.'); location.reload(); }
    }
    async function destroy(id) {
        if (!confirm('Delete this node and ALL its descendants?')) return;
        try { await req(`${base}/${id}`, 'DELETE'); location.reload(); }
        catch (e) { console.error(e); alert('Failed to delete.'); }
    }
    async function toggleProgram(id, checked) {
        try { await req(`${progBase}/${id}/toggle-offered`, 'POST', { is_offered: checked }); }
        catch (e) { console.error(e); alert('Failed to update program.'); location.reload(); }
    }
    async function destroyProgram(id) {
        if (!confirm('Delete this program?')) return;
        try { await req(`${progBase}/${id}`, 'DELETE'); location.reload(); }
        catch (e) { console.error(e); alert('Failed to delete program.'); }
    }

    // Pull a human-readable message out of a thrown req() error (its message is
    // the raw JSON response body).
    function errMessage(e, fallback) {
        try {
            const j = JSON.parse(e.message);
            if (j.message) return j.message;
            if (j.errors) return Object.values(j.errors)[0][0];
        } catch (_) {}
        return fallback;
    }

    function openAddProgram(nodeId, nodeName) {
        progModal.dataset.nodeId = String(nodeId);
        progParentEl.textContent = nodeName || '';
        progCodeInp.value = '';
        progNameInp.value = '';
        // No colleges yet? Force the inline "add college" open so the program
        // has something to attach to.
        addCollegeRow.classList.toggle('hidden', progCollege.options.length > 0);
        progModal.classList.remove('hidden');
        progModal.classList.add('flex');
    }
    function closeAddProgram() {
        progModal.classList.add('hidden');
        progModal.classList.remove('flex');
    }
    async function saveProgram() {
        const code = progCodeInp.value.trim();
        const name = progNameInp.value.trim();
        const collegeId = progCollege.value ? parseInt(progCollege.value, 10) : null;
        const nodeId = parseInt(progModal.dataset.nodeId, 10);
        if (!code || !name) { alert('Program code and name are required.'); return; }
        if (!collegeId)     { alert('Please select or add a college first.'); return; }
        try {
            await req(progBase, 'POST', {
                code, name, college_id: collegeId, education_node_id: nodeId,
            });
            location.reload();
        } catch (e) { console.error(e); alert(errMessage(e, 'Failed to add program.')); }
    }
    async function saveCollege() {
        const code = newCollegeCode.value.trim();
        const name = newCollegeName.value.trim();
        if (!code || !name) { alert('College code and name are required.'); return; }
        try {
            const res = await req(`${base}/colleges`, 'POST', { code, name });
            const opt = document.createElement('option');
            opt.value = res.college.id;
            opt.textContent = res.college.name;
            progCollege.appendChild(opt);
            progCollege.value = res.college.id;
            addCollegeRow.classList.add('hidden');
            newCollegeCode.value = '';
            newCollegeName.value = '';
        } catch (e) { console.error(e); alert(errMessage(e, 'Failed to add college.')); }
    }

    function expandAll() {
        document.querySelectorAll('[data-tree-children]').forEach(el => el.classList.remove('hidden'));
        document.querySelectorAll('button[data-tree-toggle]').forEach(el => {
            el.dataset.open = '1'; el.textContent = '▾';
        });
    }
    function collapseAll() {
        document.querySelectorAll('[data-tree-children]').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('button[data-tree-toggle]').forEach(el => {
            el.dataset.open = '0'; el.textContent = '▸';
        });
    }

    // Single delegated click handler for all data-action buttons.
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-action]');
        if (!btn) return;
        const action = btn.dataset.action;

        switch (action) {
            case 'add-root':       openCreate(null, null); break;
            case 'add-child':      openCreate(parseInt(btn.dataset.parentId, 10), btn.dataset.parentName); break;
            case 'edit-node':      openEdit(parseInt(btn.dataset.id, 10), btn.dataset.name, btn.dataset.nodeType, parseInt(btn.dataset.orderIndex, 10) || 0); break;
            case 'edit-program':   openEditProgram(parseInt(btn.dataset.id, 10), btn.dataset.name, btn.dataset.code); break;
            case 'delete-node':    destroy(parseInt(btn.dataset.id, 10)); break;
            case 'delete-program': destroyProgram(parseInt(btn.dataset.id, 10)); break;
            case 'expand-all':     expandAll(); break;
            case 'collapse-all':   collapseAll(); break;
            case 'add-program':    openAddProgram(parseInt(btn.dataset.nodeId, 10), btn.dataset.nodeName); break;
            case 'close-program':  closeAddProgram(); break;
            case 'save-program':   saveProgram(); break;
            case 'save-college':   saveCollege(); break;
            case 'toggle-add-college': addCollegeRow.classList.toggle('hidden'); break;
            case 'close-modal':    closeModal(); break;
            case 'save':           save(); break;
            case 'modal-backdrop':
                // Only close if the click landed on the backdrop itself (not the box).
                if (e.target === btn) closeModal();
                break;
            case 'program-backdrop':
                if (e.target === btn) closeAddProgram();
                break;
        }
    });

    // Checkbox toggles use change event.
    document.addEventListener('change', (e) => {
        const cb = e.target;
        if (!(cb instanceof HTMLInputElement) || cb.type !== 'checkbox') return;
        if (cb.dataset.action === 'toggle-offered') {
            toggleOffered(parseInt(cb.dataset.id, 10), cb.checked);
        } else if (cb.dataset.action === 'toggle-program') {
            toggleProgram(parseInt(cb.dataset.id, 10), cb.checked);
        }
    });

    // Esc closes whichever modal is open.
    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        if (!modal.classList.contains('hidden')) closeModal();
        if (!progModal.classList.contains('hidden')) closeAddProgram();
    });
})();

// Plain-JS expand/collapse — works for chevron, folder icon, and node name.
// Each click toggles ONLY that specific branch, independent of siblings.
document.addEventListener('click', (e) => {
    const trigger = e.target.closest('[data-tree-toggle]');
    if (!trigger) return;
    // Don't trigger when the click came from a data-action button or a checkbox.
    if (e.target.closest('[data-action], input[type="checkbox"]')) return;

    const id   = trigger.dataset.treeToggle;
    const wrap = document.querySelector(`[data-tree-children="${id}"]`);
    if (!wrap) return;

    const willCollapse = !wrap.classList.contains('hidden');
    wrap.classList.toggle('hidden', willCollapse);

    const chevron = document.querySelector(`button[data-tree-toggle="${id}"]`);
    if (chevron) {
        chevron.dataset.open = willCollapse ? '0' : '1';
        chevron.textContent  = willCollapse ? '▸' : '▾';
    }
});
</script>
@endsection
