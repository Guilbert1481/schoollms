@extends('layouts.game')

@section('title', 'Quiz Snakes & Ladders — '.$test->title)

@section('content')
{{-- Quiz Snakes & Ladders — the BOARD-GAME delivery of this online test
     attempt. The engine only ever receives question text + choice ids/text;
     correctness comes back per answer and every dice roll is generated on the
     server (SnakeLadderController@answer / @roll). --}}

<link rel="stylesheet" href="{{ asset('css/tools/games/snake-ladder.css') }}">

{{-- snl-fixed pins the board to the viewport: one whole-page screen, no scroll. --}}
<style> html, body { overflow: hidden; } </style>
<div id="snlGame" class="snl-fixed" role="application" aria-label="Quiz Snakes and Ladders game"></div>

{{-- Finishing is a normal POST so the redirect to the result page carries the
     session flash; the engine triggers it from the finish panel. --}}
<form id="snlFinishForm" method="POST" action="{{ route('student.assessments.board-finish', $attempt) }}" class="hidden">
    @csrf
</form>

@push('scripts')
<script src="{{ asset('js/tools/games/snake-ladder-engine.js') }}"></script>
<script>
(function () {
    var PAYLOAD = @json($payload);
    var ANSWER_URL = @json(route('student.assessments.board-answer', $attempt));
    var ROLL_URL = @json(route('student.assessments.board-roll', $attempt));
    var CSRF = @json(csrf_token());

    var questions = PAYLOAD.questions;

    function post(url, body) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json'
            },
            body: JSON.stringify(body)
        }).then(function (r) {
            if (r.status === 409) return r.json(); // expired → {expired, redirect}
            if (!r.ok) throw new Error('request failed');
            return r.json();
        });
    }

    var adapter = {
        meters: 'server',
        count: function () { return questions.length; },
        get: function (i) { return questions[i] || null; },
        submit: function (q, choiceId) {
            return post(ANSWER_URL, { question_id: q.question_id, choice_id: choiceId });
        },
        roll: function (q) {
            return post(ROLL_URL, { question_id: q.question_id });
        },
        finish: function (summary) {
            var pct = questions.length
                ? Math.round(summary.correct / questions.length * 100) : 0;
            summary.show(
                '<h2>' + (summary.board_completed ? '🏆 You reached tile 100!' : 'Game Complete!') + '</h2>' +
                '<p>Your answers are locked in — submit to record your official score.</p>' +
                '<div class="snl-result-grid">' +
                  '<div class="snl-result-cell"><b>' + summary.correct + ' / ' + questions.length + '</b><span>Correct</span></div>' +
                  '<div class="snl-result-cell"><b>' + pct + '%</b><span>Accuracy</span></div>' +
                  '<div class="snl-result-cell"><b>Tile ' + summary.position + '</b><span>Final position</span></div>' +
                  '<div class="snl-result-cell"><b>' + summary.score.toLocaleString() + '</b><span>Game score</span></div>' +
                '</div>' +
                '<p style="font-size:12px">The board, dice, and shields are just for fun — ' +
                'your official grade counts correct answers only.</p>',
                [{ label: 'Submit My Test', onClick: function () { document.getElementById('snlFinishForm').submit(); } }]
            );
        }
    };

    SnakeLadder.mount({
        root: document.getElementById('snlGame'),
        adapter: adapter,
        settings: PAYLOAD.settings,
        game: PAYLOAD.game,
        board: PAYLOAD.game.board,
        timerSeconds: @json($remainingSeconds),
        meta: {
            subject: @json($test->subject?->name ?? 'Assessment'),
            level: @json($test->section?->name),
            studentName: @json($studentName),
            {{-- Real photo when uploaded; friendly placeholder until then. --}}
            avatarUrl: @json($avatarUrl)
        }
    });
})();
</script>
@endpush
@endsection
