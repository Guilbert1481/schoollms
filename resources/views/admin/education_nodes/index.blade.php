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
                    @include('admin.education_nodes.partials.node', ['node' => $node, 'depth' => 0])
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
</div>

<script>
(function () {
    const base       = "{{ url('admin/education-nodes') }}";
    const modal      = document.getElementById('nodeModal');
    const modalTitle = document.getElementById('modalTitle');
    const parentRow  = document.getElementById('parentRow');
    const parentInp  = document.getElementById('parentInput');
    const nameInp    = document.getElementById('nameInput');
    const typeInp    = document.getElementById('nodeTypeInput');
    const orderInp   = document.getElementById('orderInput');

    let state = { isEdit: false, editingId: null };

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
        state = { isEdit: false, editingId: null };
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
        state = { isEdit: true, editingId: id };
        modalTitle.textContent = 'Edit Node';
        parentRow.classList.add('hidden');
        parentInp.value = '';
        nameInp.value   = name || '';
        typeInp.value   = nodeType || '';
        orderInp.value  = orderIndex || 0;
        modal.dataset.parentId = '';
        openModal();
    }

    async function save() {
        const name = nameInp.value.trim();
        const type = typeInp.value;
        if (!name || !type) { alert('Name and Node Type are required.'); return; }

        const order = parseInt(orderInp.value, 10) || 0;

        try {
            if (state.isEdit) {
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
            case 'add-root':     openCreate(null, null); break;
            case 'add-child':    openCreate(parseInt(btn.dataset.parentId, 10), btn.dataset.parentName); break;
            case 'edit-node':    openEdit(parseInt(btn.dataset.id, 10), btn.dataset.name, btn.dataset.nodeType, parseInt(btn.dataset.orderIndex, 10) || 0); break;
            case 'delete-node':  destroy(parseInt(btn.dataset.id, 10)); break;
            case 'expand-all':   expandAll(); break;
            case 'collapse-all': collapseAll(); break;
            case 'close-modal':  closeModal(); break;
            case 'save':         save(); break;
            case 'modal-backdrop':
                // Only close if the click landed on the backdrop itself (not the box).
                if (e.target === btn) closeModal();
                break;
        }
    });

    // Checkbox toggles use change event.
    document.addEventListener('change', (e) => {
        const cb = e.target;
        if (!(cb instanceof HTMLInputElement) || cb.type !== 'checkbox') return;
        if (cb.dataset.action === 'toggle-offered') {
            toggleOffered(parseInt(cb.dataset.id, 10), cb.checked);
        }
    });

    // Esc closes modal.
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
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
