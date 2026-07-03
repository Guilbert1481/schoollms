@extends('layouts.enrollment')

@section('content')
<div class="px-8 py-6 w-full">

    <div class="text-xs font-extrabold text-slate-500 tracking-widest mb-1">
        STEP 4 OF 9 — LEARNING PATHWAY
    </div>
    <div class="h-2 w-full bg-slate-200 rounded-full overflow-hidden mb-6">
        <div class="h-full bg-indigo-600" style="width:44%"></div>
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
        @php($lockedRootId = $lockedRootId ?? null)
        @if ($lockedRootId)
            {{-- Basic-ed enrollment session: educational level is fixed to
                 "Basic Education". Render as a readonly label + hidden input
                 so the cascade still seeds the grade-level dropdowns. --}}
            @php($lockedRoot = $rootLevels->firstWhere('id', $lockedRootId))
            <div>
                <label class="block text-sm font-bold mb-1">Educational Level</label>
                <div class="w-full rounded-lg border border-slate-200 bg-slate-50 p-2.5 text-sm text-slate-700 font-bold">
                    {{ $lockedRoot?->name ?? 'Basic Education' }}
                </div>
            </div>
            <select id="rootLevel" class="hidden">
                <option value="{{ $lockedRootId }}" data-name="{{ $lockedRoot?->name }}" selected>{{ $lockedRoot?->name }}</option>
            </select>
        @else
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
        @endif

        {{-- Cascading dropdowns + Year Level live in the same 2-up grid.
             JS keeps Year Level pinned as the 2nd child so it sits next to
             the first cascade dropdown (e.g. Basic Ed sub-category | Year Level,
             then Senior High sub-cat | Track sub-cat, etc.) --}}
        <div id="cascadeContainer" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div id="yearLevelWrap">
                <label class="block text-sm font-bold mb-1">Year Level <span class="text-red-500">*</span></label>
                <select name="year_level" id="yearLevelSelect" required class="w-full rounded-lg border border-slate-300 p-2.5"
                        data-saved="{{ $saved['year_level'] ?? '' }}">
                    <option value="" disabled {{ empty($saved['year_level']) ? 'selected' : '' }}>— Select year level —</option>
                    @foreach ([1=>'1st Year',2=>'2nd Year',3=>'3rd Year',4=>'4th Year',5=>'5th Year',6=>'6th Year'] as $lv => $lbl)
                        <option value="{{ $lv }}" @selected(($saved['year_level'] ?? null) == $lv)>{{ $lbl }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-slate-400 mt-1 hidden" id="yearLevelHint">
                    Not applicable — grade level is taken from your selected programme.
                </p>
            </div>
        </div>

        {{-- Selected program (filled by JS) — full width because labels are long --}}
        <div id="programWrap" class="hidden">
            <label class="block text-sm font-bold mb-1">Programme <span class="text-red-500">*</span></label>
            <select name="program_id" id="programSelect" class="w-full rounded-lg border border-slate-300 p-2.5">
                <option value="" disabled selected>— Select programme —</option>
            </select>
        </div>

        {{-- Hidden — final selected education node id (deepest node before program) --}}
        <input type="hidden" name="education_node_id" id="finalNodeId"
               value="{{ $saved['education_node_id'] ?? '' }}">

        {{-- 2-up grid: Modality + Student Type --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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

            {{-- Student Type — hidden when modality = Asynchronous Online --}}
            <div id="studentTypeWrap">
                <label class="block text-sm font-bold mb-1">Student Type <span class="text-red-500">*</span></label>
                <select name="student_type" id="studentTypeSelect" class="w-full rounded-lg border border-slate-300 p-2.5">
                    <option value="" disabled {{ empty($saved['student_type']) ? 'selected' : '' }}>— Select student type —</option>
                    @foreach (['new'=>'New Student','transferee'=>'Transferee','shifter'=>'Shifter','returnee'=>'Returnee','regular'=>'Regular','irregular'=>'Irregular'] as $val => $lbl)
                        <option value="{{ $val }}" @selected(($saved['student_type'] ?? null) === $val)>{{ $lbl }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-slate-500 mt-1">
                    Regular students will skip the Academic Background step automatically.
                </p>
            </div>
        </div>

        {{-- ============== SUBJECTS (below Student Type) ============== --}}
        <div id="subjectsSection" class="pt-2">
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

        {{-- Foreigner flag --}}
        <div class="pt-1">
            <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer select-none">
                <input type="checkbox" name="is_foreigner" id="isForeigner" value="1"
                       class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                       @checked(!empty($saved['is_foreigner']))>
                <span class="font-bold">Foreigner</span>
            </label>
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
        // Build a combined string from the root selection PLUS every selected
        // cascade dropdown's option text, so we can detect "Senior High School"
        // etc. even when it's a sub-category of "Basic Education".
        const rootOpt   = rootSel.options[rootSel.selectedIndex];
        const rootName  = (rootOpt && rootOpt.dataset.name || '').toLowerCase();

        const cascadeTexts = Array.from(cascade.querySelectorAll('select'))
            .map(s => (s.options[s.selectedIndex]?.textContent || '').toLowerCase());
        const combined = (rootName + ' ' + cascadeTexts.join(' ')).toLowerCase();

        const isShs        = /senior\s*high|shs|grade\s*1[12]\b/.test(combined);
        const isBasicLower = /(kinder|preschool|elementary|junior\s*high|grade\s*school|grade\s*[1-9]\b|grade\s*10\b)/.test(combined) && !isShs;
        const isBasicAny   = isBasicLower || isShs || /^basic|\bbasic\s*education\b/.test(combined);
        const saved        = yearSel.dataset.saved || '';

        // If the cascade has stashed grade-leaf children (e.g. STEM → [Grade 11,
        // Grade 12], Elementary → [Grade 1..6]), use those to populate the Year
        // Level dropdown so it acts as the leaf selector.
        let leaves = [];
        try { leaves = JSON.parse(cascade.dataset.gradeLeaves || '[]'); } catch (e) { leaves = []; }

        if (leaves.length > 0) {
            yearWrap.querySelector('label').classList.remove('text-slate-400');
            yearSel.classList.remove('hidden');
            yearSel.setAttribute('required', 'required');
            yearHint.classList.add('hidden');

            const opts = leaves
                .map(c => {
                    const m = String(c.name || '').match(/(\d+)/);
                    return m ? { value: m[1], label: c.name } : null;
                })
                .filter(Boolean)
                .sort((a, b) => Number(a.value) - Number(b.value));

            yearSel.innerHTML =
                '<option value="" disabled selected>— Select grade level —</option>' +
                opts.map(o => `<option value="${o.value}">${o.label}</option>`).join('');

            if (saved && opts.some(o => o.value === String(saved))) {
                yearSel.value = String(saved);
            }
        } else if (isShs) {
            // SHS selected but not yet drilled into a strand: always allow
            // Grade 11 / 12 picks so the field is never blocked.
            yearWrap.querySelector('label').classList.remove('text-slate-400');
            yearSel.classList.remove('hidden');
            yearSel.setAttribute('required', 'required');
            yearHint.classList.add('hidden');
            yearSel.innerHTML =
                '<option value="" disabled selected>— Select grade level —</option>' +
                '<option value="11">Grade 11</option>' +
                '<option value="12">Grade 12</option>';
            if (saved === '11' || saved === '12') yearSel.value = saved;
        } else if (isBasicAny) {
            // Inside basic-ed but no leaves yet stashed (e.g. still on first
            // sub-category). Hide year level until the cascade reaches a node
            // whose children are grade leaves.
            yearWrap.querySelector('label').classList.add('text-slate-400');
            yearSel.classList.add('hidden');
            yearSel.removeAttribute('required');
            yearSel.value = '';
            yearHint.classList.remove('hidden');
        } else {
            // Higher ed: 1st–6th Year.
            yearWrap.querySelector('label').classList.remove('text-slate-400');
            yearSel.classList.remove('hidden');
            yearSel.setAttribute('required', 'required');
            yearHint.classList.add('hidden');
            yearSel.innerHTML =
                '<option value="" disabled selected>— Select year level —</option>' +
                '<option value="1">1st Year</option>' +
                '<option value="2">2nd Year</option>' +
                '<option value="3">3rd Year</option>' +
                '<option value="4">4th Year</option>' +
                '<option value="5">5th Year</option>' +
                '<option value="6">6th Year</option>';
            if (['1','2','3','4','5','6'].includes(saved)) yearSel.value = saved;
        }

        // ---- Subjects panel ----
        // Hide for ALL basic education (kinder → SHS). Higher-ed only.
        const subjectsSection = document.getElementById('subjectsSection');
        if (subjectsSection) {
            subjectsSection.classList.toggle('hidden', isBasicAny);
        }
    }

    function clearFromDepth(depth) {
        // Remove all child dropdowns at depth >= depth. yearLevelWrap has no
        // dataset.depth so its NaN comparison is false and it survives.
        Array.from(cascade.children).forEach(el => {
            if (parseInt(el.dataset.depth, 10) >= depth) el.remove();
        });
        // Reset stashed grade-leaves whenever the cascade is rebuilt; the new
        // branch fetch will repopulate them if applicable.
        delete cascade.dataset.gradeLeaves;
        if (depth === 0) {
            programWrap.classList.add('hidden');
            programSel.removeAttribute('required');
            programSel.innerHTML = '<option value="" disabled selected>— Select programme —</option>';
            finalNodeId.value = '';
        }
        positionYearLevel();
    }

    /**
     * Keep yearLevelWrap pinned as the 2nd child of the cascade grid so it
     * always lands in column-2 of row-1 (next to the first cascade dropdown).
     * If the cascade is empty, year level becomes the sole grid item.
     */
    function positionYearLevel() {
        if (yearWrap.parentNode === cascade) cascade.removeChild(yearWrap);
        const cascadeKids = Array.from(cascade.children); // excludes yearWrap now
        if (cascadeKids.length === 0) {
            cascade.appendChild(yearWrap);
        } else if (cascadeKids.length === 1) {
            cascade.appendChild(yearWrap); // after the only cascade dropdown
        } else {
            cascade.insertBefore(yearWrap, cascadeKids[1]); // slot it as 2nd
        }
    }

    /**
     * Map the chosen Year Level (e.g. "11"/"12") to one of the grade-leaf
     * child node ids stashed on the cascade container, and assign that as
     * the final education_node_id. Used when the parent node's children are
     * all leaf "Grade N" entries — we hide the redundant sub-category dropdown
     * and let the Year Level dropdown drive the leaf selection instead.
     */
    function applyGradeLeafFromYearLevel() {
        const raw = cascade.dataset.gradeLeaves;
        if (!raw) return;
        let leaves = [];
        try { leaves = JSON.parse(raw); } catch (e) { return; }
        const yl = String(yearSel.value || '');
        if (!yl) return;
        const match = leaves.find(c => {
            const m = String(c.name || '').match(/(\d+)/);
            return m && m[1] === yl;
        });
        if (match) finalNodeId.value = String(match.id);
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
            programSel.setAttribute('required', 'required');
        } else {
            programWrap.classList.add('hidden');
            programSel.removeAttribute('required');
            programSel.value = '';
            programSel.innerHTML = '<option value="" disabled selected>— Select programme —</option>';
        }

        // If this node has children, render another dropdown to drill deeper.
        // EXCEPT: if the children are leaf grade-level nodes (e.g. "Grade 11",
        // "Grade 12") we skip that dropdown and let the Year Level selector
        // pick the correct leaf node — the sub-category dropdown would be
        // redundant with Year Level.
        const childLabels = (data.children || []).map(c => String(c.name || ''));
        const allGradeLeaves = childLabels.length > 0
            && childLabels.every(n => /^\s*grade\s*\d+\s*$/i.test(n));

        if (data.children && data.children.length && !allGradeLeaves) {
            const wrap = document.createElement('div');
            wrap.dataset.depth = depth;
            wrap.innerHTML = `
                <label class="block text-sm font-bold mb-1">${escapeHtml(data.node.name)} — Select sub-category</label>
                <select class="w-full rounded-lg border border-slate-300 p-2.5">
                    <option value="" disabled selected>— Select —</option>
                    ${data.children.map(c => `<option value="${c.id}">${escapeHtml(c.name)}</option>`).join('')}
                </select>`;
            const sel = wrap.querySelector('select');
            sel.addEventListener('change', () => {
                loadBranch(sel.value, depth + 1);
                syncYearLevelVisibility();
            });
            cascade.appendChild(wrap);
            positionYearLevel();
        } else if (allGradeLeaves) {
            // Remember the grade-leaf children so the Year Level <select> can
            // map its value (e.g. "11") to the corresponding node id.
            cascade.dataset.gradeLeaves = JSON.stringify(
                data.children.map(c => ({ id: c.id, name: c.name }))
            );
            syncYearLevelVisibility();
            applyGradeLeafFromYearLevel();
        } else {
            delete cascade.dataset.gradeLeaves;
        }

        // After populating this branch's programs/children, re-sync UI so SHS
        // selected as a sub-category of Basic Ed shows Grade 11/12 properly.
        positionYearLevel();
        syncYearLevelVisibility();
    }

    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
    }

    rootSel.addEventListener('change', () => {
        syncYearLevelVisibility();
        loadBranch(rootSel.value, 1);
    });

    // When the Year Level changes (e.g. Grade 11 ↔ Grade 12 for SHS), update
    // the hidden education_node_id to point at the matching grade-leaf node.
    yearSel.addEventListener('change', applyGradeLeafFromYearLevel);

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
    positionYearLevel();
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

    // Replay the saved cascade so the student sees their previous picks.
    const savedChain   = @json($savedChain ?? []);
    const savedProgram = @json($saved['program_id'] ?? null);
    const lockedRootId = @json($lockedRootId ?? null);

    if (savedChain.length > 0) {
        (async () => {
            // 1) Select the root.
            rootSel.value = String(savedChain[0].id);
            syncYearLevelVisibility();
            await loadBranch(rootSel.value, 1);

            // 2) Walk down the chain, selecting each child level.
            for (let i = 1; i < savedChain.length; i++) {
                const depth = i; // first child dropdown has data-depth = 1
                const wrap = Array.from(cascade.children)
                    .find(el => parseInt(el.dataset.depth, 10) === depth);
                if (!wrap) break;
                const sel = wrap.querySelector('select');
                if (!sel) break;
                sel.value = String(savedChain[i].id);
                syncYearLevelVisibility();
                await loadBranch(sel.value, depth + 1);
            }

            // 3) Restore the saved programme + refresh the subjects panel.
            if (savedProgram) {
                const opt = Array.from(programSel.options)
                    .find(o => String(o.value) === String(savedProgram));
                if (opt) {
                    programSel.value = String(savedProgram);
                    refreshSubjects();
                }
            }
        })();
    } else if (lockedRootId) {
        // No saved chain, but the root is locked (basic-ed session). Seed the
        // cascade so the grade-level / strand dropdowns appear immediately.
        (async () => {
            rootSel.value = String(lockedRootId);
            syncYearLevelVisibility();
            await loadBranch(lockedRootId, 1);
        })();
    }
})();
</script>
@endsection
