@php
    $showCompetency = $showCompetency ?? true;
@endphp

<div class="tpl-cd-wrapper">

    <div class="form-row">
        <label for="tpl-subject">Subject</label>
        <select id="tpl-subject" name="subject_id" required>
            <option value="">Select Subject</option>
            @foreach ($subjects as $subject)
                <option value="{{ $subject->id }}">{{ $subject->code }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-row">
        <label for="tpl-topic">Topic</label>
        <select id="tpl-topic" name="topic_id" disabled required>
            <option value="">Select Topic</option>
        </select>
    </div>
    
    <div class="form-row">
        <label for="tpl-lesson">Lesson / Title</label>
        <select id="tpl-lesson" name="lesson_id" disabled required>
            <option value="">Select Lesson</option>
        </select>
    </div>

    @if ($showCompetency)
    <div class="form-row">
        <label for="tpl-competency">Competency</label>
        <select id="tpl-competency" name="competency_id" disabled>
            <option value="">Select Competency</option>
        </select>
    </div>
    @endif

</div>


<!-- TO BE PASTED TO THE PAGE WHERE YOU WANT THIS CASCADING DROPDOWN TO BE USED

<div class="tpl-cd-wrapper">

    <div class="form-row">
        <label for="tpl-subject">Subject</label>
        <select id="tpl-subject" name="subject_id">
            <option value="">Select Subject</option>
            
        </select>
    </div>

    <div class="form-row">
        <label for="tpl-topic">Topic</label>
        <select id="tpl-topic" name="topic_id" disabled>
            <option value="">Select Topic</option>
        </select>
    </div>

    <div class="form-row">
        <label for="tpl-lesson">Lesson</label>
        <select id="tpl-lesson" name="lesson_id" disabled>
            <option value="">Select Lesson</option>
        </select>
    </div>

    <div class="form-row">
        <label for="tpl-competency">Competency</label>
        <select id="tpl-competency" name="competency_id" disabled>
            <option value="">Select Competency</option>
        </select>
    </div>

</div>
 

-->


<script>
    // This prevents the "selectedSubjects" crash from killing your dropdown script
    window.selectedSubjects = window.selectedSubjects || [];
</script>