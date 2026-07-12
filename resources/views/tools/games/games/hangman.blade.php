{{-- Hangman — playable Identification game.
     Question supply: /tools/games/api/questions?type=identification
     (school-scoped question bank; answer = the identification keyword).
     The question text is the clue; guess the answer letter by letter. --}}
<div data-game="hangman" class="space-y-4">

    {{-- ============ START SCREEN ============ --}}
    <div id="hmStart" class="rounded-xl border border-slate-200 bg-white p-6">
        <h3 class="text-lg font-bold text-slate-800">Start a game</h3>
        <p class="mt-1 text-sm text-slate-600">
            Identification questions from your school's question bank. Read the clue and guess the answer one letter
            at a time — six wrong guesses and the round is lost.
        </p>

        <div class="mt-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Practicing</div>
            <div id="hmScope" class="text-sm font-semibold text-slate-700">All subjects</div>
            <div class="mt-0.5 text-[11px] text-slate-400">Change content from the ☰ menu (top right).</div>
        </div>

        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Rounds</label>
                <select id="hmCount" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="5">5</option>
                    <option value="10" selected>10</option>
                    <option value="15">15</option>
                </select>
            </div>
        </div>

        <div id="hmStartError" class="mt-3 hidden rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800"></div>

        <button type="button" id="hmStartBtn"
                class="mt-4 rounded-lg bg-slate-800 px-5 py-2.5 text-sm font-bold text-white hover:bg-slate-700">
            Start Game
        </button>
    </div>

    {{-- ============ GAME SCREEN ============ --}}
    <div id="hmGame" class="hidden space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div id="hmProgress" class="text-xs font-semibold uppercase tracking-wide text-slate-500"></div>
            <div class="flex items-center gap-3">
                <div id="hmScore" class="text-xs font-bold text-emerald-700"></div>
                <div id="hmLives" class="text-base tracking-wide"></div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Clue</div>
            <div id="hmClue" class="mt-1 text-lg font-semibold text-slate-800"></div>
        </div>

        <div id="hmWord" class="flex flex-wrap justify-center gap-1.5 py-3"></div>

        <div id="hmRoundMsg" class="hidden rounded-lg px-3 py-2 text-sm text-center"></div>

        <div id="hmKeyboard" class="flex flex-wrap justify-center gap-1.5"></div>

        <div class="text-center">
            <button type="button" id="hmNextBtn"
                    class="hidden rounded-lg bg-slate-800 px-5 py-2 text-sm font-bold text-white hover:bg-slate-700">
                Next Round →
            </button>
        </div>
    </div>

    {{-- ============ END SCREEN ============ --}}
    <div id="hmEnd" class="hidden rounded-xl border border-slate-200 bg-white p-6 text-center">
        <div id="hmEndIcon" class="text-4xl"></div>
        <h3 class="mt-2 text-xl font-extrabold text-slate-800">Game finished!</h3>
        <div id="hmEndScore" class="mt-2 text-3xl font-black text-slate-800"></div>
        <p id="hmEndDetail" class="mt-1 text-sm text-slate-600"></p>
        <button type="button" id="hmAgainBtn"
                class="mt-5 rounded-lg bg-slate-800 px-5 py-2.5 text-sm font-bold text-white hover:bg-slate-700">
            Play Again
        </button>
    </div>
</div>

