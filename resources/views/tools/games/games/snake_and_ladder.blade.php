{{-- ============================================================================
     Quiz Snakes & Ladders — PRACTICE mode (games catalog).

     Question supply: /tools/games/api/questions?type=mcq&difficulty=… (school-
     scoped, GameScope hamburger filters), the same client-graded practice feed
     every catalog game uses. Practice only — graded Snakes & Ladders runs live
     under Student → Assessments with server-authoritative answers AND dice.

     The board engine itself is shared: public/js/tools/games/snake-ladder-engine.js
     + public/css/tools/games/snake-ladder.css (plain CSS — no build step).
     ========================================================================== --}}

<link rel="stylesheet" href="{{ asset('css/tools/games/snake-ladder.css') }}">

<div data-game="snake-and-ladder">

    {{-- Config splash (start screen inside the partial — house pattern) --}}
    <div id="snlSetup" class="rounded-2xl border border-slate-200 bg-gradient-to-b from-sky-50 to-white p-6">
        <div class="text-center">
            <div class="text-3xl font-black italic uppercase tracking-wide text-slate-800">
                Quiz <span class="text-emerald-600">Snakes</span> &amp; <span class="text-amber-500">Ladders</span>
            </div>
            <p class="mt-1 text-sm text-slate-600">
                Answer correctly to roll the dice and race to tile 100 — climb the ladders, dodge the snakes!
            </p>
            <p id="snlScopeLine" class="mt-1 text-xs font-semibold text-emerald-700"></p>
        </div>

        <div class="mx-auto mt-5 grid max-w-md gap-3 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-slate-400">Questions</label>
                <select id="snlCount" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                    <option value="10">10</option>
                    <option value="15" selected>15</option>
                    <option value="20">20</option>
                    <option value="30">30</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-slate-400">Difficulty</label>
                <select id="snlDiff" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                    <option value="mixed" selected>Mixed</option>
                    <option value="average">Average</option>
                    <option value="advanced">Advanced</option>
                </select>
            </div>
        </div>

        <div class="mt-5 text-center">
            <button type="button" id="snlStart"
                    class="rounded-full bg-emerald-600 px-8 py-3 text-sm font-black uppercase tracking-wide text-white shadow-lg hover:bg-emerald-700">
                Start the Race
            </button>
            <p id="snlError" class="mt-3 hidden text-sm font-semibold text-rose-600"></p>
            <p class="mt-3 text-xs text-slate-400">
                Practice mode — missed questions come back around, and nothing here is graded.
                Best score on this device: <span id="snlBestScore" class="font-bold text-slate-600">0</span>
            </p>
        </div>
    </div>

    <div id="snlGameRoot"></div>
</div>

