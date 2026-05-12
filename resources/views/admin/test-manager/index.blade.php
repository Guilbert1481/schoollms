@extends('layouts.admin')

@section('page-title', 'Test Manager')
@section('page-subtitle', 'Configure curriculum structure and test rules for this school')

@section('title', 'Test Manager')

@section('content')

<link rel="stylesheet" href="{{ asset('css/admin/test-manager.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin/add-items.css') }}">

<div class="container-fluid px-4">

    <!-- ================= ACTION BUTTONS ================= -->
    <div class="tm-actions">
        <button class="tm-btn" onclick="openModal('programModal')">➕ Add Program</button>
        <button class="tm-btn" onclick="openModal('subjectModal')">➕ Add Subject</button>
        <button class="tm-btn" onclick="openModal('topicModal')">➕ Add Topic</button>
        <button class="tm-btn" onclick="openModal('lessonModal')">➕ Add Lesson</button>
        <button class="tm-btn" onclick="openModal('competencyModal')">➕ Add Competency</button>
    </div>

    <!-- ================= SUCCESS/ERROR MESSAGES ================= -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- ================= CONFIGURATION FORM ================= -->
    <h2>Question Configuration</h2>

    <form method="POST" action="{{ route('admin.configurations.update') }}" id="config-form">
        @csrf

        <div class="config-section">
            <h3>Question Configuration</h3>

            <div class="config-grid">

                <!-- LEFT COLUMN -->
                <div class="config-column">

                    <!-- Academic Level -->
                    <div class="config-box">
                        <h4>Academic Level</h4>

                        <div id="academic-level-list" class="checkbox-list">
                            @foreach($academicLevels as $level)
                                <label>
                                    <input type="checkbox" 
                                           name="enabled_configs[]" 
                                           value="{{ $level->id }}"
                                           {{ $level->is_active ? 'checked' : '' }}>
                                    <span class="config-label" data-id="{{ $level->id }}">{{ $level->label }}</span>
                                    <button type="button" 
                                            class="btn-delete delete-config" 
                                            data-id="{{ $level->id }}"
                                            title="Delete">×</button>
                                </label>
                            @endforeach
                        </div>

                        <div class="add-new-section">
                            <input type="text" 
                                   class="new-config-input" 
                                   placeholder="Add new academic level">
                            <button type="button" 
                                    class="add-config-btn" 
                                    data-category="academic_level">Add</button>
                        </div>
                    </div>

                    <!-- Question Type -->
                    <div class="config-box">
                        <h4>Question Type</h4>

                        <div id="question-type-list" class="checkbox-list">
                            @foreach($questionTypes as $type)
                                <label>
                                    <input type="checkbox" 
                                           name="enabled_configs[]" 
                                           value="{{ $type->id }}"
                                           {{ $type->is_active ? 'checked' : '' }}>
                                    <span class="config-label" data-id="{{ $type->id }}">{{ $type->label }}</span>
                                    <button type="button" 
                                            class="btn-delete delete-config" 
                                            data-id="{{ $type->id }}"
                                            title="Delete">×</button>
                                </label>
                            @endforeach
                        </div>

                        <div class="add-new-section">
                            <input type="text" 
                                   class="new-config-input" 
                                   placeholder="Add new question type">
                            <button type="button" 
                                    class="add-config-btn" 
                                    data-category="question_type">Add</button>
                        </div>
                    </div>

                </div>

                <!-- RIGHT COLUMN -->
                <div class="config-column">

                    <!-- Difficulty -->
                    <div class="config-box">
                        <h4>Difficulty</h4>

                        <div id="difficulty-list" class="checkbox-list">
                            @foreach($difficulties as $difficulty)
                                <label>
                                    <input type="checkbox" 
                                           name="enabled_configs[]" 
                                           value="{{ $difficulty->id }}"
                                           {{ $difficulty->is_active ? 'checked' : '' }}>
                                    <span class="config-label" data-id="{{ $difficulty->id }}">{{ $difficulty->label }}</span>
                                    <button type="button" 
                                            class="btn-delete delete-config" 
                                            data-id="{{ $difficulty->id }}"
                                            title="Delete">×</button>
                                </label>
                            @endforeach
                        </div>

                        <div class="add-new-section">
                            <input type="text" 
                                   class="new-config-input" 
                                   placeholder="Add new difficulty">
                            <button type="button" 
                                    class="add-config-btn" 
                                    data-category="difficulty">Add</button>
                        </div>
                    </div>

                    <!-- Assessment Type -->
                    <div class="config-box">
                        <h4>Assessment Type</h4>

                        <div id="assessment-type-list" class="checkbox-list">
                            @foreach($assessmentTypes as $type)
                                <label>
                                    <input type="checkbox" 
                                           name="enabled_configs[]" 
                                           value="{{ $type->id }}"
                                           {{ $type->is_active ? 'checked' : '' }}>
                                    <span class="config-label" data-id="{{ $type->id }}">{{ $type->label }}</span>
                                    <button type="button" 
                                            class="btn-delete delete-config" 
                                            data-id="{{ $type->id }}"
                                            title="Delete">×</button>
                                </label>
                            @endforeach
                        </div>

                        <div class="add-new-section">
                            <input type="text" 
                                   class="new-config-input" 
                                   placeholder="Add new assessment type">
                            <button type="button" 
                                    class="add-config-btn" 
                                    data-category="assessment_type">Add</button>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- ================= TERM STRUCTURE ================= -->
        <div class="config-box">
            <h4>Academic Term Structure</h4>

            <div class="checkbox-list">
                @foreach($termDivisions as $division)
                    <label>
                        <input type="checkbox" 
                               name="enabled_configs[]" 
                               value="{{ $division->id }}"
                               {{ $division->is_active ? 'checked' : '' }}>
                        <span class="config-label" data-id="{{ $division->id }}">{{ $division->label }}</span>
                        <button type="button" 
                                class="btn-delete delete-config" 
                                data-id="{{ $division->id }}"
                                title="Delete">×</button>
                    </label>
                @endforeach
            </div>

            <p style="font-size:12px;color:#6b7280;margin-top:8px;">
                Select the grading periods used by the school during a semester.
            </p>
        </div>

        <!-- ================= SAVE BUTTON ================= -->
        <div class="mt-4 mb-5 text-center">
            <button type="submit" class="btn btn-primary btn-lg">
                💾 Save Configuration
            </button>
        </div>

    </form>

