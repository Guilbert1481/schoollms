@php
    $showCompetency = $showCompetency ?? true;
@endphp


<div class="cd-wrapper">

    {{-- Subject is gated on the Assessment Levels picker: it stays disabled
         until a grade/year level is chosen, then it's populated (by
         testBuilder.js) with only the subjects that have questions at that
         level. data-selected carries the saved subject for the edit page. --}}
    <div class="form-row">
        <label for="cd-subject">Subject</label>
        <select id="cd-subject" name="subject_id" required disabled
                data-selected="{{ optional($test ?? null)->subject_id }}">
            <option value="">Select grade / year level first</option>
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

