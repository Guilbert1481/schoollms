{{-- Who Wants to Be a Millionaire? — playable MCQ game.
     Question supply: /tools/games/api/questions?type=mcq (school-scoped
     question bank; see GamesController::questions). Grades client-side —
     practice play, not an exam. --}}
<div data-game="millionaire" class="space-y-4">

    {{-- ============ START SCREEN ============ --}}
    <div id="mlStart" class="rounded-xl border border-slate-200 bg-white p-6">
        <h3 class="text-lg font-bold text-slate-800">Start a game</h3>
        <p class="mt-1 text-sm text-slate-600">
            Multiple-choice questions from your school's question bank. Climb the prize ladder — one wrong answer ends the run.
        </p>

        <div class="mt-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Practicing</div>
            <div id="mlScope" class="text-sm font-semibold text-slate-700">All subjects</div>
            <div class="mt-0.5 text-[11px] text-slate-400">Change content from the ☰ menu (top right).</div>
        </div>

        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Questions</label>
                <select id="mlCount" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="5">5</option>
                    <option value="10">10</option>
                    <option value="15" selected>15</option>
                </select>
            </div>
        </div>

        <div id="mlStartError" class="mt-3 hidden rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800"></div>

        <button type="button" id="mlStartBtn"
                class="mt-4 rounded-lg bg-amber-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-amber-700">
            Start Game
        </button>
    </div>

    {{-- ============ GAME SCREEN ============ --}}
    <div id="mlGame" class="hidden grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div id="mlProgress" class="text-xs font-semibold uppercase tracking-wide text-slate-500"></div>
            <div id="mlQuestion" class="mt-2 text-lg font-semibold text-slate-800"></div>
            <div id="mlOptions" class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-2"></div>
            <div id="mlFeedback" class="mt-4 hidden rounded-lg px-3 py-2 text-sm"></div>
        </div>

        <aside class="rounded-xl border border-slate-200 bg-white p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Prize Ladder</div>
            <ol id="mlLadder" class="space-y-1 text-sm"></ol>
            <div class="mt-3">
                <button type="button" id="mlFifty"
                        class="w-full rounded-lg border border-amber-300 bg-amber-50 px-2 py-1.5 text-xs font-bold text-amber-800 hover:bg-amber-100 disabled:cursor-not-allowed disabled:opacity-40">
                    50:50 (remove two wrong answers)
                </button>
            </div>
        </aside>
    </div>

    {{-- ============ END SCREEN ============ --}}
    <div id="mlEnd" class="hidden rounded-xl border border-slate-200 bg-white p-6 text-center">
        <div id="mlEndIcon" class="text-4xl"></div>
        <h3 id="mlEndTitle" class="mt-2 text-xl font-extrabold text-slate-800"></h3>
        <p id="mlEndDetail" class="mt-1 text-sm text-slate-600"></p>
        <div id="mlEndPrize" class="mt-3 text-3xl font-black text-amber-600"></div>
        <button type="button" id="mlAgainBtn"
                class="mt-5 rounded-lg bg-amber-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-amber-700">
            Play Again
        </button>
    </div>
</div>

