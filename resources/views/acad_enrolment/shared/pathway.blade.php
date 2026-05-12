@extends('layouts.enrollment')

@section('content')
<div class="px-8 py-6 w-full">

    <div class="text-xs font-extrabold text-slate-500 tracking-widest mb-1">
        STEP 4 OF 7 — LEARNING PATHWAY
    </div>
    <div class="h-2 w-full bg-slate-200 rounded-full overflow-hidden mb-6">
        <div class="h-full bg-indigo-600" style="width:57%"></div>
    </div>

    <h1 class="text-2xl font-extrabold text-slate-800 mb-1">Learning Pathway</h1>
    <p class="text-sm text-slate-500 mb-6">
        Pick the educational level, drill down to your specific programme, then choose
        your modality and student type. Your projected class load will appear below.
    </p>

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-3 mb-4 text-sm">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('public.apply.pathway.store', $term->id) }}" class="space-y-5 max-w-4xl">
        @csrf

        {{-- Educational Level (root nodes) — full width since it controls cascade --}}
        <div>
            <label class="block text-sm font-bold mb-1">Educational Level <span class="text-red-500">*</span></label>
            <select id="rootLevel" class="w-full rounded-lg border border-slate-300 p-2.5">
                <option value="" data-name="" disabled {{ empty($saved['education_node_id']) ? 'selected' : '' }}>
                    — Select educational level —
                </option>
                @foreach ($rootLevels as $root)
                    <option value="{{ $root->id }}" data-name="{{ $root->name }}">{{ $root->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Cascading dropdowns appear here (rendered 2-up by JS via grid wrapper) --}}
        <div id="cascadeContainer" class="grid grid-cols-1 md:grid-cols-2 gap-4"></div>

        {{-- Selected program (filled by JS) — full width because labels are long --}}
        <div id="programWrap" class="hidden">
            <label class="block text-sm font-bold mb-1">Programme <span class="text-red-500">*</span></label>
            <select name="program_id" id="programSelect" required class="w-full rounded-lg border border-slate-300 p-2.5">
                <option value="" disabled selected>— Select programme —</option>
            </select>
        </div>

        {{-- Hidden — final selected education node id (deepest node before program) --}}
        <input type="hidden" name="education_node_id" id="finalNodeId"
               value="{{ $saved['education_node_id'] ?? '' }}">

        {{-- 2-up grid: Year Level + Modality --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div id="yearLevelWrap">
                <label class="block text-sm font-bold mb-1">Year Level <span class="text-red-500">*</span></label>
                <select name="year_level" id="yearLevelSelect" required class="w-full rounded-lg border border-slate-300 p-2.5">
                    <option value="" disabled {{ empty($saved['year_level']) ? 'selected' : '' }}>— Select year level —</option>
                    @foreach ([1=>'1st Year',2=>'2nd Year',3=>'3rd Year',4=>'4th Year',5=>'5th Year',6=>'6th Year'] as $lv => $lbl)
                        <option value="{{ $lv }}" @selected(($saved['year_level'] ?? null) == $lv)>{{ $lbl }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-slate-400 mt-1 hidden" id="yearLevelHint">
                    Not applicable — grade level is taken from your selected programme.
                </p>
            </div>

            <div>
                <label class="block text-sm font-bold mb-1">Modality <span class="text-red-500">*</span></label>
                <select name="modality_id" id="modalitySelect" required class="w-full rounded-lg border border-slate-300 p-2.5">
                    <option value="" disabled {{ empty($saved['modality_id']) ? 'selected' : '' }}>— Select modality —</option>
                    @foreach ($modalities as $m)
                        <option value="{{ $m->id }}"
                                data-code="{{ $m->code }}"
                                @selected(($saved['modality_id'] ?? null) == $m->id)>{{ $m->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Student Type — hidden when modality = Asynchronous Online --}}
        <div id="studentTypeWrap">
            <label class="block text-sm font-bold mb-1">Student Type <span class="text-red-500">*</span></label>
            <select name="student_type" id="studentTypeSelect" class="w-full rounded-lg border border-slate-300 p-2.5">
                <option value="" disabled {{ empty($saved['student_type']) ? 'selected' : '' }}>— Select student type —</option>
                @foreach (['new'=>'New Student','transferee'=>'Transferee','returnee'=>'Returnee','regular'=>'Regular','irregular'=>'Irregular'] as $val => $lbl)
                    <option value="{{ $val }}" @selected(($saved['student_type'] ?? null) === $val)>{{ $lbl }}</option>
                @endforeach
            </select>
            <p class="text-xs text-slate-500 mt-1">
                Regular students will skip the Academic Background step automatically.
            </p>
        </div>

        {{-- ============== SUBJECTS (below Student Type) ============== --}}
        <div class="pt-2">
            <div class="flex items-baseline justify-between mb-2 px-1">
                <h2 class="text-lg font-extrabold text-slate-800">Subjects</h2>
                <span id="subjectsCurriculum" class="text-xs text-slate-400 font-mono"></span>
            </div>

            <div id="subjectsPanel"
                 class="bg-white border border-slate-200 rounded-xl shadow-sm p-4">
                <p id="subjectsHint" class="text-sm text-slate-500 mb-3">
                    Pick a programme, year level, and student type to preview your class load.
                </p>

                <div id="subjectsLoading" class="hidden text-xs text-indigo-600 font-bold mb-2">Loading subjects…</div>

                {{-- Section: current year load --}}
                <div id="currentSubjectsSection" class="hidden">
                    <div class="text-xs font-extrabold uppercase tracking-widest text-slate-500 mb-2"
                         id="currentSubjectsHeader">Current Year Subjects</div>
                    <div id="currentSubjectsList" class="grid grid-cols-1 md:grid-cols-2 gap-1.5"></div>
                </div>

                {{-- Section: additional pickable subjects --}}
                <div id="additionalSubjectsSection" class="hidden mt-5">
                    <div class="text-xs font-extrabold uppercase tracking-widest text-emerald-600 mb-2">
                        Additional Subjects Available
                    </div>
                    <p class="text-xs text-slate-500 mb-2">
                        Tick any extra subjects you intend to take this term.
                    </p>
                    <div id="additionalSubjectsList" class="grid grid-cols-1 md:grid-cols-2 gap-1.5"></div>
                </div>

                <div id="subjectsEmpty" class="hidden text-sm text-slate-400 italic py-6 text-center">
                    No active curriculum found for this programme yet.
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between pt-4">
            <a href="{{ route('public.apply.family', $term->id) }}"
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
    const branchUrl    = "{{ url('apply/'.$term->id.'/pathway/branch') }}/"; // append :nodeId
    const cascade      = document.getElementById('cascadeContainer');
    const rootSel      = document.getElementById('rootLevel');
    const finalNodeId  = document.getElementById('finalNodeId');
    const programWrap  = document.getElementById('programWrap');
    const programSel   = document.getElementById('programSelect');
    const modalitySel  = document.getElementById('modalitySelect');
    const studentWrap  = document.getElementById('studentTypeWrap');
    const studentSel   = document.getElementById('studentTypeSelect');
    const yearWrap     = document.getElementById('yearLevelWrap');
    const yearSel      = document.getElementById('yearLevelSelect');
    const yearHint     = document.getElementById('yearLevelHint');

    function syncYearLevelVisibility() {
        const opt = rootSel.options[rootSel.selectedIndex];
        const name = (opt && opt.dataset.name || '').toLowerCase();
        // Year Level (1st-6th Year) is a higher-ed concept. Basic Ed and SHS
        // already have their grade level baked into the cascade selection,
        // so hide it to avoid redundancy.
        const isBasicOrShs = /basic|kinder|elementary|junior|senior\s*high|grade\s*school|shs/.test(name);
        if (isBasicOrShs) {
            yearWrap.querySelector('label').classList.add('text-slate-400');
            yearSel.classList.add('hidden');
            yearSel.removeAttribute('required');
            yearSel.value = '';
            yearHint.classList.remove('hidden');
        } else {
            yearWrap.querySelector('label').classList.remove('text-slate-400');
            yearSel.classList.remove('hidden');
            yearSel.setAttribute('required', 'required');
            yearHint.classList.add('hidden');
        }
    }

    function clearFromDepth(depth) {
        // Remove all child dropdowns at depth >= depth
        Array.from(cascade.children).forEach(el => {
            if (parseInt(el.dataset.depth, 10) >= depth) el.remove();
        });
        if (depth === 0) {
            programWrap.classList.add('hidden');
            programSel.innerHTML = '<option value="" disabled selected>— Select programme —</option>';
            finalNodeId.value = '';
        }
    }

    async function loadBranch(nodeId, depth) {
        clearFromDepth(depth);
        if (!nodeId) return;

        const res = await fetch(branchUrl + nodeId, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!res.ok) return;
        const data = await res.json();

        finalNodeId.value = nodeId;

        // Populate programs at THIS node, if any.
        if (data.programs && data.programs.length) {
            programSel.innerHTML = '<option value="" disabled selected>— Select programme —</option>';
            data.programs.forEach(p => {
                const opt = document.createElement('option');
                opt.value = p.id;
                opt.textContent = (p.code ? p.code + ' — ' : '') + p.name;
                programSel.appendChild(opt);
            });
            programWrap.classList.remove('hidden');
        } else {
            programWrap.classList.add('hidden');
            programSel.innerHTML = '<option value="" disabled selected>— Select programme —</option>';
        }

        // If this node has children, render another dropdown to drill deeper.
        if (data.children && data.children.length) {
            const wrap = document.createElement('div');
            wrap.dataset.depth = depth;
            wrap.innerHTML = `
                <label class="block text-sm font-bold mb-1">${escapeHtml(data.node.name)} — Select sub-category</label>
                <select class="w-full rounded-lg border border-slate-300 p-2.5">
                    <option value="" disabled selected>— Select —</option>
                    ${data.children.map(c => `<option value="${c.id}">${escapeHtml(c.name)}</option>`).join('')}
                </select>`;
            const sel = wrap.querySelector('select');
            sel.addEventListener('change', () => loadBranch(sel.value, depth + 1));
            cascade.appendChild(wrap);
        }
    }

    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
    }

    rootSel.addEventListener('change', () => {
        syncYearLevelVisibility();
        loadBranch(rootSel.value, 1);
    });

    // Toggle Student Type visibility based on modality code (async_online).
    function syncStudentTypeVisibility() {
        const opt = modalitySel.options[modalitySel.selectedIndex];
        const isAsync = opt && opt.dataset.code === 'async_online';
        if (isAsync) {
            studentWrap.classList.add('hidden');
            studentSel.removeAttribute('required');
            studentSel.value = '';
        } else {
            studentWrap.classList.remove('hidden');
            studentSel.setAttribute('required', 'required');
        }
    }
    modalitySel.addEventListener('change', syncStudentTypeVisibility);
    syncStudentTypeVisibility();
    syncYearLevelVisibility();

    /* =====================================================================
     |  SUBJECTS RIGHT PANEL — fetches the active curriculum's class load
     |  whenever Programme / Year Level / Student Type changes.
     | =====================================================================*/
    const subjectsUrl    = "{{ route('public.apply.pathway.subjects', $term->id) }}";
    const subjectsPanel  = document.getElementById('subjectsPanel');
    const sLoading       = document.getElementById('subjectsLoading');
    const sHint          = document.getElementById('subjectsHint');
    const sEmpty         = document.getElementById('subjectsEmpty');
    const sCurrSection   = document.getElementById('currentSubjectsSection');
    const sCurrHeader    = document.getElementById('currentSubjectsHeader');
    const sCurrList      = document.getElementById('currentSubjectsList');
    const sAddSection    = document.getElementById('additionalSubjectsSection');
    const sAddList       = document.getElementById('additionalSubjectsList');
    const sCurrLabel     = document.getElementById('subjectsCurriculum');

    function rowHtml(s, withCheckbox) {
        const checkbox = withCheckbox
            ? `<input type="checkbox" name="picked_subjects[]" value="${s.subject_id}"
                      class="mt-0.5 w-4 h-4 text-indigo-600 rounded border-slate-300">`
            : '';
        const semLbl = s.semester ? `Sem ${s.semester}` : '';
        const meta = [
            s.year_level ? `Y${s.year_level}` : '',
            semLbl,
            s.units ? `${s.units}u` : '',
            s.is_elective ? 'Elective' : '',
        ].filter(Boolean).join(' · ');
        return `<label class="flex gap-2 items-start p-2 rounded-lg border border-slate-100 hover:bg-slate-50 cursor-pointer">
            ${checkbox}
            <div class="flex-1 text-sm leading-tight">
                <div class="font-bold text-slate-800">${escapeHtml(s.code || '')} — ${escapeHtml(s.name)}</div>
                <div class="text-xs text-slate-500">${meta}</div>
            </div>
        </label>`;
    }

    let subjectsAbort = null;
    async function refreshSubjects() {
        const programId   = programSel.value;
        const yearLevel   = yearSel.value;
        const studentType = studentSel.value;

        if (!programId) {
            sHint.classList.remove('hidden');
            sEmpty.classList.add('hidden');
            sCurrSection.classList.add('hidden');
            sAddSection.classList.add('hidden');
            sCurrLabel.textContent = '';
            return;
        }

        sHint.classList.add('hidden');
        sLoading.classList.remove('hidden');

        if (subjectsAbort) subjectsAbort.abort();
        subjectsAbort = new AbortController();

        const params = new URLSearchParams();
        params.set('program_id', programId);
        if (yearLevel)   params.set('year_level', yearLevel);
        if (studentType) params.set('student_type', studentType);

        try {
            const res = await fetch(`${subjectsUrl}?${params}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: subjectsAbort.signal,
            });
            sLoading.classList.add('hidden');
            if (!res.ok) return;
            const data = await res.json();

            sCurrLabel.textContent = data.curriculum
                ? `${data.curriculum.name}${data.curriculum.version ? ' v'+data.curriculum.version : ''}`
                : '';

            const pickable = data.mode === 'pickable';
            const hasCurrent = (data.current_year_subjects || []).length > 0;
            const hasAdd     = (data.additional_subjects   || []).length > 0;

            if (!data.curriculum && !hasCurrent && !hasAdd) {
                sEmpty.classList.remove('hidden');
                sCurrSection.classList.add('hidden');
                sAddSection.classList.add('hidden');
                return;
            }
            sEmpty.classList.add('hidden');

            // Header text reflects mode.
            sCurrHeader.textContent = pickable
                ? 'Curriculum Active Subjects (tick to enroll)'
                : (yearLevel ? `Year ${yearLevel} Subjects` : 'Curriculum Subjects');

            sCurrSection.classList.toggle('hidden', !hasCurrent);
            sCurrList.innerHTML = (data.current_year_subjects || [])
                .map(s => rowHtml(s, pickable)).join('');

            // Additional pickable section only for pickable modes.
            if (pickable && hasAdd) {
                sAddSection.classList.remove('hidden');
                sAddList.innerHTML = data.additional_subjects.map(s => rowHtml(s, true)).join('');
            } else {
                sAddSection.classList.add('hidden');
                sAddList.innerHTML = '';
            }
        } catch (e) {
            if (e.name !== 'AbortError') console.error(e);
            sLoading.classList.add('hidden');
        }
    }

    programSel.addEventListener('change', refreshSubjects);
    yearSel.addEventListener('change',    refreshSubjects);
    studentSel.addEventListener('change', refreshSubjects);

    // Initial fetch when the page loads with a saved programme.
    if (programSel.value) refreshSubjects();

    // If we have a saved root level, pre-load its branch.
    @if (!empty($saved['education_node_id']))
        // Best-effort: walk down from root using the saved node id; we just set
        // the hidden field and let the user re-pick if they want to change.
    @endif
})();
</script>
@endsection