<script src="{{ asset('js/tools/games/snake-ladder-engine.js') }}"></script>
<script>
(function () {
    'use strict';
    var ENDPOINT = @json(route('tools.games.questions'));
    var el = function (id) { return document.getElementById(id); };

    // Mirrors SnakesBoard::defaultLayout() — the curated classic spread.
    var BOARD = {
        snakes: { 98: 78, 95: 56, 87: 24, 72: 51, 62: 42, 49: 30, 36: 15 },
        ladders: { 4: 25, 13: 46, 21: 58, 33: 70, 43: 77, 59: 81, 66: 88 }
    };

    var best = 0;
    try { best = parseInt(localStorage.getItem('snl_best') || '0', 10) || 0; } catch (e) {}
    el('snlBestScore').textContent = String(best);

    function refreshScope() {
        if (window.GameScope && typeof window.GameScope.summary === 'function') {
            el('snlScopeLine').textContent = window.GameScope.summary();
        }
    }
    document.addEventListener('gamescope:changed', refreshScope);
    refreshScope();

    function fetchBatch(limit, difficulty) {
        var params = new URLSearchParams({ type: 'mcq', limit: String(limit), difficulty: difficulty });
        var scope = window.GameScope || {};
        ['subject_id', 'topic_id', 'lesson_id', 'competency_id', 'academic_level_id'].forEach(function (k) {
            if (scope[k]) params.set(k, scope[k]);
        });
        return fetch(ENDPOINT + '?' + params.toString(), { headers: { Accept: 'application/json' } })
            .then(function (r) {
                if (!r.ok) throw new Error('Request failed (' + r.status + ')');
                return r.json();
            })
            .then(function (d) { return d.questions || []; });
    }

    // API row → engine question. Choice "ids" are synthetic; grading is local
    // (practice feed is client-graded, same as every catalog game).
    var nextUid = 1;
    function shape(row) {
        var uid = nextUid++;
        return {
            question_id: 'p' + uid,
            text: row.question,
            choices: row.choices.map(function (t, i) { return { id: uid * 10 + i, text: t }; }),
            _answerIdx: row.answer,
            _row: row
        };
    }

    el('snlStart').addEventListener('click', function () {
        var btn = el('snlStart'), err = el('snlError');
        var target = parseInt(el('snlCount').value, 10);
        var difficulty = el('snlDiff').value;
        btn.disabled = true; btn.textContent = 'Loading…'; err.classList.add('hidden');

        fetchBatch(target, difficulty).then(function (rows) {
            if (rows.length < 2) {
                err.textContent = rows.length === 0
                    ? 'No multiple-choice questions found for this selection — try "All subjects" or ask a teacher to add MCQ items.'
                    : 'Only 1 question found — at least 2 are needed for a race.';
                err.classList.remove('hidden');
                btn.disabled = false; btn.textContent = 'Start the Race';
                return;
            }

            var queue = rows.map(shape);

            var adapter = {
                meters: 'client',
                count: function () { return queue.length; },
                get: function (i) { return queue[i] || null; },
                submit: function (q, choiceId) {
                    var pickedIdx = q.choices.findIndex(function (c) { return c.id === choiceId; });
                    var correct = pickedIdx === q._answerIdx;
                    // Missed questions come back later in the race.
                    if (!correct) queue.push(shape(q._row));
                    return Promise.resolve({
                        correct: correct,
                        correct_choice_id: q.choices[q._answerIdx] ? q.choices[q._answerIdx].id : null
                    });
                },
                finish: function (summary) {
                    var attempted = summary.correct + summary.wrong;
                    var pct = attempted ? Math.round(summary.correct / attempted * 100) : 0;
                    var isBest = summary.score > best;
                    if (isBest) {
                        best = summary.score;
                        try { localStorage.setItem('snl_best', String(best)); } catch (e) {}
                    }
                    summary.show(
                        '<h2>' + (summary.board_completed ? '🏆 You reached tile 100!' : 'Race Complete!') + '</h2>' +
                        (isBest ? '<p style="color:#c98a12;font-weight:900">★ NEW PERSONAL BEST! ★</p>' : '') +
                        '<div class="snl-result-grid">' +
                          '<div class="snl-result-cell"><b>' + summary.score.toLocaleString() + '</b><span>Game score</span></div>' +
                          '<div class="snl-result-cell"><b>' + pct + '%</b><span>Accuracy</span></div>' +
                          '<div class="snl-result-cell"><b>Tile ' + summary.position + '</b><span>Final position</span></div>' +
                          '<div class="snl-result-cell"><b>x' + summary.best_streak + '</b><span>Best streak</span></div>' +
                        '</div>',
                        [
                            { label: 'Play Again', onClick: function () { window.location.reload(); } },
                            { label: 'Exit', onClick: function () {
                                if (window.parent !== window) window.parent.postMessage({ type: 'schoollms:game-exit' }, '*');
                                else window.location.href = @json(route('tools.games.index'));
                            } }
                        ]
                    );
                }
            };

            el('snlSetup').style.display = 'none';
            // Whole-page play: pin the game to the viewport and stop the page
            // behind it from scrolling.
            el('snlGameRoot').classList.add('snl-fixed');
            document.documentElement.style.overflow = 'hidden';
            SnakeLadder.mount({
                root: el('snlGameRoot'),
                adapter: adapter,
                board: BOARD,
                meta: {
                    subject: (window.GameScope && window.GameScope.summary) ? window.GameScope.summary() : 'Practice',
                    studentName: 'Player',
                    avatarUrl: @json(asset('images/games/avatar-placeholder.svg'))
                }
            });
        }).catch(function (e) {
            err.textContent = 'Could not load questions: ' + e.message;
            err.classList.remove('hidden');
            btn.disabled = false; btn.textContent = 'Start the Race';
        });
    });
})();
</script>