<script>
(function () {
    const ENDPOINT = @json(route('tools.games.questions'));
    const PRIZES_FULL = [100, 200, 300, 500, 1000, 2000, 4000, 8000, 16000, 32000, 64000, 125000, 250000, 500000, 1000000];
    const LETTERS = ['A', 'B', 'C', 'D', 'E', 'F'];

    const el = (id) => document.getElementById(id);
    const peso = (n) => '₱' + Number(n).toLocaleString();

    let questions = [];
    let prizes = [];
    let current = 0;
    let fiftyUsed = false;
    let locked = false;

    function show(screen) {
        el('mlStart').classList.toggle('hidden', screen !== 'start');
        el('mlGame').classList.toggle('hidden', screen !== 'game');
        el('mlEnd').classList.toggle('hidden', screen !== 'end');
    }

    async function start() {
        const btn = el('mlStartBtn');
        const err = el('mlStartError');
        err.classList.add('hidden');
        btn.disabled = true;
        btn.textContent = 'Loading questions…';

        try {
            const params = new URLSearchParams({ type: 'mcq', limit: el('mlCount').value });
            const scope = window.GameScope || {};
            ['subject_id', 'topic_id', 'lesson_id', 'competency_id', 'academic_level_id'].forEach(k => {
                if (scope[k]) params.set(k, scope[k]);
            });
            const res = await fetch(ENDPOINT + '?' + params.toString(), { headers: { 'Accept': 'application/json' } });
            if (!res.ok) throw new Error('Request failed (' + res.status + ')');
            const data = await res.json();
            questions = data.questions || [];

            if (questions.length < 3) {
                err.textContent = questions.length === 0
                    ? 'No multiple-choice questions found for this selection. Ask a teacher to add MCQ items to the Question Bank first.'
                    : 'Only ' + questions.length + ' question(s) found — at least 3 are needed. Try "All subjects" or add more MCQ items.';
                err.classList.remove('hidden');
                return;
            }

            prizes = PRIZES_FULL.slice(0, questions.length);
            current = 0;
            fiftyUsed = false;
            el('mlFifty').disabled = false;
            renderQuestion();
            show('game');
        } catch (e) {
            err.textContent = 'Could not load questions: ' + e.message;
            err.classList.remove('hidden');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Start Game';
        }
    }

    function renderLadder() {
        el('mlLadder').innerHTML = prizes.map((amt, i) => {
            const active = i === current;
            return '<li class="flex items-center justify-between rounded px-2 py-1 '
                + (active ? 'bg-amber-50 ring-1 ring-amber-200 font-semibold' : (i < current ? 'text-emerald-600' : ''))
                + '"><span>Q' + (i + 1) + '</span><span>' + peso(amt) + '</span></li>';
        }).reverse().join('');
    }

    function renderQuestion() {
        const q = questions[current];
        locked = false;
        el('mlProgress').textContent = 'Question ' + (current + 1) + ' of ' + questions.length + ' — playing for ' + peso(prizes[current]);
        el('mlQuestion').textContent = q.question;
        el('mlFeedback').classList.add('hidden');
        el('mlOptions').innerHTML = q.choices.map((c, i) =>
            '<button type="button" data-idx="' + i + '" class="ml-opt text-left rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm hover:bg-amber-50 hover:border-amber-300">'
            + '<span class="font-bold">' + (LETTERS[i] || '?') + '.</span> ' + escapeHtml(c) + '</button>'
        ).join('');
        el('mlOptions').querySelectorAll('.ml-opt').forEach(btn => {
            btn.addEventListener('click', () => answer(parseInt(btn.dataset.idx, 10)));
        });
        renderLadder();
    }

    function answer(idx) {
        if (locked) return;
        locked = true;
        const q = questions[current];
        const buttons = el('mlOptions').querySelectorAll('.ml-opt');
        buttons.forEach(b => {
            const i = parseInt(b.dataset.idx, 10);
            b.disabled = true;
            if (i === q.answer) b.className = 'ml-opt text-left rounded-lg border border-emerald-400 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-800';
            else if (i === idx)  b.className = 'ml-opt text-left rounded-lg border border-rose-400 bg-rose-50 px-3 py-2 text-sm text-rose-700';
        });

        const fb = el('mlFeedback');
        if (idx === q.answer) {
            fb.className = 'mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800';
            fb.textContent = 'Correct! ' + (q.explanation ? q.explanation : '');
            fb.classList.remove('hidden');
            setTimeout(() => {
                current++;
                if (current >= questions.length) end(true);
                else renderQuestion();
            }, 1200);
        } else {
            fb.className = 'mt-4 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700';
            fb.textContent = 'Wrong! ' + (q.explanation ? q.explanation : '');
            fb.classList.remove('hidden');
            setTimeout(() => end(false), 1600);
        }
    }

    function fifty() {
        if (fiftyUsed || locked) return;
        fiftyUsed = true;
        el('mlFifty').disabled = true;
        const q = questions[current];
        const wrong = [];
        el('mlOptions').querySelectorAll('.ml-opt').forEach(b => {
            const i = parseInt(b.dataset.idx, 10);
            if (i !== q.answer) wrong.push(b);
        });
        wrong.sort(() => Math.random() - 0.5).slice(0, Math.min(2, wrong.length - 1)).forEach(b => {
            b.disabled = true;
            b.classList.add('opacity-30', 'line-through');
        });
    }

    function end(won) {
        const winnings = current > 0 ? prizes[current - 1] : 0;
        el('mlEndIcon').textContent = won ? '🏆' : '💥';
        el('mlEndTitle').textContent = won ? 'Congratulations — you reached the top!' : 'Game over!';
        el('mlEndDetail').textContent = won
            ? 'You answered all ' + questions.length + ' questions correctly.'
            : 'You answered ' + current + ' of ' + questions.length + ' correctly.';
        el('mlEndPrize').textContent = 'You take home ' + peso(won ? prizes[prizes.length - 1] : winnings);
        show('end');
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    el('mlStartBtn').addEventListener('click', start);
    el('mlAgainBtn').addEventListener('click', () => show('start'));
    el('mlFifty').addEventListener('click', fifty);

    // Scope line follows the ☰ menu selection.
    function refreshScope() {
        if (window.GameScope && typeof window.GameScope.summary === 'function') {
            el('mlScope').textContent = window.GameScope.summary();
        }
    }
    document.addEventListener('gamescope:changed', refreshScope);
    refreshScope();
})();
</script>
