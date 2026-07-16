@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/tests/testBuilder.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tests/points-modal.css') }}">
    <link rel="stylesheet" href="{{ asset('css/modules/testBuilder-cascadeDropdown.css') }}">
@endpush

@section('content')
<body data-page="test-builder">

<div class="dashboard-content-rail">
    <div class="test-builder">

        {{-- ✅ ADD HIDDEN TEST ID INPUT --}}
        <input type="hidden" id="testId" value="{{ $test->id ?? '' }}">

        {{-- ===============================
            TEST SOURCE
        =============================== --}}
        <div class="test-source-grid">
        
        {{-- ===============================
            TEST CONTROLS — shown first so the teacher sets difficulty + levels
            before the source (the levels drive the Subject list).
        =============================== --}}
        <div class="card">
            <h2 style="margin-left:25px">Test Controls</h2>
            <div class="diff-assessment-render-grid">
                
                <div class="form-pair" style="display: block; width: 100%;">

                    {{-- Title on top --}}
                    <div class="label" style="margin-bottom: 6px;">Difficulty:</div>

                    {{-- White box container --}}
                    <div style="
                        background: #ffffff;
                        padding: 12px 16px;
                        border-radius: 6px;
                        border: 1px solid #d1d5db; /* same as academic level border */
                        display: flex;
                        gap: 24px;
                        width: 100%;
                    ">

                        {{-- LEFT COLUMN --}}
                        <div style="flex: 1; display: flex; flex-direction: column; gap: 10px;">
                            <label class="ts-check">
                                <input type="checkbox" name="difficulty[]" value="average">
                                Average
                            </label>
                        </div>

                        {{-- RIGHT COLUMN --}}
                        <div style="flex: 1; display: flex; flex-direction: column; gap: 10px;">
                            <label class="ts-check">
                                <input type="checkbox" name="difficulty[]" value="advanced">
                                Advanced
                            </label>
                        </div>

                    </div>
                </div>

                <div class="form-pair-checkboxes">
                    <label class="section-label">Assessment Levels</label>

                    @php($savedLevelIds = array_map('intval', (array) (optional($test)->academic_levels ?? [])))

                    @if(!empty($levelTree))
                        {{-- Cascade driven by the admin education-structure tree
                             (education_nodes). Navigating to a branch reveals only the
                             grade/year levels that belong to it; ticks are collected as
                             chips whose hidden inputs carry the academic_level ids the
                             builder already submits as academic_levels[]. Scoped inline
                             styles (no Tailwind utilities) so the UI is build-independent. --}}
                        <style>
                            .level-cascade { width: 100%; }
                            .level-cascade .lc-selects { display: flex; flex-direction: column; gap: 10px; }
                            .level-cascade .lc-label { display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 4px; }
                            .level-cascade .lc-select { width: 100%; padding: 8px 10px; border: 1px solid #d1d5db; border-radius: 6px; background: #fff; font-size: 14px; }
                            .level-cascade .lc-levels { margin-top: 12px; background: #fff; border: 1px solid #d1d5db; border-radius: 6px; padding: 10px 12px; }
                            .level-cascade .lc-levels-hint { font-size: 12px; color: #94a3b8; font-style: italic; }
                            .level-cascade .lc-levels-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 6px 18px; }
                            .level-cascade .lc-check { display: flex; align-items: center; gap: 6px; font-size: 13px; color: #334155; cursor: pointer; }
                            .level-cascade .lc-selected { margin-top: 12px; }
                            .level-cascade .lc-selected-label { font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px; }
                            .level-cascade .lc-chips { display: flex; flex-wrap: wrap; gap: 6px; }
                            .level-cascade .lc-chip { display: inline-flex; align-items: center; gap: 6px; background: #eef2ff; color: #3730a3; border: 1px solid #c7d2fe; border-radius: 999px; padding: 3px 10px; font-size: 12px; font-weight: 600; }
                            .level-cascade .lc-chip button { border: 0; background: transparent; color: #4338ca; cursor: pointer; font-size: 14px; line-height: 1; padding: 0; }
                            .level-cascade .lc-empty { font-size: 12px; color: #94a3b8; font-style: italic; }
                        </style>

                        <div id="levelCascade" class="level-cascade">
                            <div class="lc-selects" id="lcSelects">
                                <div class="lc-field" data-depth="0">
                                    <label class="lc-label">Educational Level</label>
                                    <select class="lc-select" data-depth="0">
                                        <option value="" disabled selected>— Select educational level —</option>
                                        @foreach($levelTree as $root)
                                            <option value="{{ $root['id'] }}">{{ $root['name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="lc-levels" id="lcLevels">
                                <div class="lc-levels-hint" id="lcLevelsHint">Select an educational level above to choose grade / year levels.</div>
                                <div class="lc-levels-grid" id="lcLevelsGrid"></div>
                            </div>

                            <div class="lc-selected">
                                <div class="lc-selected-label">Selected Levels</div>
                                <div class="lc-chips" id="lcChips">
                                    <span class="lc-empty" id="lcChipsEmpty">None selected yet.</span>
                                </div>
                            </div>
                        </div>

                        <script>
                        (function () {
                            const TREE           = @json($levelTree);
                            const LEVELS_BY_NODE = @json($levelsByNode);
                            const ALL_LEVELS     = @json($academicLevels->pluck('name', 'id'));
                            const SAVED          = @json($savedLevelIds);

                            const nodeById = {};
                            (function index(list) {
                                (list || []).forEach(n => { nodeById[n.id] = n; index(n.children); });
                            })(TREE);

                            const selects    = document.getElementById('lcSelects');
                            const grid       = document.getElementById('lcLevelsGrid');
                            const hint       = document.getElementById('lcLevelsHint');
                            const chipsWrap  = document.getElementById('lcChips');
                            const chipsEmpty = document.getElementById('lcChipsEmpty');

                            const selected = new Map(); // academicLevelId(int) -> name
                            SAVED.forEach(id => { if (ALL_LEVELS[id] !== undefined) selected.set(Number(id), ALL_LEVELS[id]); });

                            const esc = (s) => String(s).replace(/[&<>"']/g, c =>
                                ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

                            function renderChips() {
                                chipsWrap.querySelectorAll('.lc-chip, input[name="academic_levels[]"]').forEach(el => el.remove());
                                chipsEmpty.style.display = selected.size ? 'none' : '';
                                selected.forEach((name, id) => {
                                    const chip = document.createElement('span');
                                    chip.className = 'lc-chip';
                                    chip.innerHTML = esc(name) + ' <button type="button" data-id="' + id + '" aria-label="Remove">&times;</button>';
                                    chipsWrap.appendChild(chip);

                                    const input = document.createElement('input');
                                    input.type = 'checkbox';
                                    input.name = 'academic_levels[]';
                                    input.value = String(id);
                                    input.checked = true;
                                    input.hidden = true;
                                    chipsWrap.appendChild(input);
                                });

                                // Level selection is the source of truth for the Subject
                                // dropdown — tell the builder to refresh it.
                                document.dispatchEvent(new CustomEvent('assessment-levels:changed'));
                            }

                            chipsWrap.addEventListener('click', (e) => {
                                const btn = e.target.closest('button[data-id]');
                                if (!btn) return;
                                const id = Number(btn.dataset.id);
                                selected.delete(id);
                                const box = grid.querySelector('.lvl-pick[data-id="' + id + '"]');
                                if (box) box.checked = false;
                                renderChips();
                            });

                            function renderLevels(nodeId) {
                                const levels = LEVELS_BY_NODE[nodeId] || [];
                                grid.innerHTML = '';
                                if (!levels.length) {
                                    hint.textContent = 'No question levels are tagged for this branch.';
                                    hint.style.display = '';
                                    return;
                                }
                                hint.style.display = 'none';
                                levels.forEach(lvl => {
                                    const label = document.createElement('label');
                                    label.className = 'lc-check';
                                    label.innerHTML =
                                        '<input type="checkbox" class="lvl-pick" data-id="' + lvl.id + '"' +
                                        (selected.has(Number(lvl.id)) ? ' checked' : '') + '> ' + esc(lvl.name);
                                    label.querySelector('input').addEventListener('change', (ev) => {
                                        const id = Number(lvl.id);
                                        if (ev.target.checked) selected.set(id, lvl.name);
                                        else selected.delete(id);
                                        renderChips();
                                    });
                                    grid.appendChild(label);
                                });
                            }

                            selects.addEventListener('change', (e) => {
                                const sel = e.target.closest('select.lc-select');
                                if (!sel) return;
                                const depth = Number(sel.dataset.depth);

                                // Drop any deeper dropdowns spawned by a previous pick.
                                selects.querySelectorAll('.lc-field').forEach(f => {
                                    if (Number(f.dataset.depth) > depth) f.remove();
                                });

                                const node = nodeById[sel.value];
                                if (!node) return;

                                // Spawn a deeper dropdown only when the branch still needs
                                // navigation (children aren't just grade/year leaves).
                                if (node.drillable) {
                                    const field = document.createElement('div');
                                    field.className = 'lc-field';
                                    field.dataset.depth = depth + 1;
                                    const options = ['<option value="" disabled selected>— Select —</option>']
                                        .concat((node.children || []).map(c => '<option value="' + c.id + '">' + esc(c.name) + '</option>'))
                                        .join('');
                                    field.innerHTML =
                                        '<label class="lc-label">' + esc(node.name) + '</label>' +
                                        '<select class="lc-select" data-depth="' + (depth + 1) + '">' + options + '</select>';
                                    selects.appendChild(field);
                                }

                                renderLevels(node.id);
                            });

                            renderChips();
                        })();
                        </script>
                    @else
                        {{-- Fallback: this school's education-structure tree isn't set up
                             yet, so show the flat level vocabulary directly (values are
                             academic_level ids, same submit contract). --}}
                        <div class="levels-grid" style="display: block !important; width: 100% !important;">
                            <div style="width: 100%; display: flex; justify-content: space-between; gap: 24px;">
                                <div style="width: 50%; display: flex; flex-direction: column; gap: 10px;">
                                    @foreach($academicLevels->slice(0, ceil($academicLevels->count() / 2)) as $level)
                                        <label class="ts-check" style="display: flex; align-items: center; gap: 6px;">
                                            <input type="checkbox" name="academic_levels[]" value="{{ $level->id }}"
                                                @checked(in_array($level->id, $savedLevelIds))>
                                            {{ $level->name }}
                                        </label>
                                    @endforeach
                                </div>
                                <div style="width: 50%; display: flex; flex-direction: column; gap: 10px;">
                                    @foreach($academicLevels->slice(ceil($academicLevels->count() / 2)) as $level)
                                        <label class="ts-check" style="display: flex; align-items: center; gap: 6px;">
                                            <input type="checkbox" name="academic_levels[]" value="{{ $level->id }}"
                                                @checked(in_array($level->id, $savedLevelIds))>
                                            {{ $level->name }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ===============================
            TEST SOURCE — second; its Subject list is gated by the levels above.
        =============================== --}}
        <div class="card">
            <h2 style="margin-left:25px">Test Source</h2>

            <div class="test-source-card">
            @include('components.testBuilder-cascadeDropdown', [
                'showCompetency' => false
            ])
            </div>
        </div>
        </div>
        <div class="render-row-container">
            <button type="button" id="renderAvailabilityBtn" class="btn-render-slim">
                Render Availability
            </button>
            <span class="render-text-hint">
                Click render to see how many questions are available based on your selection.
            </span>
        </div>

        {{-- ===============================
            SOURCE RULES
        =============================== --}}
        <div class="card mt-3" id="ruleCard">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <h3 style="margin-bottom: 0;">Question Settings</h3>
                <button id="pointsBtn" type="button" class="custom-points-btn">Points</button>
            </div>

            <table class="builder-table mb-3">
                <thead>
                    <tr>
                        <th>Source</th>
                        <th>MCQ</th>
                        <th>TF</th>
                        <th>MTF</th>
                        <th>ID</th>
                        <th>Match</th>
                        <th>FIB</th>
                        <th>Enum</th>
                        <th>Essay</th>
                    </tr>
                </thead>
                <tbody id="settingsSourceCell" class="text-muted">
                    <tr>
                        <td colspan="9">Render Availability</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Points Modal -->
        <div id="pointsModal" class="modal" style="display:none; position:fixed; z-index:1050; left:0; top:0; width:100vw; height:100vh; background:rgba(0,0,0,0.4); align-items:center; justify-content:center;">
            <div style="background:white; border-radius:8px; max-width:400px; width:90%; padding:24px; margin:40px auto; position:relative;">
                <h4 style="margin-top:0;">Set Points Per Question Type</h4>
                <form id="pointsForm" autocomplete="off">
                    <div style="display: flex; align-items:center; margin-bottom: 10px;">
                        <label style="flex:1;" for="points-mcq">MCQ:</label>
                        <input type="number" min="0" class="form-control" name="mcq_points" id="points-mcq" style="max-width:60px;" />
                    </div>
                    <div style="display: flex; align-items:center; margin-bottom: 10px;">
                        <label style="flex:1;" for="points-tf">TF:</label>
                        <input type="number" min="0" class="form-control" name="tf_points" id="points-tf" style="max-width:60px;" />
                    </div>
                    <div style="display: flex; align-items:center; margin-bottom: 10px;">
                        <label style="flex:1;" for="points-mtf">MTF:</label>
                        <input type="number" min="0" class="form-control" name="mtf_points" id="points-mtf" style="max-width:60px;" />
                    </div>
                    <div style="display: flex; align-items:center; margin-bottom: 10px;">
                        <label style="flex:1;" for="points-id">ID:</label>
                        <input type="number" min="0" class="form-control" name="id_points" id="points-id" style="max-width:60px;" />
                    </div>
                    <div style="display: flex; align-items:center; margin-bottom: 10px;">
                        <label style="flex:1;" for="points-match">Match:</label>
                        <input type="number" min="0" class="form-control" name="match_points" id="points-match" style="max-width:60px;" />
                    </div>
                    <div style="display: flex; align-items:center; margin-bottom: 10px;">
                        <label style="flex:1;" for="points-fib">FIB:</label>
                        <input type="number" min="0" class="form-control" name="fib_points" id="points-fib" style="max-width:60px;" />
                    </div>
                    <div style="display: flex; align-items:center; margin-bottom: 10px;">
                        <label style="flex:1;" for="points-enum">Enum:</label>
                        <input type="number" min="0" class="form-control" name="enum_points" id="points-enum" style="max-width:60px;" />
                    </div>
                    <div style="display: flex; align-items:center; margin-bottom: 10px;">
                        <label style="flex:1;" for="points-essay">Essay:</label>
                        <input type="number" min="0" class="form-control" name="essay_points" id="points-essay" style="max-width:60px;" />
                    </div>
                    <div style="text-align:right; margin-top: 18px;">
                        <button type="button" id="closePointsModal" class="btn btn-sm btn-secondary" style="margin-right:10px;">Cancel</button>
                        <button type="button" id="savePointsModalButton" class="btn btn-sm btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ===============================
            TEST SETTINGS
        =============================== --}}
        <div class="test-settings-card">
            <div class="ts-section">
                <h3 class="test-settings-title">Test Settings</h3>

                <div class="ts-grid two">
                    <div class="diff-assessment-render">
                    <div class="ts-row">
                        <label for="time-limit">Timer (minutes)</label>
                        <input type="number" id="time-limit" name="time_limit">
                    </div>

                    <div class="ts-row">
                        <label for="start-datetime">Start Date & Time</label>
                        <input type="datetime-local" id="start-datetime" name="start_datetime">
                    </div>

                    <div class="ts-row">
                        <label for="end-datetime">End Date & Time</label>
                        <input type="datetime-local" id="end-datetime" name="end_datetime">
                    </div>

                    <div class="ts-row">
                        <label class="ts-checkbox">
                            <input type="checkbox" id="shuffle-questions" name="shuffle_questions">
                            Shuffle Questions
                        </label>

                        <label class="ts-checkbox">
                            <input type="checkbox" id="shuffle-mcq-choices" name="shuffle_mcq_choices">
                            Shuffle MCQ Choices
                        </label>
                    </div>
                    </div>

                    <div class="diff-assessment-render">
                    <div class="ts-row">
                        <label for="attempts-allowed">Attempts</label>
                        <input type="number" id="attempts-allowed" name="attempts_allowed" value="1">
                    </div>

                    <div class="ts-row">
                        <label for="passingScore">Passing Score (%)</label>
                        <input
                            type="number"
                            id="passingScore"
                            name="passing_score"
                            value="75"
                            min="0"
                            max="100"
                        >
                    </div>

                    <div class="ts-row">
                        <label for="show-results">Show Results</label>
                        <select id="show-results" name="show_results">
                            <option value="after_exam" selected>After Exam</option>
                            <option value="immediate">Immediately</option>
                            <option value="never">Never</option>
                        </select>
                    </div>

                    <div class="ts-row mt-2">
                        <label for="show-correct-answers">Show Correct Answers</label>
                        <select id="show-correct-answers" name="show_correct_answers">
                            <option value="after_exam" selected>After Exam</option>
                            <option value="immediate">Immediately</option>
                            <option value="never">Never</option>
                        </select>
                    </div>
                    </div>
                </div>
            </div>
            <div class="ts-divider"></div>
            <div class="ts-section">
                <h4 class="ts-subtitle">Assignment & Classification</h4>
                    <div class="ts-grid two">

                        <div class="diff-assessment-render-grid">
                            <div class="ts-row">
                                <label for="class_id">Class</label>
                                <select id="class_id" name="class_id">
                                    <option value="">None</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->id }}">
                                            {{ $class->section->name }} - {{ $class->subject->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <!-- EXISTING TERM DROPDOWN -->
                                <label class="mt-2" for="term-select">Term</label>
                                <select id="term-select" name="term">
                                    <option value="">None</option>
                                    <option value="prelim">Prelim</option>
                                    <option value="midterm">Midterm</option>
                                    <option value="finals">Finals</option>
                                </select>
                            </div>
                        </div>

                        <div class="diff-assessment-render-grid">
                            <div class="ts-row">
                                <label for="mode-select">Mode</label>
                                <select id="mode-select" name="mode">
                                    <option value="">Select Mode</option>
                                    <option value="online">Online</option>
                                    <option value="f2f">F2F</option>
                                </select>

                                <label class="mt-2" for="assessment-type-select">Assessment Type</label>
                                <select id="assessment-type-select" name="assessment_type">
                                    <option value="">Select Type</option>
                                    <option>Quiz</option>
                                    <option>Homework</option>
                                    <option>Prelim Examination</option>
                                    <option>Midterm Examination</option>
                                    <option>Final Examination</option>
                                    <option>Long Test</option>
                                    <option>Seatwork</option>
                                    <option>Evaluation Test</option>
                                    <option>Diagnostic Test</option>
                                    <option>Mock Test</option>
                                    <option>Review</option>
                                    <option>Practice</option>
                                </select>
                            </div>
                        </div>
                    </div>
            </div>
        </div>

        {{-- ===============================
            ACTIONS
        =============================== --}}
        <div class="test-builder-actions">

        {{-- SAVE BUTTON --}}
        <button
            type="button"
            id="saveTestBtn"
            class="btn btn-save"
        >
            Save Test
        </button>

        {{-- PRINT BUTTON (hidden first) --}}
        <button
            type="button"
            id="printTestBtn"
            class="btn btn-print"
            style="display:none;"
        >
            Print Test
        </button>

        {{-- EDIT BUTTON (hidden first) --}}
        <button
            type="button"
            id="editTestBtn"
            class="btn btn-edit"
            style="display:none;"
        >
            Edit Test
        </button>

        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('js/tests/test-builder/testBuilder-cascadeDropdown.js') }}"></script>
<script src="{{ asset('js/tests/test-builder/testBuilder.js') }}"></script>
<script src="{{ asset('js/tests/test-builder/saveBuilder.js') }}"></script>
<script src="{{ asset('js/tests/test-builder/points-modal.js') }}"></script>
<script src="{{ asset('js/tests/test-builder/edit.js') }}"></script>
<script src="{{ asset('js/tests/test-builder/print.js') }}"></script>
@endpush