{{-- ============================================================================
     The Endless Runner / Quiz Speed Dash — PRACTICE mode (games catalog).

     Question supply: /tools/games/api/questions?type=mcq&difficulty=… (school-
     scoped, GameScope hamburger filters), the same client-graded practice feed
     every catalog game uses. Practice only — graded Speed Dash runs live under
     Student → Assessments and are graded server-side.

     The runner engine itself is shared: public/js/tools/games/speed-dash-engine.js
     + public/css/tools/games/speed-dash.css (plain CSS — no build step).
     ========================================================================== --}}

<link rel="stylesheet" href="{{ asset('css/tools/games/speed-dash.css').'?v=3' }}">

<div data-game="speed-dash">

    {{-- Config splash (start screen inside the partial — house pattern) --}}
    <div id="sdSetup" class="rounded-2xl border border-slate-200 bg-gradient-to-b from-sky-50 to-white p-6">
        <div class="text-center">
            <div class="text-3xl font-black italic uppercase tracking-wide text-slate-800">
                Quiz <span class="text-cyan-600">Speed</span> <span class="text-amber-500">Dash</span>
            </div>
            <p class="mt-1 text-sm text-slate-600">
                Sprint through answer gates! Correct answers build your streak and speed — wrong ones cost a heart.
            </p>
            <p id="sdScopeLine" class="mt-1 text-xs font-semibold text-cyan-700"></p>
        </div>

        <div class="mx-auto mt-5 grid max-w-md gap-3 sm:grid-cols-3">
            <div>
                <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-slate-400">Questions</label>
                <select id="sdCount" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                    <option value="10">10</option>
                    <option value="15" selected>15</option>
                    <option value="20">20</option>
                    <option value="0">Endless</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-slate-400">Difficulty</label>
                <select id="sdDiff" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                    <option value="mixed" selected>Mixed</option>
                    <option value="average">Average</option>
                    <option value="advanced">Advanced</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-slate-400">Hearts</label>
                <select id="sdLives" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                    <option value="3" selected>3</option>
                    <option value="5">5</option>
                    <option value="1">1 (hard!)</option>
                </select>
            </div>
        </div>

        <div class="mt-5 text-center">
            <button type="button" id="sdStart"
                    class="rounded-full bg-cyan-600 px-8 py-3 text-sm font-black uppercase tracking-wide text-white shadow-lg hover:bg-cyan-700">
                Start Running
            </button>
            <p id="sdError" class="mt-3 hidden text-sm font-semibold text-rose-600"></p>
            <p class="mt-3 text-xs text-slate-400">
                Practice mode — missed questions come back around, and nothing here is graded.
                Best score on this device: <span id="sdBest" class="font-bold text-slate-600">0</span>
            </p>
        </div>
    </div>

    <div id="sdGameRoot"></div>
</div>

