{{-- Global config for external JS --}}
<script>
    window.__schoolSettings = {
        academicYearsBaseUrl: @js(url('/school/settings/master-data/academic-years')),
        termsBaseUrl: @js(url('/school/settings/academic-years')),
    };
</script>

{{-- External JS --}}
<script src="{{ asset('js/school/school-settings.js') }}"></script>

<script>
function toggleTerms(id) {
    const el = document.getElementById('terms-' + id);
    if (!el) return;

    el.classList.toggle('hidden');

    // Save open state in localStorage
    let openTerms = JSON.parse(localStorage.getItem('openTerms') || '[]');

    if (!el.classList.contains('hidden')) {
        if (!openTerms.includes(id)) {
            openTerms.push(id);
        }
    } else {
        openTerms = openTerms.filter(v => v != id);
    }

    localStorage.setItem('openTerms', JSON.stringify(openTerms));
}

// Restore open academic years after page reload
document.addEventListener('DOMContentLoaded', function () {
    let openTerms = JSON.parse(localStorage.getItem('openTerms') || '[]');

    openTerms.forEach(function(id) {
        const el = document.getElementById('terms-' + id);
        if (el) {
            el.classList.remove('hidden');
        }
    });
});

</script>


<script>
// Generic title-field toggler: prefix is 'create' or 'edit'.
function toggleTitleField(prefix) {
    prefix = prefix || 'create';
    const typeEl = document.getElementById(prefix + '_enrollment_type');
    const fieldEl = document.getElementById(prefix + 'TitleField');
    const titleEl = document.getElementById(prefix + '_title');
    if (!typeEl || !fieldEl) return;

    if (typeEl.value === 'special') {
        fieldEl.classList.remove('hidden');
    } else {
        fieldEl.classList.add('hidden');
        if (titleEl) titleEl.value = '';
    }
}
</script>

<script>
// ---- SUBJECT OFFERINGS ----

// Toggle the per-term offerings sub-row
function toggleSubjectOfferings(termId) {
    const row = document.getElementById('offerings-' + termId);
    const chev = document.getElementById('offerings-chevron-' + termId);
    if (!row) return;
    const open = !row.classList.contains('hidden');
    if (open) {
        row.classList.add('hidden');
        if (chev) chev.textContent = '▸';
    } else {
        row.classList.remove('hidden');
        if (chev) chev.textContent = '▾';
    }
}

// Open the Add Subject Offering modal pre-filled with the term id
function openAddSubjectOfferingModal(termId, termLabel) {
    const idEl   = document.getElementById('offering_term_id');
    const lblEl  = document.getElementById('offeringTermLabel');
    const form   = document.getElementById('addSubjectOfferingForm');
    if (idEl)  idEl.value = termId;
    if (lblEl) lblEl.textContent = 'Term: ' + (termLabel || ('#' + termId));
    if (form)  form.reset();
    if (idEl)  idEl.value = termId; // restore after reset
    if (typeof openModal === 'function') openModal('addSubjectOfferingModal');
}
</script>

<script>
// ---- AY & TERMS: filter + columns toolbar ----

