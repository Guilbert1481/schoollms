{{-- resources/views/teacher/tests/test-builder/partials/enumeration-print.blade.php --}}
@if(!empty($q))
<div style="margin-bottom: -1.5em;">
    <div style="display: flex; align-items: flex-start;">
        <!-- Numbering -->
        <strong style="min-width: 18px; margin-right: 8px; display: inline-block; flex-shrink:0;">
            {{ $i + 1 }}.
        </strong>
        <!-- Enumeration Question Text -->
        <span style="flex:1; line-height:1.4;">
            {!! $q->question->question_text !!}
        </span>
    </div>
    @php
        // How many items should the student enumerate? Default to 3 if not set.
        $count = $q->question->enum_count ?? 3;
    @endphp
    <ol type="a" style="margin-left:36px; margin-top:4px;">
        @for ($j = 0; $j < $count; $j++)
            <li style="margin-bottom: 7px;">
                <span style="display: inline-block; border-bottom: 1.5px solid #222; min-width: 120px; height: 1.2em;"></span>
            </li>
        @endfor
    </ol>
</div>
@endif