<script>
(function () {
    const ENDPOINT = @json(route('tools.games.questions'));
    const MAX_WRONG = 6;
    const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('');

    const el = (id) => document.getElementById(id);

    let questions = [];
    let round = 0;
    let score = 0;
    let answer = '';
    let guessed = new Set();
    let wrong = 0;
    let roundOver = false;
    let gameActive = false;

    function show(screen) {
        el('hmStart').classList.toggle('hidden', screen !== 'start');
        el('hmGame').classList.toggle('hidden', screen !== 'game');
        el('hmEnd').classList.toggle('hidden', screen !== 'end');
        gameActive = screen === 'game';
    }

    async function start() {
        const btn = el('hmStartBtn');
        const err = el('hmStartError');
        err.classList.add('hidden');
        btn.disabled = true;
        btn.textContent = 'Loading questions…';

        try {
            const params = new URLSearchParams({ type: 'identification', limit: el('hmCount').value });
            const scope = window.GameScope || {};
            ['subject_id', 'topic_id', 'lesson_id', 'competency_id', 'academic_level_id'].forEach(k => {
                if (scope[k]) params.set(k, scope[k]);
            });
            const res = await fetch(ENDPOINT + '?' + params.toString(), { headers: { 'Accept': 'application/json' } });
            if (!res.ok) throw new Error('Request failed (' + res.status + ')');
            const data = await res.json();
            questions = (data.questions || []).filter(q => /[a-z]/i.test(q.answer));

            if (questions.length === 0) {
                err.textContent = 'No identification questions found for this selection. Ask a teacher to add Identification items to the Question Bank first.';
                err.classList.remove('hidden');
                return;
            }

            round = 0;
            score = 0;
            show('game');
            startRound();
        } catch (e) {
            err.textContent = 'Could not load questions: ' + e.message;
            err.classList.remove('hidden');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Start Game';
        }
    }

    function startRound() {
        const q = questions[round];
        answer = q.answer.toUpperCase();
        guessed = new Set();
        wrong = 0;
        roundOver = false;

        el('hmProgress').textContent = 'Round ' + (round + 1) + ' of ' + questions.length;
        el('hmClue').textContent = q.question;
        el('hmRoundMsg').classList.add('hidden');
        el('hmNextBtn').classList.add('hidden');
        renderWord();
        renderLives();
        renderScore();
        renderKeyboard();
    }

    function renderWord() {
        el('hmWord').innerHTML = answer.split('').map(ch => {
            if (!/[A-Z]/.test(ch)) {
                // Spaces, digits, punctuation are shown for free.
                const label = ch === ' ' ? '&nbsp;' : escapeHtml(ch);
                return '<span class="flex h-10 w-7 items-end justify-center text-lg font-black text-slate-500">' + label + '</span>';
            }
            const revealed = guessed.has(ch) || roundOver;
            return '<span class="flex h-10 w-7 items-end justify-center border-b-2 '
                + (revealed ? 'border-slate-300 text-slate-800' : 'border-slate-400 text-transparent')
                + ' text-lg font-black">' + (revealed ? ch : '·') + '</span>';
        }).join('');
    }

    function renderLives() {
        el('hmLives').innerHTML = '❤️'.repeat(MAX_WRONG - wrong) + '🖤'.repeat(wrong);
    }

    function renderScore() {
        el('hmScore').textContent = 'Score: ' + score + ' / ' + questions.length;
    }

    function renderKeyboard() {
        el('hmKeyboard').innerHTML = ALPHABET.map(l => {
            const used = guessed.has(l);
            const inWord = answer.includes(l);
            let cls = 'rounded-md border px-0 py-1.5 w-8 text-sm font-bold ';
            if (used) cls += inWord ? 'border-emerald-300 bg-emerald-100 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-400';
            else if (roundOver) cls += 'border-slate-200 bg-slate-50 text-slate-300';
            else cls += 'border-slate-300 bg-white text-slate-700 hover:bg-slate-100';
            return '<button type="button" data-letter="' + l + '" ' + ((used || roundOver) ? 'disabled' : '') + ' class="hm-key ' + cls + '">' + l + '</button>';
        }).join('');
        el('hmKeyboard').querySelectorAll('.hm-key').forEach(b => {
            b.addEventListener('click', () => guess(b.dataset.letter));
        });
    }

    function guess(letter) {
        if (roundOver || guessed.has(letter)) return;
        guessed.add(letter);

        if (!answer.includes(letter)) {
            wrong++;
            if (wrong >= MAX_WRONG) return endRound(false);
        } else {
            const allRevealed = answer.split('').every(ch => !/[A-Z]/.test(ch) || guessed.has(ch));
            if (allRevealed) return endRound(true);
        }

        renderWord();
        renderLives();
        renderKeyboard();
    }

    function endRound(won) {
        roundOver = true;
        if (won) score++;

        const q = questions[round];
        const msg = el('hmRoundMsg');
        msg.className = won
            ? 'rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-center text-emerald-800'
            : 'rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-center text-rose-700';
        msg.textContent = (won ? 'Correct! ' : 'Out of lives — the answer was "' + q.answer + '". ')
            + (q.explanation ? q.explanation : '');
        msg.classList.remove('hidden');

        renderWord();
        renderLives();
        renderScore();
        renderKeyboard();

        el('hmNextBtn').textContent = round + 1 >= questions.length ? 'See Results →' : 'Next Round →';
        el('hmNextBtn').classList.remove('hidden');
    }

    function next() {
        round++;
        if (round >= questions.length) return end();
        startRound();
    }

    function end() {
        const pct = Math.round(score / questions.length * 100);
        el('hmEndIcon').textContent = pct >= 80 ? '🏆' : (pct >= 50 ? '🎯' : '📖');
        el('hmEndScore').textContent = score + ' / ' + questions.length;
        el('hmEndDetail').textContent = pct >= 80
            ? 'Outstanding — you really know this topic!'
            : (pct >= 50 ? 'Good effort — a little more review and you\'ll ace it.' : 'Keep studying and try again!');
        show('end');
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    // Physical keyboard support while a round is active.
    document.addEventListener('keydown', (e) => {
        if (!gameActive || roundOver) return;
        const l = e.key.toUpperCase();
        if (ALPHABET.includes(l)) guess(l);
    });

    el('hmStartBtn').addEventListener('click', start);
    el('hmNextBtn').addEventListener('click', next);
    el('hmAgainBtn').addEventListener('click', () => show('start'));

    // Scope line follows the ☰ menu selection.
    function refreshScope() {
        if (window.GameScope && typeof window.GameScope.summary === 'function') {
            el('hmScope').textContent = window.GameScope.summary();
        }
    }
    document.addEventListener('gamescope:changed', refreshScope);
    refreshScope();
})();
</script>
