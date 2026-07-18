<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $test->title }} — Answer Key</title>
    <link rel="stylesheet" href="{{ asset('css/tests/printBuilder.css') }}?v={{ time() }}">
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .exam-header { text-align: center; margin-bottom: 25px; }
        .divider { margin: 30px 0 15px 0; }
        
        .section-columns {
            column-count: 2;
            column-gap: 32px;
        }
        .section-block {
            display: inline-block;
            width: 100%;
            vertical-align: top;
            break-inside: avoid;
            page-break-inside: avoid;
        }
        .section-title { font-weight: bold; margin-top: 24px; margin-bottom: 10px; }
        ol.question-list {
            margin: 0;
            padding-left: 36px; /* aligns numbers under the header */
            margin-bottom: 12px;
        }
        ol.question-list li {
            margin-bottom: 3px;
            font-size: 1rem;
        }
        @media print {
            .no-print { display: none; }
            body { margin: 10mm 10mm 6mm 10mm; }
            .section-columns { gap: 0 18mm; }
        }
    </style>
</head>
<body>
<div class="no-print" style="margin-bottom: 20px;">
    <button onclick="window.print()" class="print-btn">Print Answer Key</button>
</div>

<div class="exam-header">
    <div class="school-name">
        {{ $school->name ?? 'Memory Ridge International Schools' }}
    </div>
    <div class="college-name">
        {{ $school->college_name ?? 'College of Education' }}
    </div>
    <div class="exam-meta">
        @if($test->class?->semester)
            {{ $test->class->semester->name }}<br>
        @endif
        @if($test->settings->term)
            {{ ucfirst($test->settings->term) }} Examination in
        @endif
    </div>
    <div class="subject-name">
        {{ $test->subject->name ?? 'Subject Name' }}
    </div>
    <div class="assessment-type">
        {{ $test->settings->assessment_type ?? 'Assessment Type' }}
    </div>
    @if (! empty($printHeader['coverage']))
        <div class="coverage-name">{{ $printHeader['coverage'] }}</div>
    @endif
</div>
<hr class="divider">
ANSWER KEY

<div class="section-columns">
@php $roman = ['I','II','III','IV','V','VI','VII','VIII']; $sn=0; @endphp
@foreach($sections as $section)
    <div class="section-block">
        <div class="section-title">
            {{ $section['roman'] ?? $roman[$sn] }}. {{ $section['title'] }}
        </div>
        <ol class="question-list">
        @foreach ($section['questions'] as $i => $q)
            <li>
                @php
    switch($q->print_type) {
        case 'mcq':
            $correct = $q->question->choices->firstWhere('is_correct', true);
            if($correct) {
                // MCQ: Display letter and answer text
                $letter = $correct->label
                        ?? chr(97 + $q->question->choices->search(fn($c) => $c->id === $correct->id)); // a,b,c...
                echo "{$letter} &emsp;   {$correct->choice_text}";
            } else {
                echo '—';
            }
            break;

        case 'matching':
            $columnB = $section['columnB'] ?? collect();
            $correct = $q->question->choices->firstWhere('is_correct', true);
            if($correct && $columnB->count()) {
                $idx = $columnB->search(function($c) use ($correct) {
                    // Compare by id if present, else by text
                    return isset($c->id, $correct->id)
                        ? $c->id == $correct->id
                        : $c->choice_text == $correct->choice_text;
                });
                $letter = $idx !== false ? strtoupper(chr(65 + $idx)) : '?';
                echo "{$letter}  &emsp;  {$correct->choice_text}";
            } else {
                echo '—';
            }
            break;

        case 'true_false':
        case 'mtf':
            $correct = $q->question->choices->firstWhere('is_correct', true);
            echo $correct ? $correct->choice_text : '—';
            break;

        case 'identification':
        case 'fib':
            $choices = $q->question->choices
                ->where('is_correct', true)
                ->sortBy(function($choice){
                    $meta = json_decode($choice->meta, true) ?? [];
                    return $meta['blank_index'] ?? 0;
                })
                ->pluck('choice_text')
                ->toArray();
            echo !empty($choices) ? implode(', ', $choices) : ($q->question->correct_answer ?? '—');
            break;

        case 'enumeration':
            if (is_array($q->question->correct_answers)) {
                echo implode(', ', $q->question->correct_answers);
            } else {
                $corrects = $q->question->choices
                    ? $q->question->choices->where('is_correct', true)->pluck('choice_text')->toArray()
                    : [];
                echo !empty($corrects)
                    ? implode(', ', $corrects)
                    : ($q->question->correct_answers ?? '—');
            }
            break;

        case 'essay':
            echo 'Teacher will check';
            break;

        default:
            echo '—';
    }
@endphp
            </li>
        @endforeach
        </ol>
    </div>
    @php $sn++; @endphp
@endforeach
</div>
</body>
</html>