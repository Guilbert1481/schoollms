@php
    $showCompetency = $showCompetency ?? true;
@endphp


<div class="cd-wrapper">

    <div class="form-row">
        <label for="cd-subject">Subject</label>
        <select id="cd-subject" name="subject_id" required>
            <option value="">Select Subject</option>
            @foreach ($subjects as $subject)
                <option value="{{ $subject->id }}">{{ $subject->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-row">
        <label for="cd-topic">Topic</label>
        <select id="cd-topic" name="topic_id" disabled required>
            <option value="">Select Topic</option>
        </select>
    </div>

    <div class="form-row">
        <label for="cd-lesson">Lesson / Title</label>
        <select id="cd-lesson" name="lesson_id" disabled required>
            <option value="">Select Lesson</option>
        </select>
    </div>

    @if ($showCompetency)
    <div class="form-row">
        <label for="cd-competency">Competency</label>
        <select id="cd-competency" name="competency_id" disabled>
            <option value="">Select Competency</option>
        </select>
    </div>
    @endif

</div>