</div>

<!-- ================= MODALS ================= -->

<!-- PROGRAM MODAL -->
<div id="programModal" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h3>Add Program</h3>
            <span class="modal-close" onclick="closeModal('programModal')">✖</span>
        </div>

        @if(session('success_program'))
            <p class="success">{{ session('success_program') }}</p>
        @endif

        <form method="POST" action="{{ route('admin.test-manager.programs.store') }}">
            @csrf
            <input type="text" name="name" placeholder="e.g. Senior High" required>
            <div class="modal-actions">
                <button type="submit" class="btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- SUBJECT MODAL -->
<div id="subjectModal" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h3>+ Subjects (Bulk)</h3>
            <span class="modal-close" onclick="closeModal('subjectModal')">✖</span>
        </div>

        @if(session('success_subject'))
            <p class="success">{{ session('success_subject') }}</p>
        @endif

        <form method="POST" action="{{ route('admin.test-manager.subjects.store') }}">
            @csrf

            <div id="subjectInputs">
                <input type="text" name="names[]" placeholder="e.g. Mathematics" required>
            </div>

            <button type="button" class="btn-secondary" onclick="addInput('subjectInputs')">
                + Add another
            </button>

            <div class="modal-actions">
                <button type="submit" class="btn-primary">Save All</button>
            </div>
        </form>
    </div>
