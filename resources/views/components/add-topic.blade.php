@php
    use App\Models\Subject;
    $subjects = Subject::orderBy('name')->get();
@endphp


<!-- =======================
 ADD TOPIC BUTTON
======================= -->
<button type="button" class="action-btn" id="addTopicBtn">
    Add Topic
</button>

<!-- =======================
 ADD TOPIC MODAL (HIDDEN)
======================= -->
<div id="addTopicModal" class="modal-overlay">
    <div class="modal-box">
        <h3>Add Topic</h3>

        <form method="POST" action="{{ route('teacher.topics.store') }}">
            @csrf

            <label>Subject</label>
            <select name="subject_id" required>
                @foreach ($subjects as $subject)
                    <option value="{{ $subject->id }}">
                        {{ $subject->name }}
                    </option>
                @endforeach
            </select>

            <label>Topic Name</label>
            <input type="text" name="name" required>

            <div class="modal-actions">
                <button type="submit" class="action-btn">Save</button>
                <button type="button" class="action-btn cancel" id="cancelAddTopic">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<link rel="stylesheet" href="{{ asset('css/modules/add-subject-topic-buttons.css') }}">
<script src="{{ asset('js/modules/add-subject-topic-buttons.js') }}"></script>

