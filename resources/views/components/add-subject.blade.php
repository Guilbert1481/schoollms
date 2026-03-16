<!-- =======================
 ADD SUBJECT BUTTON
======================= -->
<button type="button" class="action-btn" id="addSubjectBtn">
    Add Subject
</button>

<!-- =======================
 ADD SUBJECT MODAL
======================= -->
<div id="addSubjectModal" class="modal-overlay">
    <div class="modal-box">
        <h3>Add Subject</h3>

        <form method="POST" action="{{ route('teacher.subjects.store') }}">

            @csrf


            <label for="subject_name">Subject Name</label>
            <input
                type="text"
                id="subject_name"
                name="name"
                required
                autofocus
            >

            <div class="modal-actions">
                <button type="submit" class="action-btn">Save</button>
                <button type="button" class="action-btn cancel" id="cancelAddSubject">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<link rel="stylesheet" href="{{ asset('css/modules/add-subject-topic-buttons.css') }}">
<script src="{{ asset('js/modules/add-subject-topic-buttons.js') }}"></script>

