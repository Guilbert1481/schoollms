<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $test->title }} — Print Version</title>
    <link rel="stylesheet" href="{{ asset('css/tests/printBuilder.css') }}?v={{ time() }}">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
        }
        .exam-header { text-align: center; margin-bottom: 25px; }
        .divider { margin: 30px 0 15px 0; }

        .print-container {
            /* Body is already centered by margin */
            max-width: 900px; /* or 1000px as desired */
            margin: 0 auto;
        }

        .no-print {
            margin-bottom: 20px;
        }

        .exam-info {
            display: flex; justify-content: space-between; margin-bottom: 12px;
        }
        .info-row {
            display: flex;
            align-items: center;
            margin-bottom: 4px;
        }
        .line-field {
            flex: 1; border-bottom: 1px solid #888;
            margin-left: 8px; min-width: 120px;
            height: 12px; display: inline-block;
        }
        .section-block {
            margin-bottom: 40px;
        }
        .section-title {
            font-weight: bold;
            margin-top: 24px;
            margin-bottom: 8px;
        }
        .question-block {
            margin-bottom: 2px;
            /* Keep an individual question intact across a page break, but let a
               whole section flow so it fills the page instead of jumping to the
               next one and leaving a large blank. */
            break-inside: avoid;
            page-break-inside: avoid;
        }
        .choice-line {
            /* Uniform, tight vertical spacing for every choice. The long-answer
               branch of the MCQ partial used <p> and inherited the browser's wide
               default margin, so some questions spaced their choices unevenly. */
            margin: 3px 0 3px 20px;
        }
        @media print {
            .no-print { display: none; }
            body { margin: 10mm 10mm 6mm 10mm; }
            .print-container {
                margin: 0;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    {{-- Print Button and Answer Key Button --}}
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" class="print-btn">
            Print Test
        </button>
        <a href="{{ route('teacher.tests.print-answer-key', ['test' => $test->id]) }}" 
            class="print-btn" 
            target="_blank"
            style="text-decoration: none; display: inline-block;">
            Answer Key
        </a>
    </div>

    <div class="print-container">
        {{-- Exam Header --}}
        <div class="exam-header">
            <div class="school-name">
                {{ $school_name ?? 'School Name' }}
            </div>
            @if(!empty($printHeader['academicYear']))
                <div class="academic-year">{{ $printHeader['academicYear'] }}</div>
            @endif
            @if(!empty($printHeader['level']))
                <div class="level-name">{{ $printHeader['level'] }}</div>
            @endif
            @if(!empty($printHeader['semester']))
                <div class="semester-name">{{ $printHeader['semester'] }}</div>
            @endif
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

        {{-- STUDENT INFO --}}
        <div class="exam-info">
            <div>
                <div class="info-row">
                    <span>Name:</span>
                    <div class="line-field"></div>
                </div>
                <div class="info-row">
                    <span>Teacher:</span>
                    <div class="line-field"></div>
                </div>
            </div>
            <div>
                <div class="info-row">
                    <span>Year:</span>
                    <div class="line-field"></div>
                </div>
                <div class="info-row">
                    <span>Block/Section:</span>
                    <div class="line-field"></div>
                </div>
            </div>
        </div>
        <hr class="divider">

        {{-- RENDER SECTIONS --}}
        @foreach ($sections as $section)
            <div class="section-block">
                <h3 class="section-title">
                    {{ $section['roman'] }}. {{ $section['title'] }}
                    @if(isset($section['points']))
                        ({{ $section['points'] }} {{ $section['points'] == 1 ? 'pt' : 'pts' }})
                    @endif
                </h3>
                <p><strong>Direction:</strong> {{ $section['direction'] }}</p>

                @if($section['title'] == 'Matching Type')
                    @includeIf('teacher.tests.test-builder.partials.matching-print', [
                        'prompts' => $section['prompts'] ?? [],
                        'shuffledAnswers' => $section['shuffledAnswers'] ?? [],
                    ])
                @else
                    @foreach ($section['questions'] as $i => $q)
                        <div class="question-block">
                            @includeIf('teacher.tests.test-builder.partials.' . $q->question->question_type . '-print', ['q' => $q, 'i' => $i])
                        </div>
                        <br>
                    @endforeach
                @endif
            </div>
        @endforeach
    </div>
    <div class="page-number"></div>
</body>
</html>