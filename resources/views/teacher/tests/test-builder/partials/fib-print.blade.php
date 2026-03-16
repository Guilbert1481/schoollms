{{-- resources/views/teacher/tests/test-builder/partials/fib-print.blade.php --}}
@if(!empty($q))
<div style="display: flex; align-items: flex-start; margin-bottom: -1.3em;">
    <!-- Numbering, fixed width -->
    <strong style="min-width: 18px; margin-right: 8px; display: inline-block; flex-shrink:0;">
        {{ $i + 1 }}.
    </strong>
    <span style="flex:1; line-height:1.4;">
        {!! preg_replace(
            '/\{\{blank\}\}/i',
            '<span style="display: inline-block; border-bottom: 1.5px solid #222; min-width: 64px; height: 1.2em; vertical-align: middle; margin: 0 4px; flex-shrink:0;"></span>',
            e($q->question->question_text)
        ) !!}
    </span>
</div>
@endif