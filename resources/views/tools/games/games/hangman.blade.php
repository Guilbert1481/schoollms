{{-- Hangman — playable Identification game with a drawn gallows + figure.
     Question supply: /tools/games/api/questions?type=identification (school
     question bank; answer = the identification keyword). Falls back to a small
     built-in practice deck when the bank has none, so the game is always
     playable. The clue is the question; guess the answer letter by letter.
     Each wrong guess draws the next body part; six wrong loses the round. --}}
<div data-game="hangman">
@verbatim
<style>
[data-game="hangman"]{
  --hm-ink:#16233b; --hm-muted:#5c6b85; --hm-faint:#93a0b5;
  --hm-line:rgba(20,40,80,.12); --hm-line2:rgba(20,40,80,.2);
  --hm-accent:#1f5fe0; --hm-accent2:#0ea5e9;
  --hm-good:#0e8a5f; --hm-goodbg:#e6f5ec; --hm-bad:#c02747; --hm-badbg:#fbe9ed;
  --hm-card:#ffffff; --hm-bg2:#eef3fb; --hm-key:#f4f7fc;
  --hm-stage-a:#111b30; --hm-stage-b:#1c2b49; --hm-wood:#d8b98c; --hm-chalk:#eef4ff;
  --hm-serif:"Iowan Old Style","Palatino Linotype",Palatino,Georgia,"Times New Roman",serif;
  --hm-mono:ui-monospace,"Cascadia Code","SFMono-Regular",Consolas,monospace;
  color:var(--hm-ink);
}
[data-game="hangman"] *{box-sizing:border-box}
[data-game="hangman"] .hm-hidden{display:none!important}
[data-game="hangman"] .hm-card{background:var(--hm-card);border:1px solid var(--hm-line);border-radius:18px;padding:22px;box-shadow:0 14px 40px -22px rgba(20,45,95,.4)}
[data-game="hangman"] .hm-title{margin:0;font-family:var(--hm-serif);font-size:20px;font-weight:600}
[data-game="hangman"] .hm-lead{margin:6px 0 0;color:var(--hm-muted);font-size:13.5px;line-height:1.55;max-width:60ch}
[data-game="hangman"] .hm-scope{margin-top:16px;border:1px solid var(--hm-line);background:var(--hm-bg2);border-radius:12px;padding:10px 14px}
[data-game="hangman"] .hm-scope .k{font-size:10px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:var(--hm-faint)}
[data-game="hangman"] .hm-scope .v{font-size:14px;font-weight:600;color:var(--hm-ink);margin-top:2px}
[data-game="hangman"] .hm-scope .h{font-size:11px;color:var(--hm-faint);margin-top:2px}
[data-game="hangman"] .hm-controls{margin-top:16px;display:flex;align-items:flex-end;gap:12px;flex-wrap:wrap}
[data-game="hangman"] .hm-field label{display:block;font-size:10.5px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:var(--hm-muted);margin-bottom:5px}
[data-game="hangman"] .hm-field select{border:1px solid var(--hm-line2);background:var(--hm-card);color:var(--hm-ink);font:inherit;font-size:14px;font-weight:600;border-radius:10px;padding:9px 12px;cursor:pointer}
[data-game="hangman"] .hm-btn{border:0;font:inherit;font-size:15px;font-weight:600;color:#fff;padding:12px 26px;border-radius:12px;cursor:pointer;background:linear-gradient(135deg,var(--hm-accent),var(--hm-accent2));box-shadow:0 12px 26px -12px rgba(15,55,150,.9);transition:transform .12s}
[data-game="hangman"] .hm-btn:hover{transform:translateY(-1px)}
[data-game="hangman"] .hm-btn.ghost{background:transparent;color:var(--hm-muted);border:1px solid var(--hm-line2);box-shadow:none}
[data-game="hangman"] .hm-btn.ghost:hover{border-color:var(--hm-accent);color:var(--hm-accent)}
[data-game="hangman"] .hm-note{margin-top:12px;border-radius:10px;padding:9px 12px;font-size:12.5px}
[data-game="hangman"] .hm-note.warn{border:1px solid #f6d98a;background:#fdf6e3;color:#8a6d1b}
[data-game="hangman"] .hm-note.err{border:1px solid #f3b4b4;background:var(--hm-badbg);color:var(--hm-bad)}

[data-game="hangman"] .hm-bar{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:14px}
[data-game="hangman"] .hm-round{font-size:11px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:var(--hm-faint)}
[data-game="hangman"] .hm-chips{display:flex;gap:8px;align-items:center}
[data-game="hangman"] .hm-chip{display:inline-flex;align-items:center;gap:6px;font-size:12.5px;font-weight:600;background:var(--hm-bg2);border:1px solid var(--hm-line);border-radius:20px;padding:5px 12px;font-variant-numeric:tabular-nums}
[data-game="hangman"] .hm-chip.sc{color:var(--hm-accent)}
[data-game="hangman"] .hm-hearts{letter-spacing:2px;font-size:14px;line-height:1}

[data-game="hangman"] .hm-stagewrap{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:18px;align-items:stretch}
[data-game="hangman"] .hm-stage{position:relative;border-radius:16px;padding:16px;display:grid;place-items:center;min-height:290px;
  background:radial-gradient(120% 100% at 50% 0%,var(--hm-stage-b),var(--hm-stage-a));border:1px solid rgba(255,255,255,.06);
  box-shadow:inset 0 1px 0 rgba(255,255,255,.06),0 18px 40px -26px rgba(10,20,45,.8)}
[data-game="hangman"] .hm-stage svg{width:100%;max-width:230px;height:auto;color:var(--hm-chalk)}
[data-game="hangman"] .hm-gallows{fill:none;stroke:var(--hm-wood);stroke-width:6;stroke-linecap:round;stroke-linejoin:round}
[data-game="hangman"] .hm-rope{fill:none;stroke:#b9c2d6;stroke-width:3.5;stroke-linecap:round}
[data-game="hangman"] .hm-part{fill:none;stroke:currentColor;stroke-width:5.5;stroke-linecap:round;stroke-linejoin:round;
  stroke-dasharray:150;stroke-dashoffset:150;opacity:0;transition:stroke-dashoffset .55s ease,opacity .25s ease}
[data-game="hangman"] .hm-part.on{stroke-dashoffset:0;opacity:1}
[data-game="hangman"] .hm-part.fatal{stroke:#ff8ba0}
[data-game="hangman"] .hm-stage .tag{position:absolute;top:12px;left:14px;font-size:10.5px;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:rgba(238,244,255,.55)}
[data-game="hangman"] .hm-misses{position:absolute;bottom:12px;left:0;right:0;text-align:center;font-family:var(--hm-mono);font-size:12px;color:rgba(238,244,255,.6);min-height:16px;letter-spacing:1px}

[data-game="hangman"] .hm-right{display:flex;flex-direction:column;gap:14px}
[data-game="hangman"] .hm-clue{border:1px solid var(--hm-line);background:var(--hm-bg2);border-radius:14px;padding:14px 16px}
[data-game="hangman"] .hm-clue .k{font-size:10px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:var(--hm-faint)}
[data-game="hangman"] .hm-clue .v{margin-top:4px;font-size:16px;font-weight:600;color:var(--hm-ink);line-height:1.4}
[data-game="hangman"] .hm-word{display:flex;flex-wrap:wrap;justify-content:center;gap:8px 6px;padding:6px 0 2px}
[data-game="hangman"] .hm-slot{width:26px;height:38px;display:flex;align-items:flex-end;justify-content:center;
  font-family:var(--hm-serif);font-size:24px;font-weight:600;color:var(--hm-ink)}
[data-game="hangman"] .hm-slot.blank{border-bottom:3px solid var(--hm-line2)}
[data-game="hangman"] .hm-slot.fill{border-bottom:3px solid var(--hm-accent);animation:hm-pop .3s ease}
[data-game="hangman"] .hm-slot.gap{width:12px}
@keyframes hm-pop{from{transform:translateY(4px);opacity:.2}to{transform:none;opacity:1}}
[data-game="hangman"] .hm-msg{border-radius:12px;padding:11px 14px;font-size:13.5px;font-weight:500;text-align:center}
[data-game="hangman"] .hm-msg.good{border:1px solid #a9dcc1;background:var(--hm-goodbg);color:var(--hm-good)}
[data-game="hangman"] .hm-msg.bad{border:1px solid #f0b8b8;background:var(--hm-badbg);color:var(--hm-bad)}
[data-game="hangman"] .hm-keys{display:grid;grid-template-columns:repeat(9,1fr);gap:6px}
[data-game="hangman"] .hm-key{border:1px solid var(--hm-line2);background:var(--hm-key);color:var(--hm-ink);font:inherit;font-size:14px;font-weight:700;
  padding:9px 0;border-radius:9px;cursor:pointer;transition:transform .08s,background .15s,border-color .15s}
[data-game="hangman"] .hm-key:hover:not(:disabled){transform:translateY(-1px);border-color:var(--hm-accent);color:var(--hm-accent)}
[data-game="hangman"] .hm-key:disabled{cursor:default}
[data-game="hangman"] .hm-key.hit{background:var(--hm-goodbg);border-color:#a9dcc1;color:var(--hm-good)}
[data-game="hangman"] .hm-key.miss{background:var(--hm-badbg);border-color:#f0b8b8;color:var(--hm-bad);opacity:.65}
[data-game="hangman"] .hm-next{text-align:center;margin-top:4px}

[data-game="hangman"] .hm-end{text-align:center;padding:14px 8px}
[data-game="hangman"] .hm-end .ico{font-size:44px}
[data-game="hangman"] .hm-end .big{font-family:var(--hm-serif);font-size:24px;font-weight:600;margin:8px 0 0}
[data-game="hangman"] .hm-end .score{font-family:var(--hm-mono);font-size:30px;font-weight:600;color:var(--hm-accent);margin:8px 0 0}
[data-game="hangman"] .hm-end .sub{color:var(--hm-muted);font-size:13.5px;margin:6px 0 0}
@media (prefers-reduced-motion:reduce){[data-game="hangman"] *{animation:none!important;transition:none!important}}
</style>
@endverbatim

  {{-- Config screen — shared component (Constitution §11B). Hangman is always
       Identification and single-player, so only the item count applies. --}}
  <div id="hmStart">
    <x-gamified-configuration
        title="Hangman"
        subtitle="Read the clue and guess the answer one letter at a time — six wrong and the round is lost."
        icon="🎯"
        :items="[5, 10, 15]"
        :items-default="10"
        start-label="Start game" />
  </div>

  <div id="hmGame" class="hm-card hm-hidden">
    <div class="hm-bar">
      <div class="hm-round" id="hmProgress"></div>
      <div class="hm-chips">
        <span class="hm-chip sc" id="hmScore"></span>
        <span class="hm-chip"><span class="hm-hearts" id="hmLives"></span></span>
      </div>
    </div>

    <div id="hmDeckNote" class="hm-note warn hm-hidden">Using built-in practice words — ask a teacher to add Identification questions for your subject to play your own.</div>

    <div class="hm-stagewrap">
      <div class="hm-stage">
        <span class="tag">The gallows</span>
        <svg viewBox="0 0 200 240" role="img" aria-label="Hangman figure">
          <path class="hm-gallows" d="M18 226 H118"/>
          <path class="hm-gallows" d="M40 226 V18"/>
          <path class="hm-gallows" d="M40 18 H140"/>
          <path class="hm-rope" d="M140 18 V40"/>
          <circle class="hm-part" id="hmP0" cx="140" cy="58" r="18"/>
          <path class="hm-part" id="hmP1" d="M140 76 V138"/>
          <path class="hm-part" id="hmP2" d="M140 92 L112 116"/>
          <path class="hm-part" id="hmP3" d="M140 92 L168 116"/>
          <path class="hm-part" id="hmP4" d="M140 138 L116 178"/>
          <path class="hm-part" id="hmP5" d="M140 138 L164 178"/>
        </svg>
        <div class="hm-misses" id="hmMisses"></div>
      </div>

      <div class="hm-right">
        <div class="hm-clue">
          <div class="k">Clue</div>
          <div class="v" id="hmClue"></div>
        </div>
        <div id="hmWord" class="hm-word"></div>
        <div id="hmRoundMsg" class="hm-msg hm-hidden"></div>
        <div id="hmKeyboard" class="hm-keys"></div>
        <div class="hm-next">
          <button type="button" id="hmNextBtn" class="hm-btn hm-hidden">Next round →</button>
        </div>
      </div>
    </div>
  </div>

  <div id="hmEnd" class="hm-card hm-hidden">
    <div class="hm-end">
      <div class="ico" id="hmEndIcon"></div>
      <div class="big">Game finished</div>
      <div class="score" id="hmEndScore"></div>
      <p class="sub" id="hmEndDetail"></p>
      <div style="margin-top:16px"><button type="button" id="hmAgainBtn" class="hm-btn">Play again</button></div>
    </div>
  </div>
</div>

<script>
(function () {
    const ENDPOINT = @json(route('tools.games.questions'));
    const MAX_WRONG = 6;
    const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('');

    const FALLBACK = [
        { question: 'The planet we live on', answer: 'EARTH' },
        { question: 'It lights up the sky in the daytime', answer: 'SUN' },
        { question: "Earth's natural satellite", answer: 'MOON' },
        { question: 'The largest land animal', answer: 'ELEPHANT' },
        { question: 'The king of the jungle', answer: 'LION' },
        { question: 'A place where you go to learn', answer: 'SCHOOL' },
        { question: 'Frozen water', answer: 'ICE' },
        { question: 'You read these to learn new things', answer: 'BOOK' },
        { question: 'The colour of a clear daytime sky', answer: 'BLUE' },
        { question: 'The opposite of night', answer: 'DAY' },
        { question: 'A large body of salt water', answer: 'OCEAN' },
        { question: 'It falls from clouds as water', answer: 'RAIN' },
        { question: 'The star nearest to Earth is our…', answer: 'SUN' },
        { question: 'An animal that says "moo"', answer: 'COW' },
        { question: 'The season after winter', answer: 'SPRING' }
    ];

    const el = (id) => document.getElementById(id);

    let questions = [];
    let round = 0, score = 0;
    let answer = '', guessed = new Set(), wrong = 0, roundOver = false, gameActive = false;

    function show(screen) {
        el('hmStart').classList.toggle('hm-hidden', screen !== 'start');
        el('hmGame').classList.toggle('hm-hidden', screen !== 'game');
        el('hmEnd').classList.toggle('hm-hidden', screen !== 'end');
        gameActive = screen === 'game';
    }

    async function start(cfg) {
        window.GamifiedConfig.clearError();
        window.GamifiedConfig.loading(true, 'Loading…');
        const limit = cfg.items;
        let usedFallback = false;
        try {
            const params = new URLSearchParams({ type: 'identification', limit });
            const scope = window.GameScope || {};
            ['subject_id', 'topic_id', 'lesson_id', 'competency_id', 'academic_level_id'].forEach(k => {
                if (scope[k]) params.set(k, scope[k]);
            });
            const res = await fetch(ENDPOINT + '?' + params.toString(), { headers: { 'Accept': 'application/json' } });
            if (!res.ok) throw new Error('Request failed (' + res.status + ')');
            const data = await res.json();
            questions = (data.questions || []).filter(q => /[a-z]/i.test(q.answer));
            if (questions.length === 0) { questions = pickFallback(limit); usedFallback = true; }
        } catch (e) {
            questions = pickFallback(limit);
            usedFallback = true;
        } finally {
            window.GamifiedConfig.loading(false);
        }

        el('hmDeckNote').classList.toggle('hm-hidden', !usedFallback);
        round = 0; score = 0;
        show('game');
        startRound();
    }

    function pickFallback(limit) {
        const pool = FALLBACK.slice();
        for (let i = pool.length - 1; i > 0; i--) { const j = Math.floor(Math.random() * (i + 1)); [pool[i], pool[j]] = [pool[j], pool[i]]; }
        return pool.slice(0, Math.min(parseInt(limit, 10) || 10, pool.length));
    }

    function startRound() {
        const q = questions[round];
        answer = q.answer.toUpperCase();
        guessed = new Set();
        wrong = 0;
        roundOver = false;

        el('hmProgress').textContent = 'Round ' + (round + 1) + ' of ' + questions.length;
        el('hmClue').textContent = q.question;
        el('hmRoundMsg').classList.add('hm-hidden');
        el('hmNextBtn').classList.add('hm-hidden');
        renderWord();
        renderFigure();
        renderLives();
        renderScore();
        renderKeyboard();
    }

    function renderWord() {
        el('hmWord').innerHTML = answer.split('').map(ch => {
            if (ch === ' ') return '<span class="hm-slot gap"></span>';
            if (!/[A-Z0-9]/.test(ch)) return '<span class="hm-slot">' + escapeHtml(ch) + '</span>';
            const revealed = guessed.has(ch) || roundOver;
            return '<span class="hm-slot ' + (revealed ? 'fill' : 'blank') + '">' + (revealed ? ch : '') + '</span>';
        }).join('');
    }

    function renderFigure() {
        for (let i = 0; i < MAX_WRONG; i++) {
            const part = el('hmP' + i);
            if (!part) continue;
            part.classList.toggle('on', i < wrong);
            part.classList.toggle('fatal', roundOver && wrong >= MAX_WRONG);
        }
        const misses = [...guessed].filter(l => !answer.includes(l)).sort();
        el('hmMisses').textContent = misses.length ? 'Missed: ' + misses.join(' ') : '';
    }

    function renderLives() {
        const left = MAX_WRONG - wrong;
        el('hmLives').textContent = '❤️'.repeat(Math.max(0, left)) + '♡'.repeat(Math.max(0, wrong));
    }

    function renderScore() { el('hmScore').textContent = 'Score ' + score + ' / ' + questions.length; }

    function renderKeyboard() {
        el('hmKeyboard').innerHTML = ALPHABET.map(l => {
            const used = guessed.has(l), inWord = answer.includes(l);
            let cls = 'hm-key';
            if (used) cls += inWord ? ' hit' : ' miss';
            return '<button type="button" data-letter="' + l + '" ' + ((used || roundOver) ? 'disabled' : '') + ' class="' + cls + '">' + l + '</button>';
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
            const allRevealed = answer.split('').every(ch => !/[A-Z0-9]/.test(ch) || guessed.has(ch));
            if (allRevealed) return endRound(true);
        }
        renderWord();
        renderFigure();
        renderLives();
        renderKeyboard();
    }

    function endRound(won) {
        roundOver = true;
        if (won) score++;
        const q = questions[round];
        const msg = el('hmRoundMsg');
        msg.className = 'hm-msg ' + (won ? 'good' : 'bad');
        msg.textContent = (won ? '✓ Correct! ' : 'Out of guesses — the answer was "' + q.answer + '". ') + (q.explanation ? q.explanation : '');
        msg.classList.remove('hm-hidden');
        renderWord();
        renderFigure();
        renderLives();
        renderScore();
        renderKeyboard();
        el('hmNextBtn').textContent = round + 1 >= questions.length ? 'See results →' : 'Next round →';
        el('hmNextBtn').classList.remove('hm-hidden');
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
            ? 'Outstanding — you really know this!'
            : (pct >= 50 ? "Good effort — a little more review and you'll ace it." : 'Keep practising and try again!');
        show('end');
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    document.addEventListener('keydown', (e) => {
        if (!gameActive || roundOver) return;
        const l = e.key.toUpperCase();
        if (ALPHABET.includes(l)) guess(l);
    });

    // Start is driven by the shared gamified-configuration component.
    document.addEventListener('gamified-config:start', (e) => start(e.detail));
    el('hmNextBtn').addEventListener('click', next);
    el('hmAgainBtn').addEventListener('click', () => show('start'));
})();
</script>
