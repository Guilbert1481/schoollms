@php
    $alpha = range('A','Z');
@endphp
<table style="width:100%; margin-bottom: 14px; table-layout: fixed;">
    <tr>
        <td style="vertical-align: top; width: 70%;">
            <!-- COLUMN A as before -->
            <ul style="list-style: none; padding-left: 0; margin-bottom: 0;">
                <li style="margin-bottom:4px; font-weight:bold; font-size:1.1em; letter-spacing:1px;">
                    <span style="display:inline-block; visibility:hidden; border-bottom:1.5px solid #222; min-width:34px; margin-right:7px;"></span>
                    COLUMN A
                </li>
                @foreach($prompts as $idx => $prompt)
                <li style="margin-bottom: 0.5em; display: flex; align-items: flex-start;">
                    <span style="display:inline-block; border-bottom:1.5px solid #222; min-width:34px; margin-right:7px; height:1.2em; flex-shrink:0;"></span>
                    <strong style="min-width:18px; margin-right:8px; display:inline-block; flex-shrink:0;">
                        {{ $idx+1 }}.
                    </strong>
                    <span style="display:block; flex:1; line-height:1.4;">{!! $prompt !!}</span>
                </li>
                @endforeach
            </ul>
        </td>
        <td style="
            vertical-align: top;
            width: 30%;
            min-width: 190px;
            padding-left: 60px;
        ">
            <ul style="list-style: none; padding-left: 0; margin-bottom: 0;">
                <li style="margin-bottom:4px; font-weight:bold; font-size:1.1em; letter-spacing:1px;">
                    COLUMN B
                </li>
                @foreach($shuffledAnswers as $ai => $answer)
                <li style="margin-bottom: 0.5em; display: flex; align-items: flex-start;">
                    <strong style="min-width:18px; margin-right:8px; display:inline-block; flex-shrink:0;">
                        {{ $alpha[$ai] }}.
                    </strong>
                    <span style="display:block; flex:1; line-height:1.4;">{!! $answer !!}</span>
                </li>
                @endforeach
            </ul>
        </td>
    </tr>
</table>