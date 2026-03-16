<div class="exam-paper">

    <div class="exam-header">

        <div class="school-letterhead">
            SCHOOL LETTERHEAD WITH LOGO
            <span class="note">(Default by school admin but editable by teacher)</span>
        </div>

        <div class="college-name">
            COLLEGE OF <span class="highlight-red">EDUCATION</span>
        </div>

        <div class="exam-term">
            <span class="highlight-red">1st Semester</span>
            <span class="highlight-blue">Midterm Examination</span>
        </div>

        <div class="exam-subject">
            Mathematics
        </div>

    </div>

    <hr class="divider">

    {{-- STUDENT META --}}
    <table class="exam-meta-table">
        <tr>
            <td class="label">Name:</td>
            <td class="fill">______________________________________________</td>

            <td class="label right">Year / Section:</td>
            <td class="fill">______________________________________________</td>
        </tr>

        <tr>
            <td class="label">Teacher:</td>
            <td class="fill">______________________________________________</td>

            <td class="label right">Date:</td>
            <td class="fill">______________________________________________</td>
        </tr>
    </table>

    <hr>

    {{-- INSTRUCTIONS --}}
    <div class="exam-instructions">
        <strong>Instructions:</strong>
        Answer all questions. Read each question carefully.
        No notes or electronic devices allowed.
    </div>

    {{-- QUESTIONS --}}
    @php
        $grouped = $test->questions->groupBy('type');
    @endphp

    @foreach ($grouped as $type => $questions)
        <div class="exam-section">
            <div class="section-title">
                {{ strtoupper($type) }}
            </div>

            @foreach ($questions as $index => $question)
                <div class="question">
                    {{ $index + 1 }}. {{ $question->question }}

                    @if ($type === 'true_false')
                        <span class="tf-line">____</span>
                    @endif

                    @if ($type === 'mcq')
                        <div class="choices">
                            @foreach ($question->choices as $choice)
                                <div class="choice">
                                    ( ) {{ $choice->choice }}
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($type === 'essay')
                        <div class="essay-space"></div>
                        <div class="essay-space"></div>
                    @endif
                </div>
            @endforeach
        </div>
    @endforeach

</div>
