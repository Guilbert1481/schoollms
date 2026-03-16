{{-- resources/views/teacher/tests/test-builder/partials/mtf-print.blade.php --}}
@if(!empty($q))
<div style="display: flex; align-items: flex-start; margin-bottom: -1.3em;">
    <!-- Answer line + number, fixed width -->
    <span style="
        display: inline-block;
        border-bottom: 1.5px solid #222;
        min-width: 60px;
        margin-right: 6px;
        height: 1.2em;
        flex-shrink:0;
    "></span>
    <strong style="min-width: 18px; margin-right: 8px; display: inline-block; flex-shrink:0;">
        {{ $i + 1 }}.
    </strong>
    <!-- Actual question text, takes rest of row & all wraps align under first line -->
    <span style="display:block; flex:1; line-height:1.4;">
        {!! $q->question->question_text !!}
    </span>
</div>
@endif