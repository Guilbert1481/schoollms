@extends('layouts.game')

@section('title', 'Quiz Speed Dash — '.$test->title)

@section('content')
{{-- Quiz Speed Dash — the GAME delivery of this online test attempt. The
     engine only ever receives question text + choice ids/text; correctness
     comes back from the server per answer (SpeedDashController@answer). --}}

<link rel="stylesheet" href="{{ asset('css/tools/games/speed-dash.css').'?v=3' }}">

<div id="sdGame" role="application" aria-label="Quiz Speed Dash game"></div>

{{-- Finishing is a normal POST so the redirect to the result page carries the
     session flash; the engine triggers it from the finish panel. --}}
<form id="sdFinishForm" method="POST" action="{{ route('student.assessments.game-finish', $attempt) }}" class="hidden">
    @csrf
</form>

@push('scripts')
<script src="{{ asset('js/tools/games/speed-dash-engine.js').'?v=3' }}"></script>
<script>
(function () {
    var PAYLOAD = @json($payload);
    var ANSWER_URL = @json(route('student.assessments.game-answer', $attempt));
    var CSRF = @json(csrf_token());

    var questions = PAYLOAD.questions;

    var adapter = {
        meters: 'server',
        count: function () { return questions.length; },
        get: function (i) { return questions[i] || null; },
        submit: function (q, choiceId, ms) {
            return fetch(ANSWER_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    question_id: q.question_id,
                    choice_id: choiceId,
                    response_ms: Math.min(ms, 3600000)
                })
            }).then(function (r) {
                if (r.status === 409) return r.json(); // expired → {expired, redirect}
                if (!r.ok) throw new Error('answer failed');
                return r.json();
            });
        },
        finish: function (summary) {
            var pct = questions.length
                ? Math.round(summary.correct / questions.length * 100) : 0;
            summary.show(
                '<h2>Finish Line!</h2>' +
                '<p>Your answers are locked in — submit to record your official score.</p>' +
                '<div class="sd-result-grid">' +
                  '<div class="sd-result-cell"><b>' + summary.correct + ' / ' + questions.length + '</b><span>Correct</span></div>' +
                  '<div class="sd-result-cell"><b>' + pct + '%</b><span>Accuracy</span></div>' +
                  '<div class="sd-result-cell"><b>' + summary.score + '</b><span>Game score</span></div>' +
                  '<div class="sd-result-cell"><b>x' + summary.best_streak + '</b><span>Best streak</span></div>' +
                '</div>' +
                '<p style="margin-top:10px;font-size:12px">Game score, hearts, and power-ups are just for fun — ' +
                'your official grade counts correct answers only.</p>',
                [{ label: 'Submit My Test', onClick: function () { document.getElementById('sdFinishForm').submit(); } }]
            );
        }
    };

    SpeedDash.mount({
        root: document.getElementById('sdGame'),
        adapter: adapter,
        settings: {
            startingLives: PAYLOAD.settings.starting_lives,
            instantSubmit: !!PAYLOAD.settings.instant_submit,
            powerupsEnabled: !!PAYLOAD.settings.powerups_enabled
        },
        game: PAYLOAD.game,
        timerSeconds: @json($remainingSeconds),
        meta: {
            subject: @json($test->subject?->name ?? 'Assessment'),
            level: @json($test->section?->name),
            studentName: @json($studentName),
            {{-- Hardcoded placeholder until real student photos are on file. --}}
            avatarUrl: @json(asset('images/games/avatar-placeholder.svg'))
        }
    });
})();
</script>
@endpush
@endsection
