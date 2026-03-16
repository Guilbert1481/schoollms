@php
    $letters = ['a','b','c','d','e','f','g','h','i','j','k','l','m'];
    $choices = $q->question->choices;
    $maxLen = collect($choices)->max(fn($c) => strlen($c->choice_text));
    $numChoices = count($choices);
    $splitAt = ceil($numChoices / 2);
@endphp

<p>
    <span class="question-number">{{ $i + 1 }}.</span>
    {{ $q->question->question_text }}
</p>

@if ($maxLen <= 15)
    <div class="choices-row">
        <div class="col">
            @for ($ci = 0; $ci < $splitAt; $ci++)
                @if (isset($choices[$ci]))
                    <div class="choice-line">
                        {{ $letters[$ci] }}. {{ $choices[$ci]->choice_text }}
                    </div>
                @endif
            @endfor
        </div>
        <div class="col">
            @for ($ci = $splitAt; $ci < $numChoices; $ci++)
                <div class="choice-line">
                    {{ $letters[$ci] }}. {{ $choices[$ci]->choice_text }}
                </div>
            @endfor
        </div>
    </div>
@else
    @foreach ($choices as $ci => $choice)
        <p class="choice-line">
            {{ $letters[$ci] }}. {{ $choice->choice_text }}
        </p>
    @endforeach
@endif