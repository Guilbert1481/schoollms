@extends('layouts.enrollment')

@section('content')
<div class="px-8 py-6 max-w-4xl">

    <div class="text-xs font-extrabold text-slate-500 tracking-widest mb-1">
        STEP 5 OF 7 — ACADEMIC BACKGROUND
    </div>
    <div class="h-2 w-full bg-slate-200 rounded-full overflow-hidden mb-6">
        <div class="h-full bg-indigo-600" style="width:71%"></div>
    </div>

    <h1 class="text-2xl font-extrabold text-slate-800 mb-1">Academic Background</h1>
    <p class="text-sm text-slate-500 mb-6">
        List the schools you previously attended. Add a row per level (elementary,
        junior high, senior high, college, etc.).
    </p>

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-3 mb-4 text-sm">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $err)<li>{{ $err }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('public.apply.academic.store', $term->id) }}" id="bgForm">
        @csrf

        <div class="space-y-4" id="bgList"></div>

        <button type="button" id="addBgBtn"
                class="mt-3 px-4 py-2 rounded-lg border-2 border-dashed border-indigo-300 text-indigo-700 font-bold hover:bg-indigo-50">
            + Add another school
        </button>

        <div class="flex items-center justify-between pt-6">
            <a href="{{ route('public.apply.pathway', $term->id) }}"
               class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 font-bold">← Back</a>
            <button type="submit"
                    class="px-6 py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold">
                Continue →
            </button>
        </div>
    </form>
</div>

