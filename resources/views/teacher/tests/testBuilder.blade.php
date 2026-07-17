@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/tests/testBuilder.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tests/points-modal.css') }}">
    <link rel="stylesheet" href="{{ asset('css/modules/testBuilder-cascadeDropdown.css') }}">
@endpush

@section('page-title', 'Test Builder')

@section('content')
<body data-page="test-builder">

<div class="dashboard-content-rail">
    <div class="test-builder">

        <div style="margin-bottom: 16px;">
            <h1 class="text-2xl font-bold text-slate-800">Test Builder</h1>
        </div>

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

                    @include('components.education-levels', [
                        'levelTree'      => $levelTree,
                        'levelsByNode'   => $levelsByNode,
                        'academicLevels' => $academicLevels,
                        'multiple'       => true,
                        'name'           => 'academic_levels',
                        'selected'       => array_map('intval', (array) (optional($test)->academic_levels ?? [])),
                    ])
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
            @php
                $ptLabels = ['mcq' => 'MCQ', 'tf' => 'TF', 'mtf' => 'MTF', 'id' => 'ID', 'match' => 'Match', 'fib' => 'FIB', 'enum' => 'Enum', 'essay' => 'Essay'];
                $ptParts = [];
                foreach ($ptLabels as $ptKey => $ptLabel) {
                    if (isset(($typePoints ?? [])[$ptKey]) && (int) $typePoints[$ptKey] > 0) {
                        $ptParts[] = $ptLabel.' '.(int) $typePoints[$ptKey];
                    }
                }
            @endphp
            <div id="pointsSummary" style="margin-top:6px; font-size:0.85rem; color:#475569;">
                @if (count($ptParts))
                    <strong>Points/type:</strong> {{ implode(' · ', $ptParts) }}
                @else
                    <span style="color:#94a3b8;">No custom points set — defaults to 1 pt each.</span>
                @endif
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
                @php $typePoints = $typePoints ?? []; @endphp
                <form id="pointsForm" autocomplete="off">
                    <div style="display: flex; align-items:center; margin-bottom: 10px;">
                        <label style="flex:1;" for="points-mcq">MCQ:</label>
                        <input type="number" min="0" class="form-control" name="mcq_points" id="points-mcq" value="{{ $typePoints['mcq'] ?? '' }}" style="max-width:60px;" />
                    </div>
                    <div style="display: flex; align-items:center; margin-bottom: 10px;">
                        <label style="flex:1;" for="points-tf">TF:</label>
                        <input type="number" min="0" class="form-control" name="tf_points" id="points-tf" value="{{ $typePoints['tf'] ?? '' }}" style="max-width:60px;" />
                    </div>
                    <div style="display: flex; align-items:center; margin-bottom: 10px;">
                        <label style="flex:1;" for="points-mtf">MTF:</label>
                        <input type="number" min="0" class="form-control" name="mtf_points" id="points-mtf" value="{{ $typePoints['mtf'] ?? '' }}" style="max-width:60px;" />
                    </div>
                    <div style="display: flex; align-items:center; margin-bottom: 10px;">
                        <label style="flex:1;" for="points-id">ID:</label>
                        <input type="number" min="0" class="form-control" name="id_points" id="points-id" value="{{ $typePoints['id'] ?? '' }}" style="max-width:60px;" />
                    </div>
                    <div style="display: flex; align-items:center; margin-bottom: 10px;">
                        <label style="flex:1;" for="points-match">Match:</label>
                        <input type="number" min="0" class="form-control" name="match_points" id="points-match" value="{{ $typePoints['match'] ?? '' }}" style="max-width:60px;" />
                    </div>
                    <div style="display: flex; align-items:center; margin-bottom: 10px;">
                        <label style="flex:1;" for="points-fib">FIB:</label>
                        <input type="number" min="0" class="form-control" name="fib_points" id="points-fib" value="{{ $typePoints['fib'] ?? '' }}" style="max-width:60px;" />
                    </div>
                    <div style="display: flex; align-items:center; margin-bottom: 10px;">
                        <label style="flex:1;" for="points-enum">Enum:</label>
                        <input type="number" min="0" class="form-control" name="enum_points" id="points-enum" value="{{ $typePoints['enum'] ?? '' }}" style="max-width:60px;" />
                    </div>
                    <div style="display: flex; align-items:center; margin-bottom: 10px;">
                        <label style="flex:1;" for="points-essay">Essay:</label>
                        <input type="number" min="0" class="form-control" name="essay_points" id="points-essay" value="{{ $typePoints['essay'] ?? '' }}" style="max-width:60px;" />
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
                                {{-- Options are filtered by testBuilder.js to the chosen
                                     Test-Source subject + selected grade/year level. --}}
                                <select id="class_id" name="class_id">
                                    <option value="">None</option>
                                    <option value="others">Others (personal — not for a class)</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->id }}"
                                            data-subject-id="{{ $class->subject_id }}"
                                            data-year-level="{{ optional($class->section)->year_level }}">
                                            {{ optional($class->section)->name }} - {{ optional($class->subject)->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <script>
                                    // academic_level id → year number (sequence_order), for matching
                                    // a selected level to a class section's year_level.
                                    window.CLASS_LEVEL_YEARS = @json($academicLevels->pluck('sequence_order', 'id'));
                                </script>
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

        {{-- ANSWER SHEETS (OMR) BUTTON (hidden first) --}}
        <button
            type="button"
            id="answerSheetsBtn"
            class="btn btn-print"
            style="display:none;"
        >
            Answer Sheets
        </button>

        {{-- RECORD OMR ANSWERS BUTTON (hidden first) --}}
        <button
            type="button"
            id="recordOmrBtn"
            class="btn btn-print"
            style="display:none;"
        >
            Record Answers
        </button>

        {{-- SCAN OMR (CAMERA) BUTTON (hidden first) --}}
        <button
            type="button"
            id="scanOmrBtn"
            class="btn btn-print"
            style="display:none;"
        >
            Scan (Camera)
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