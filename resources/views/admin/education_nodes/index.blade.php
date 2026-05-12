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

    <div x-data="educationTree()" class="grid grid-cols-1 lg:grid-cols-3 gap-6">

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
                    <button type="button" @click="collapseAll"
                        class="px-3 py-1.5 text-xs rounded-lg border border-slate-300 bg-white hover:bg-slate-50">
                        Collapse All
                    </button>
                    <button type="button" @click="expandAll"
                        class="px-3 py-1.5 text-xs rounded-lg border border-slate-300 bg-white hover:bg-slate-50">
                        Expand All
                    </button>
                    <button type="button" @click="openCreate(null, null)"
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
    <div x-show="modalOpen" x-cloak
         class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-5"
             @click.outside="modalOpen = false">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-slate-900" x-text="isEdit ? 'Edit Node' : 'Add New Node'"></h3>
                <button type="button" @click="modalOpen = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>

            <div class="space-y-3">
                <div x-show="!isEdit && parentName">
                    <label class="block text-xs font-medium text-slate-700 mb-1">Parent</label>
                    <input type="text" :value="parentName" disabled
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-slate-50 text-sm">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Name <span class="text-red-500">*</span></label>
                    <input type="text" x-model="form.name" placeholder="Enter node name"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Node Type <span class="text-red-500">*</span></label>
                    <select x-model="form.node_type"
                            class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white">
                        <option value="">Select node type</option>
                        @foreach ($nodeTypes as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Order Index</label>
                    <input type="number" x-model.number="form.order_index"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-5">
                <button type="button" @click="modalOpen = false"
                        class="px-4 py-2 rounded-lg border border-slate-300 text-sm hover:bg-slate-50">
                    Cancel
                </button>
                <button type="button" @click="save"
                        class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">
                    Save
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function educationTree() {
    return {
        modalOpen: false,
        isEdit: false,
        editingId: null,
        parentName: '',
        form: { name: '', parent_id: null, node_type: '', order_index: 0 },

        csrf: () => document.querySelector('meta[name="csrf-token"]')?.content
            || document.querySelector('input[name="_token"]')?.value,

        async req(url, method, body) {
            const res = await fetch(url, {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': this.csrf(),
                },
                body: body ? JSON.stringify(body) : null,
            });
            if (!res.ok) throw new Error(await res.text());
            return res.json();
        },

        openCreate(parentId, parentName) {
            this.isEdit = false;
            this.editingId = null;
            this.parentName = parentName || '';
            this.form = { name: '', parent_id: parentId, node_type: '', order_index: 0 };
            this.modalOpen = true;
        },

        openEdit(id, name, nodeType, orderIndex) {
            this.isEdit = true;
            this.editingId = id;
            this.parentName = '';
            this.form = { name, parent_id: null, node_type: nodeType, order_index: orderIndex };
            this.modalOpen = true;
        },

        async save() {
            if (!this.form.name.trim() || !this.form.node_type) {
                alert('Name and Node Type are required.');
                return;
            }
            try {
                if (this.isEdit && typeof this.editingId === 'string' && this.editingId.startsWith('program:')) {
                    const pid = this.editingId.slice('program:'.length);
                    await this.req(`{{ url('admin/education-nodes/programs') }}/${pid}`, 'PUT', {
                        name: this.form.name,
                        code: this.form._code || '',
                    });
                } else if (this.isEdit) {
                    await this.req(`{{ url('admin/education-nodes') }}/${this.editingId}`, 'PUT', this.form);
                } else {
                    await this.req(`{{ url('admin/education-nodes') }}`, 'POST', this.form);
                }
                location.reload();
            } catch (e) {
                console.error(e);
                alert('Failed to save.');
            }
        },

        async toggleOffered(id, checked) {
            try {
                await this.req(`{{ url('admin/education-nodes') }}/${id}/toggle-offered`, 'POST', { is_offered: checked });
            } catch (e) {
                console.error(e);
                alert('Failed to update.');
                location.reload();
            }
        },

        async destroy(id) {
            if (!confirm('Delete this node and ALL its descendants?')) return;
            try {
                await this.req(`{{ url('admin/education-nodes') }}/${id}`, 'DELETE');
                location.reload();
            } catch (e) {
                console.error(e);
                alert('Failed to delete.');
            }
        },

        // ---- Program proxy actions ----
        async toggleProgram(id, checked) {
            try {
                await this.req(`{{ url('admin/education-nodes/programs') }}/${id}/toggle-offered`, 'POST', { is_offered: checked });
            } catch (e) {
                console.error(e);
                alert('Failed to update program.');
                location.reload();
            }
        },

        openEditProgram(id, name, code) {
            // Reuse the same modal but route the save to programs endpoint.
            this.isEdit = true;
            this.editingId = 'program:' + id;
            this.parentName = '';
            this.form = { name, parent_id: null, node_type: 'program_type', order_index: 0, _code: code };
            this.modalOpen = true;
        },

        async destroyProgram(id) {
            if (!confirm('Delete this program?')) return;
            try {
                await this.req(`{{ url('admin/education-nodes/programs') }}/${id}`, 'DELETE');
                location.reload();
            } catch (e) {
                console.error(e);
                alert('Failed to delete program.');
            }
        },

        expandAll() {
            document.querySelectorAll('[data-tree-children]').forEach(el => el.classList.remove('hidden'));
            document.querySelectorAll('button[data-tree-toggle]').forEach(el => {
                el.dataset.open = '1';
                el.textContent = '▾';
            });
        },
        collapseAll() {
            document.querySelectorAll('[data-tree-children]').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('button[data-tree-toggle]').forEach(el => {
                el.dataset.open = '0';
                el.textContent = '▸';
            });
        },
    };
}

// Plain-JS expand/collapse — works for chevron, folder icon, and node name.
// Each click toggles ONLY that specific branch, independent of siblings.
document.addEventListener('click', (e) => {
    const trigger = e.target.closest('[data-tree-toggle]');
    if (!trigger) return;
    // Ignore clicks on buttons/inputs inside the row (checkbox, action buttons).
    if (e.target.closest('input, button[onclick], button[\\@click], [x-on\\:click]')
        && !e.target.closest('button[data-tree-toggle]')) return;

    const id   = trigger.dataset.treeToggle;
    const wrap = document.querySelector(`[data-tree-children="${id}"]`);
    if (!wrap) return;

    const willCollapse = !wrap.classList.contains('hidden');
    wrap.classList.toggle('hidden', willCollapse);

    // Sync the chevron button (if any) for this same node id.
    const chevron = document.querySelector(`button[data-tree-toggle="${id}"]`);
    if (chevron) {
        chevron.dataset.open = willCollapse ? '0' : '1';
        chevron.textContent  = willCollapse ? '▸' : '▾';
    }
});
</script>
@endsection