<script>
(function () {
    const list      = document.getElementById('bgList');
    const addBtn    = document.getElementById('addBgBtn');
    const existing  = @json($hydrated ?? []);
    const roots     = @json($rootLevels ?? []);
    const branchUrl = "{{ url('apply/'.$term->id.'/pathway/branch') }}/"; // append :nodeId

    function escapeAttr(v) {
        return String(v ?? '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }
    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
    }

    function rowHTML(idx, row) {
        const r = row || {};
        const v = (k) => escapeAttr(r[k] ?? '');
        const selOpt = (val, current) => current === val ? 'selected' : '';
        const rootId = r.chain && r.chain.length ? r.chain[0].id : '';
        const rootOptions = roots.map(rt =>
            `<option value="${rt.id}" ${String(rt.id) === String(rootId) ? 'selected' : ''}>${escapeHtml(rt.name)}</option>`
        ).join('');

        return `
        <div class="border border-slate-200 rounded-xl p-4 bg-white shadow-sm relative" data-bg-row data-idx="${idx}">
            <button type="button" data-remove
                    class="absolute top-2 right-3 text-red-500 text-sm font-bold hover:text-red-700"
                    style="display:none">Remove</button>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                {{-- Cascade: Educational Level + drill-down sub-categories --}}
                <div class="md:col-span-2">
                    <label class="text-xs font-bold text-slate-600">Educational Level *</label>
                    <select data-root-sel name="backgrounds[${idx}][__root_id]"
                            class="w-full rounded-lg border border-slate-300 p-2">
                        <option value="" disabled ${rootId ? '' : 'selected'}>— select educational level —</option>
                        ${rootOptions}
                    </select>
                </div>
                <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-3" data-cascade></div>

                <input type="hidden" name="backgrounds[${idx}][education_node_id]" data-final-node value="${v('education_node_id')}">
                <input type="hidden" name="backgrounds[${idx}][education_level]" data-edu-level value="${v('education_level')}">

                <div>
                    <label class="text-xs font-bold text-slate-600">School Type</label>
                    <select name="backgrounds[${idx}][school_type]"
                            class="w-full rounded-lg border border-slate-300 p-2">
                        <option value="">—</option>
                        <option value="public"  ${selOpt('public',  r.school_type)}>Public</option>
                        <option value="private" ${selOpt('private', r.school_type)}>Private</option>
                        <option value="home"    ${selOpt('home',    r.school_type)}>Home School</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs font-bold text-slate-600">School Name *</label>
                    <input type="text" name="backgrounds[${idx}][school_name]" required value="${v('school_name')}"
                           class="w-full rounded-lg border border-slate-300 p-2">
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs font-bold text-slate-600">School Address</label>
                    <input type="text" name="backgrounds[${idx}][school_address]" value="${v('school_address')}"
                           class="w-full rounded-lg border border-slate-300 p-2">
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-600">GPA</label>
                    <input type="number" step="0.01" min="0" max="100"
                           name="backgrounds[${idx}][gpa]" value="${v('gpa')}"
                           class="w-full rounded-lg border border-slate-300 p-2">
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-600">Honors / Awards</label>
                    <input type="text" name="backgrounds[${idx}][honors]" value="${v('honors')}"
                           class="w-full rounded-lg border border-slate-300 p-2">
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-600">Year Started</label>
                    <input type="number" min="1950" max="2100"
                           name="backgrounds[${idx}][year_started]" value="${v('year_started')}"
                           class="w-full rounded-lg border border-slate-300 p-2">
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-600">Year Ended</label>
                    <input type="number" min="1950" max="2100"
                           name="backgrounds[${idx}][year_ended]" value="${v('year_ended')}"
                           class="w-full rounded-lg border border-slate-300 p-2">
                </div>
            </div>
        </div>`;
    }

    function reindex() {
        const rows = list.querySelectorAll('[data-bg-row]');
        rows.forEach((row, i) => {
            row.dataset.idx = i;
            row.querySelectorAll('[name^="backgrounds["]').forEach((el) => {
                el.name = el.name.replace(/^backgrounds\[\d+\]/, `backgrounds[${i}]`);
            });
            const rm = row.querySelector('[data-remove]');
            if (rm) rm.style.display = rows.length > 1 ? '' : 'none';
        });
    }

    async function fetchBranch(nodeId) {
        const res = await fetch(branchUrl + nodeId, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!res.ok) return null;
        return res.json();
    }

    function clearCascadeFromDepth(rowEl, depth) {
        const cascade = rowEl.querySelector('[data-cascade]');
        Array.from(cascade.children).forEach(el => {
            if (parseInt(el.dataset.depth, 10) >= depth) el.remove();
        });
    }

    function setFinalNode(rowEl, nodeId) {
        rowEl.querySelector('[data-final-node]').value = nodeId || '';
    }

    function syncEduLevel(rowEl) {
        const rootSel = rowEl.querySelector('[data-root-sel]');
        const opt = rootSel.options[rootSel.selectedIndex];
        const name = (opt && opt.value) ? (opt.textContent || '').trim() : '';
        const slug = name.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
        rowEl.querySelector('[data-edu-level]').value = slug;
    }

    /**
     * Render one cascade level (depth >= 1) and wire its change handler.
     * preselectId optionally pre-selects an option and recurses into it.
     */
    async function loadBranch(rowEl, parentNodeId, depth, preselectId) {
        clearCascadeFromDepth(rowEl, depth);
        setFinalNode(rowEl, parentNodeId);
        if (!parentNodeId) return;

        const data = await fetchBranch(parentNodeId);
        if (!data) return;

        if (data.children && data.children.length) {
            const cascade = rowEl.querySelector('[data-cascade]');
            const wrap = document.createElement('div');
            wrap.dataset.depth = depth;
            const labelText = /basic\s*education/i.test(data.node.name)
                ? 'Last Grade Level'
                : `${data.node.name} — Select sub-category`;
            wrap.innerHTML = `
                <label class="text-xs font-bold text-slate-600">${escapeHtml(labelText)}</label>
                <select class="w-full rounded-lg border border-slate-300 p-2">
                    <option value="" disabled selected>— Select —</option>
                    ${data.children.map(c => `<option value="${c.id}">${escapeHtml(c.name)}</option>`).join('')}
                </select>`;
            const sel = wrap.querySelector('select');
            cascade.appendChild(wrap);

            sel.addEventListener('change', () => {
                loadBranch(rowEl, sel.value, depth + 1);
            });

            if (preselectId) {
                sel.value = String(preselectId);
            }
        }
    }

    /**
     * Replay an entire saved chain (root → … → leaf) on a row.
     */
    async function replayChain(rowEl, chain) {
        if (!chain || !chain.length) return;
        // chain[0] is the root — already selected via the root <select>.
        // For each subsequent link, render the dropdown at depth N and select
        // the next link's id.
        let depth = 1;
        for (let i = 0; i < chain.length - 1; i++) {
            const parentId = chain[i].id;
            const nextId   = chain[i + 1].id;
            await loadBranch(rowEl, parentId, depth, nextId);
            depth++;
        }
        // Final node id = last link in chain.
        setFinalNode(rowEl, chain[chain.length - 1].id);
    }

    function bindRow(rowEl, initialChain) {
        const rootSel = rowEl.querySelector('[data-root-sel]');

        rootSel.addEventListener('change', () => {
            syncEduLevel(rowEl);
            applyRoot(rowEl, rootSel.value, null);
        });

        // Hydrate cascade from saved chain (if any).
        if (initialChain && initialChain.length) {
            syncEduLevel(rowEl);
            const isBasic = isBasicEdRoot(rowEl);
            if (isBasic) {
                replayChain(rowEl, initialChain);
            } else {
                // Non-basic roots have no cascade — just restore the Program
                // text field from the saved last_grade_level value.
                setFinalNode(rowEl, initialChain[0].id);
                renderProgramField(rowEl, rowEl.dataset.savedProgram || '');
            }
        } else if (rootSel.value) {
            syncEduLevel(rowEl);
            applyRoot(rowEl, rootSel.value, null);
        }
    }

    function isBasicEdRoot(rowEl) {
        const rootSel = rowEl.querySelector('[data-root-sel]');
        const opt = rootSel.options[rootSel.selectedIndex];
        return /basic\s*education/i.test(opt?.textContent || '');
    }

    /**
     * Branch on root type:
     *   - Basic Education → start cascade at depth 1.
     *   - Anything else (Undergrad, Graduate, Post-Bac, Post-Doctoral) →
     *     no cascade. Final node = root id. Render a free-text "Program" input.
     */
    function applyRoot(rowEl, rootId, preselectId) {
        clearCascadeFromDepth(rowEl, 0);
        if (!rootId) { setFinalNode(rowEl, ''); return; }

        if (isBasicEdRoot(rowEl)) {
            loadBranch(rowEl, rootId, 1, preselectId);
        } else {
            setFinalNode(rowEl, rootId);
            renderProgramField(rowEl, '');
        }
    }

    function renderProgramField(rowEl, value) {
        const cascade = rowEl.querySelector('[data-cascade]');
        const idx     = rowEl.dataset.idx;
        const wrap    = document.createElement('div');
        wrap.dataset.depth = 0;
        wrap.className     = 'md:col-span-2';
        wrap.innerHTML = `
            <label class="text-xs font-bold text-slate-600">Program</label>
            <input type="text" name="backgrounds[${idx}][last_grade_level]"
                   value="${String(value ?? '').replace(/"/g, '&quot;')}"
                   placeholder="e.g. BS Information Technology"
                   class="w-full rounded-lg border border-slate-300 p-2">`;
        cascade.appendChild(wrap);
    }

    function addRow(data) {
        const idx = list.querySelectorAll('[data-bg-row]').length;
        list.insertAdjacentHTML('beforeend', rowHTML(idx, data));
        const rowEl = list.lastElementChild;
        // Stash saved Program value (stored in last_grade_level column) so
        // bindRow() can restore it for non-Basic-Education roots.
        if (data && data.last_grade_level) {
            rowEl.dataset.savedProgram = data.last_grade_level;
        }
        reindex();
        bindRow(rowEl, data && data.chain);
    }

    list.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-remove]');
        if (!btn) return;
        const row = btn.closest('[data-bg-row]');
        if (row) row.remove();
        reindex();
    });

    addBtn.addEventListener('click', () => addRow(null));

    // Initial hydration.
    if (existing && existing.length) {
        existing.forEach((r) => addRow(r));
    } else {
        addRow(null);
    }
})();
</script>
@endsection