(function () {
    const HIDDEN_KEY = 'ayTermsHiddenCols';
    const WIDTHS_KEY = 'ayTermsColWidths';
    const COLS = [
        { key: 'name',    label: 'Academic Year / Term', def: 25 },
        { key: 'start',   label: 'Start',                def: 20 },
        { key: 'end',     label: 'End',                  def: 20 },
        { key: 'status',  label: 'Status',               def: 15 },
        { key: 'actions', label: 'Actions',              def: 20 },
    ];

    function getHidden() {
        try { return JSON.parse(localStorage.getItem(HIDDEN_KEY) || '[]'); }
        catch (e) { return []; }
    }
    function setHidden(arr) {
        localStorage.setItem(HIDDEN_KEY, JSON.stringify(arr));
    }
    function getWidths() {
        try { return JSON.parse(localStorage.getItem(WIDTHS_KEY) || '{}'); }
        catch (e) { return {}; }
    }
    function setWidths(obj) {
        localStorage.setItem(WIDTHS_KEY, JSON.stringify(obj));
    }

    function applySettings() {
        const hidden = getHidden();
        const widths = getWidths();

        // Visibility
        document.querySelectorAll('#ayTermsTable [data-ay-col]').forEach(el => {
            el.style.display = hidden.includes(el.dataset.ayCol) ? 'none' : '';
        });

        // Widths — applied to the <th> elements; table is table-fixed so all cells follow
        COLS.forEach(c => {
            const th = document.querySelector('#ayTermsTable thead th[data-ay-col="' + c.key + '"]');
            if (!th) return;
            const w = widths[c.key];
            th.style.width = (w && w > 0) ? (w + '%') : '';
        });
    }

    // Filter input
    const filterEl = document.getElementById('ayFilter');
    if (filterEl) {
        filterEl.addEventListener('input', function () {
            const q = this.value.trim().toLowerCase();
            document.querySelectorAll('#ayTermsTable tr.ay-row').forEach(row => {
                const name = row.dataset.ayName || '';
                const match = !q || name.includes(q);
                row.style.display = match ? '' : 'none';

                let next = row.nextElementSibling;
                while (next) {
                    if (next.tagName === 'TBODY' && next.id && next.id.startsWith('terms-')) {
                        if (!match) next.classList.add('hidden');
                        break;
                    }
                    if (next.tagName === 'TR' && next.classList.contains('ay-row')) break;
                    next = next.nextElementSibling;
                }
            });
        });
    }

    // Columns modal — built on demand, mirrors x-table.column-modal style
    window.openAYColumnSettings = function () {
        let modal = document.getElementById('ayColumnsModal');
        if (modal) modal.remove();

        const hidden = getHidden();
        const widths = getWidths();

        modal = document.createElement('div');
        modal.id = 'ayColumnsModal';
        modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black/40';
        modal.innerHTML = `
            <div class="bg-white rounded-lg shadow-lg w-96 p-6">
                <h2 class="text-lg font-semibold mb-4">Column Settings</h2>

                <div id="ayColumnsList" class="space-y-2"></div>

                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" id="ayColumnsCancel" class="px-3 py-2 border rounded">Cancel</button>
                    <button type="button" id="ayColumnsSave" class="px-3 py-2 bg-blue-600 text-white rounded">Save</button>
                </div>
            </div>`;

        document.body.appendChild(modal);

        const list = modal.querySelector('#ayColumnsList');
        list.innerHTML = COLS.map(c => `
            <div class="flex items-center justify-between mb-2">
                <label class="flex items-center gap-2">
                    <input type="checkbox" class="ay-col-toggle" value="${c.key}" ${hidden.includes(c.key) ? '' : 'checked'}>
                    ${c.label}
                </label>
                <input type="number"
                       class="ay-col-width border rounded px-2 py-1 w-20 text-sm"
                       data-column="${c.key}"
                       placeholder="%"
                       min="0" max="100"
                       value="${widths[c.key] ?? ''}">
            </div>`).join('');

        modal.addEventListener('click', e => { if (e.target === modal) modal.remove(); });
        modal.querySelector('#ayColumnsCancel').addEventListener('click', () => modal.remove());
        modal.querySelector('#ayColumnsSave').addEventListener('click', () => {
            const newHidden = [];
            const newWidths = {};
            modal.querySelectorAll('.ay-col-toggle').forEach(cb => {
                if (!cb.checked) newHidden.push(cb.value);
            });
            modal.querySelectorAll('.ay-col-width').forEach(inp => {
                const v = parseInt(inp.value, 10);
                if (!isNaN(v) && v > 0) newWidths[inp.dataset.column] = v;
            });
            setHidden(newHidden);
            setWidths(newWidths);
            applySettings();
            modal.remove();
        });
    };

    document.addEventListener('DOMContentLoaded', applySettings);
    applySettings();
})();
</script>