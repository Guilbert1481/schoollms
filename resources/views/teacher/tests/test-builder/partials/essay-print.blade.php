{{-- resources/views/teacher/tests/test-builder/partials/essay-print.blade.php --}}
@if(!empty($q))
<div style="margin-bottom: 2.5em;">
    <div style="display: flex; align-items: flex-start;">
        <!-- Numbering -->
        <strong style="min-width: 18px; margin-right: 8px; display: inline-block; flex-shrink:0;">
            {{ $i + 1 }}.
        </strong>
        <!-- Essay Question Text -->
        <span style="flex:1; line-height:1.4;">
            {!! $q->question->question_text !!}
        </span>
    </div>
    
</div>
@endif