<script src="{{ asset('js/tools/games/speed-dash-engine.js').'?v=3' }}"></script>
<script>
(function () {
    'use strict';
    var ENDPOINT = @json(route('tools.games.questions'));
    var el = function (id) { return document.getElementById(id); };

    var best = 0;
    try { best = parseInt(localStorage.getItem('sd_best') || '0', 10) || 0; } catch (e) {}
    el('sdBest').textContent = String(best);

    function refreshScope() {
        if (window.GameScope && typeof window.GameScope.summary === 'function') {
            el('sdScopeLine').textContent = window.GameScope.summary();
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

    // API row → engine question. Choice "ids" are synthetic indexes; grading is
    // local (practice feed is client-graded, same as every catalog game).
    var nextUid = 1;
    function shape(row) {
        var uid = nextUid++;
        return {
            question_id: 'p' + uid,
            text: row.question,
            choices: row.choices.map(function (t, i) { return { id: uid * 10 + i, text: t }; }),
            _answerIdx: row.answer,
            _explanation: row.explanation || null,
            _row: row
        };
    }

    el('sdStart').addEventListener('click', function () {
        var btn = el('sdStart'), err = el('sdError');
        var target = parseInt(el('sdCount').value, 10);   // 0 = endless
        var difficulty = el('sdDiff').value;
        var lives = parseInt(el('sdLives').value, 10);
        btn.disabled = true; btn.textContent = 'Loading…'; err.classList.add('hidden');

        fetchBatch(target || 15, difficulty).then(function (rows) {
            if (rows.length < 2) {
                err.textContent = rows.length === 0
                    ? 'No multiple-choice questions found for this selection — try "All subjects" or ask a teacher to add MCQ items.'
                    : 'Only 1 question found — at least 2 are needed for a run.';
                err.classList.remove('hidden');
                btn.disabled = false; btn.textContent = 'Start Running';
                return;
            }

            var queue = rows.map(shape);
            var answeredCount = 0;
            var fetching = false;

            var adapter = {
                meters: 'client',
                count: function () { return target || null; },
                get: function (i) {
                    if (target && answeredCount >= target) return null;
                    var q = queue[i];
                    // Endless: top the queue up in the background before it runs dry.
                    if (!target && !fetching && i >= queue.length - 3) {
                        fetching = true;
                        fetchBatch(15, difficulty).then(function (more) {
                            more.forEach(function (r) { queue.push(shape(r)); });
                            fetching = false;
                        }).catch(function () { fetching = false; });
                    }
                    return q || null;
                },
                submit: function (q, choiceId) {
                    answeredCount++;
                    var pickedIdx = q.choices.findIndex(function (c) { return c.id === choiceId; });
                    var correct = pickedIdx === q._answerIdx;
                    if (!correct) {
                        // Missed questions come back later in the run (re-shaped so
                        // the resume-skip logic never confuses it with the original).
                        queue.push(shape(q._row));
                    }
                    return Promise.resolve({
                        correct: correct,
                        correct_choice_id: q.choices[q._answerIdx] ? q.choices[q._answerIdx].id : null,
                        explanation: q._explanation
                    });
                },
                finish: function (summary) {
                    var attempted = summary.correct + summary.wrong;
                    var pct = attempted ? Math.round(summary.correct / attempted * 100) : 0;
                    var isBest = summary.score > best;
                    if (isBest) {
                        best = summary.score;
                        try { localStorage.setItem('sd_best', String(best)); } catch (e) {}
                    }
                    summary.show(
                        '<h2>Run Complete!</h2>' +
                        (isBest ? '<p style="color:#ffd257;font-weight:900">★ NEW PERSONAL BEST! ★</p>' : '') +
                        '<div class="sd-result-grid">' +
                          '<div class="sd-result-cell"><b>' + summary.score + '</b><span>Game score</span></div>' +
                          '<div class="sd-result-cell"><b>' + pct + '%</b><span>Accuracy</span></div>' +
                          '<div class="sd-result-cell"><b>' + summary.correct + '</b><span>Correct</span></div>' +
                          '<div class="sd-result-cell"><b>x' + summary.best_streak + '</b><span>Best streak</span></div>' +
                        '</div>',
                        [
                            { label: 'Play Again', onClick: function () { window.location.reload(); } },
                            { label: 'Exit', alt: true, onClick: function () {
                                if (window.parent !== window) window.parent.postMessage({ type: 'schoollms:game-exit' }, '*');
                                else window.location.href = @json(route('tools.games.index'));
                            } }
                        ]
                    );
                }
            };

            el('sdSetup').style.display = 'none';
            SpeedDash.mount({
                root: el('sdGameRoot'),
                adapter: adapter,
                settings: { startingLives: lives, instantSubmit: true, powerupsEnabled: true },
                meta: {
                    subject: (window.GameScope && window.GameScope.summary) ? window.GameScope.summary() : 'Practice',
                    studentName: 'Player',
                    avatarUrl: @json(asset('images/games/avatar-placeholder.svg'))
                }
            });
        }).catch(function (e) {
            err.textContent = 'Could not load questions: ' + e.message;
            err.classList.remove('hidden');
            btn.disabled = false; btn.textContent = 'Start Running';
        });
    });
})();
</script>
