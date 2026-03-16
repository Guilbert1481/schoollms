<div class="test-details-card">

    <h3 class="panel-title">Learning Competency Coverage</h3>
    <p class="panel-subtitle">Question Bank Overview</p>

    <div class="meta">
        <p><strong>Lesson:</strong> {{ $test->title }}</p>

        <p>
            <strong>Status:</strong>
            <span class="badge badge-draft">
                {{ ucfirst($test->status ?? 'draft') }}
            </span>
        </p>

        <p><strong>Subject:</strong> {{ $test->subject->name ?? '-' }}</p>
        <p><strong>Topic:</strong> {{ $test->topic->name ?? '-' }}</p>
    </div>

    <hr>

    <h4 class="section-title">Learning Competency</h4>
    <div class="competency-box">
        {{ $test->description }}
    </div>

    @php
        $totalQuestions = $competencyBreakdown->sum('total');
    @endphp

    <div class="competency-summary">
        <span>Total Questions Available</span>
        <span class="question-count">{{ $totalQuestions }}</span>
    </div>

    <hr>

    <h4 class="section-title">Question Type Coverage</h4>

    <ul class="coverage-list">
        @forelse ($competencyBreakdown as $row)
            <li>
                <span>{{ ucfirst(str_replace('_',' ', $row->question_type)) }}</span>
                <span class="question-count">{{ $row->total }}</span>
            </li>
        @empty
            <li>No questions found.</li>
        @endforelse
    </ul>

    <div class="tip-box">
        Tip: Add more questions to support mastery and targeted intervention.
    </div>

</div>
