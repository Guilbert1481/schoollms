<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $test->title }} — PDF</title>

    <style>
        /* ===============================
           BASE STYLES
        =============================== */
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 14px;
            color: #000;
            margin: 0;
            padding: 0;
        }

        .page {
            width: 100%;
            padding: 20mm 15mm;
        }

        h3, p {
            margin: 0;
            padding: 0;
        }

        /* ===============================
           HEADER
        =============================== */
        .exam-header {
            text-align: center;
            margin-bottom: 12px;
        }

        .school-name {
            font-size: 20px;
            font-weight: 700;
        }

        .college-name {
            font-size: 16px;
            font-weight: 600;
            margin-top: 3px;
        }

        .exam-meta {
            font-size: 14px;
            margin-top: 4px;
        }

        .subject-name {
            font-size: 18px;
            font-weight: 700;
            margin-top: 4px;
        }

        .assessment-type {
            font-size: 14px;
            margin-top: 4px;
            color: #444;
        }

        /* ===============================
           DIVIDERS
        =============================== */
        .divider {
            border: none;
            border-top: 2px solid #555;
            margin: 12px 0;
        }

        /* ===============================
           STUDENT INFO
        =============================== */
        .exam-info {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }

        .info-col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .info-row {
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .line-field {
            flex: 1;
            border-bottom: 1px dotted #bbb;
            height: 16px;
        }

        /* ===============================
           SECTIONS + QUESTIONS
        =============================== */
        .section-title {
            font-weight: bold;
            margin-top: 10px;
            margin-bottom: 4px;
            font-size: 15px;
        }

        .question-block {
            margin-bottom: 4px;
            line-height: 1.4;
        }

        .question-number {
            font-weight: bold;
        }

        .choice-line {
            margin-left: 20px;
            margin-bottom: 2px;
        }

        /* ===============================
           PAGE BREAK RULES
        =============================== */
        .section-block {
            page-break-inside: avoid;
            break-inside: avoid;
            margin-bottom: 10px;
        }

        .question-block,
        .choice-line {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        h3 {
            page-break-after: avoid;
        }

        p {
            orphans: 3;
            widows: 3;
        }
    </style>
</head>

<body>

<div class="page">

    {{-- ========================= --}}
    {{--      EXAM HEADER          --}}
    {{-- ========================= --}}
    <div class="exam-header">

        <div class="school-name">
            {{ $school->name ?? 'Memory Ridge International Schools' }}
        </div>

        <div class="college-name">
            {{ $school->college_name ?? 'College of Education' }}
        </div>

        <div class="exam-meta">
            @if($test->class?->semester)
                {{ $test->class->semester->name }}
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
    </div>

    <hr class="divider">

    {{-- ========================= --}}
    {{--      STUDENT INFO         --}}
    {{-- ========================= --}}
    <div class="exam-info">

        <div class="info-col">
            <div class="info-row">
                <span>Name:</span>
                <div class="line-field"></div>
            </div>
            <div class="info-row">
                <span>Teacher:</span>
                <div class="line-field"></div>
            </div>
        </div>

        <div class="info-col">
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

    {{-- ========================= --}}
    {{--     RENDER SECTIONS       --}}
    {{-- ========================= --}}
    @foreach ($sections as $section)

        <div class="section-block">

            <h3 class="section-title">{{ $section['roman'] }}. {{ $section['title'] }}</h3>
            

            <br>

            @foreach ($section['questions'] as $i => $q)

                <p class="question-block">
                    <span class="question-number">{{ $i + 1 }}.</span>
                    {{ $q->question->question_text }}
                </p>

                @if ($q->question->question_type === 'mcq')
                    @php $letters = ['a','b','c','d','e']; @endphp
                    @foreach ($q->question->choices as $ci => $choice)
                        <p class="choice-line">
                            {{ $letters[$ci] }}. {{ $choice->choice_text }}
                        </p>
                    @endforeach
                @endif

                <br>

            @endforeach

        </div>

    @endforeach

</div>

</body>
</html>