</div>

<!-- TOPIC MODAL -->
<div id="topicModal" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h3>Add Topics (Bulk)</h3>
            <span class="modal-close" onclick="closeModal('topicModal')">✖</span>
        </div>

        @if(session('success_topic'))
            <p class="success">{{ session('success_topic') }}</p>
        @endif

        <form method="POST" action="{{ route('admin.test-manager.topics.store') }}">
            @csrf

            <select name="subject_id" required>
                <option value="">-- select subject --</option>
                @foreach($subjects as $subject)
                    <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                @endforeach
            </select>

            <div id="topicInputs">
                <input type="text" name="names[]" placeholder="e.g. Polynomial" required>
            </div>

            <button type="button" class="btn-secondary" onclick="addInput('topicInputs')">
                + Add another
            </button>

            <div class="modal-actions">
                <button type="submit" class="btn-primary">Save All</button>
            </div>
        </form>
    </div>
</div>

<!-- LESSON MODAL -->
<div id="lessonModal" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h3>Add Lessons (Bulk)</h3>
            <span class="modal-close" onclick="closeModal('lessonModal')">✖</span>
        </div>

        @if(session('success_lesson'))
            <p class="success">{{ session('success_lesson') }}</p>
        @endif>

        <form method="POST" action="{{ route('admin.test-manager.lessons.store') }}">
            @csrf

            <select id="lesson_subject_id" name="subject_id" required>
                <option value="">-- select subject --</option>
                @foreach($subjects as $subject)
                    <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                @endforeach
            </select>

            <select id="lesson_topic_id" name="topic_id" required>
                <option value="">-- select subject first --</option>
            </select>

            <div id="lessonInputs">
                <input type="text" name="names[]" placeholder="e.g. Factoring Polynomials" required>
            </div>

            <button type="button" class="btn-secondary" onclick="addInput('lessonInputs')">
                + Add another
            </button>

            <div class="modal-actions">
                <button type="submit" class="btn-primary">Save All</button>
            </div>
        </form>
    </div>
</div>

<!-- COMPETENCY MODAL -->
<div id="competencyModal" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h3>Add Competencies (Bulk)</h3>
            <span class="modal-close" onclick="closeModal('competencyModal')">✖</span>
        </div>

        @if(session('success_competency'))
            <p class="success">{{ session('success_competency') }}</p>
        @endif

        <form method="POST" action="{{ route('admin.test-manager.competencies.store') }}">
            @csrf

            <select id="competency_subject_id" name="subject_id" required>
                <option value="">-- select subject --</option>
                @foreach($subjects as $subject)
                    <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                @endforeach
            </select>

            <select id="competency_topic_id" name="topic_id" required>
                <option value="">-- select subject first --</option>
            </select>

            <select id="competency_lesson_id" name="lesson_id" required>
                <option value="">-- select topic first --</option>
            </select>

            <div id="competencyInputs">
                <input type="text" name="names[]" placeholder="e.g. Solve polynomial equations" required>
            </div>

            <button type="button" class="btn-secondary" onclick="addInput('competencyInputs')">
                + Add another
            </button>

            <div class="modal-actions">
                <button type="submit" class="btn-primary">Save All</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('js/admin/test-manager.js') }}?v={{ time() }}" defer></script>
<script src="{{ asset('js/admin/question-config.js') }}?v={{ time() }}" defer></script>
@endpush

@push('scripts')
<script>
    window.routes = {
        topicsBySubject: "{{ url('/admin/test-manager/topics/by-subject') }}",
        lessonsByTopic:  "{{ url('/admin/test-manager/lessons/by-topic') }}",
        configStore: "{{ route('admin.configurations.store') }}",
        configDestroy: "{{ url('/admin/configurations') }}",
        configUpdateLabel: "{{ url('/admin/configurations') }}"
    };
    
    window.csrfToken = "{{ csrf_token() }}";
</script>
@endpush