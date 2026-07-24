{{-- Who Wants to Be a Millionaire? — playable MCQ game.
     Question supply: /tools/games/api/questions?type=mcq&difficulty=… (school-scoped
     question bank; see GamesController::questions). Grades client-side —
     practice play, not an exam.

     Presentation is a build-independent scoped theme: all styling lives in the
     scoped style block below, under [data-game="millionaire"] with ml- prefixed
     classes, so it never depends on the shared Tailwind build. --}}
<div data-game="millionaire" class="ml-root">

@verbatim
<style>
    [data-game="millionaire"]{
        --ml-hex: polygon(0 50%, 18px 0, calc(100% - 18px) 0, 100% 50%, calc(100% - 18px) 100%, 18px 100%);
        --ml-gold:#f0c04a; --ml-gold-bright:#ffd86b; --ml-gold-deep:#b9822a;
    }
    [data-game="millionaire"] .ml-root{ display:block; }
    [data-game="millionaire"] .ml-hidden{ display:none !important; }

    /* ---- stage: the deep-blue spotlight set ---- */
    [data-game="millionaire"] .ml-stage{
        position:relative; overflow:hidden; border-radius:18px; padding:26px 28px 30px;
        background:
            radial-gradient(ellipse 92% 62% at 50% 20%, rgba(60,130,220,.5) 0%, rgba(12,50,112,.32) 42%, rgba(5,20,54,0) 72%),
            radial-gradient(ellipse 130% 110% at 50% 122%, #0c336f 0%, #05173e 55%, #020a24 100%);
        border:1px solid rgba(120,170,255,.2);
        box-shadow: inset 0 0 120px rgba(0,18,54,.6), 0 14px 44px rgba(0,0,0,.4);
    }
    [data-game="millionaire"] .ml-beams{ position:absolute; inset:0; pointer-events:none; overflow:hidden; opacity:.5; }
    [data-game="millionaire"] .ml-beams::before,
    [data-game="millionaire"] .ml-beams::after{
        content:""; position:absolute; top:-12%; left:50%; width:55%; height:120%;
        background:linear-gradient(180deg, rgba(120,180,255,.22), transparent 62%); transform-origin:top center;
    }
    [data-game="millionaire"] .ml-beams::before{ transform:translateX(-50%) rotate(19deg); }
    [data-game="millionaire"] .ml-beams::after{ transform:translateX(-50%) rotate(-19deg); }

    /* ---- shared gold-framed hexagon lozenge ---- */
    [data-game="millionaire"] .ml-hex{ position:relative; background:linear-gradient(180deg, var(--ml-gold-bright), var(--ml-gold-deep)); clip-path:var(--ml-hex); }
    [data-game="millionaire"] .ml-hex::before{ content:""; position:absolute; inset:2px; background:linear-gradient(180deg,#0b1a40,#050d24); clip-path:var(--ml-hex); z-index:0; }
    [data-game="millionaire"] .ml-hex > *{ position:relative; z-index:1; }

    /* ================= START (splash) ================= */
    [data-game="millionaire"] .ml-start-inner,
    [data-game="millionaire"] .ml-end-inner{ position:relative; z-index:1; max-width:560px; margin:0 auto; text-align:center; }

    [data-game="millionaire"] .ml-emblem{
        position:relative; width:186px; height:186px; margin:6px auto 20px; border-radius:50%;
        background:radial-gradient(circle at 50% 42%, #3a2170 0%, #1a1046 55%, #0b0a26 100%);
        border:5px solid #7d1f31; display:flex; align-items:center; justify-content:center;
        box-shadow:0 0 46px rgba(120,70,210,.5), inset 0 0 34px rgba(0,0,0,.6);
        animation:ml-emblem-glow 3.4s ease-in-out infinite;
    }
    [data-game="millionaire"] .ml-emblem-ring{ position:absolute; inset:13px; border-radius:50%; border:2px solid rgba(120,190,255,.35); box-shadow:inset 0 0 0 6px rgba(60,120,210,.15), inset 0 0 26px rgba(80,150,255,.28); }
    [data-game="millionaire"] .ml-emblem-core{ position:relative; z-index:1; display:flex; flex-direction:column; align-items:center; gap:3px; }
    [data-game="millionaire"] .ml-emblem-sub{ color:#dfeaff; font-size:8px; font-weight:800; letter-spacing:.16em; text-transform:uppercase; }
    [data-game="millionaire"] .ml-emblem-title{ color:#ffd86b; font-size:23px; font-weight:900; letter-spacing:.03em; text-shadow:0 2px 6px rgba(0,0,0,.6), 0 0 18px rgba(255,190,70,.5); }
    @keyframes ml-emblem-glow{ 0%,100%{ box-shadow:0 0 40px rgba(120,70,210,.42), inset 0 0 34px rgba(0,0,0,.6);} 50%{ box-shadow:0 0 62px rgba(150,90,240,.62), inset 0 0 34px rgba(0,0,0,.6);} }

    [data-game="millionaire"] .ml-h3{ color:#fff; font-size:20px; font-weight:800; margin:0 0 4px; }
    [data-game="millionaire"] .ml-lead{ color:#c3d6f5; font-size:13.5px; line-height:1.55; margin:0 auto 16px; max-width:460px; }

    [data-game="millionaire"] .ml-scopebox{ background:rgba(6,18,46,.6); border:1px solid rgba(120,170,255,.22); border-radius:12px; padding:10px 14px; margin:0 auto 16px; max-width:420px; text-align:left; }
    [data-game="millionaire"] .ml-scopelabel{ color:var(--ml-gold); font-size:10px; font-weight:800; letter-spacing:.14em; text-transform:uppercase; }
    [data-game="millionaire"] .ml-scopeval{ color:#eef4ff; font-size:14px; font-weight:700; margin-top:2px; }
    [data-game="millionaire"] .ml-scopehint{ color:#8fa8cf; font-size:11px; margin-top:2px; }

    [data-game="millionaire"] .ml-controls{ display:grid; grid-template-columns:1fr 1fr; gap:12px; max-width:420px; margin:0 auto 16px; text-align:left; }
    [data-game="millionaire"] .ml-label{ display:block; color:var(--ml-gold); font-size:10px; font-weight:800; letter-spacing:.12em; text-transform:uppercase; margin-bottom:5px; }
    [data-game="millionaire"] .ml-selectwrap{ position:relative; }
    [data-game="millionaire"] .ml-selectwrap::after{ content:"\25BE"; position:absolute; right:12px; top:50%; transform:translateY(-50%); color:var(--ml-gold); pointer-events:none; font-size:12px; }
    [data-game="millionaire"] .ml-select{ width:100%; appearance:none; -webkit-appearance:none; background:rgba(6,18,46,.85); color:#eef4ff; border:1.5px solid rgba(240,192,74,.55); border-radius:10px; padding:9px 30px 9px 12px; font-size:13px; font-weight:600; cursor:pointer; }
    [data-game="millionaire"] .ml-select:focus{ outline:none; border-color:var(--ml-gold-bright); box-shadow:0 0 0 3px rgba(240,192,74,.2); }
    [data-game="millionaire"] .ml-select option{ background:#0a1a3e; color:#eef4ff; }

    [data-game="millionaire"] .ml-error{ background:rgba(240,160,32,.14); border:1px solid rgba(240,160,32,.5); color:#ffcf87; border-radius:10px; padding:10px 14px; font-size:13px; margin:0 auto 14px; max-width:420px; }

    [data-game="millionaire"] .ml-banner{ display:inline-flex; align-items:center; justify-content:center; padding:15px 54px; margin-top:6px; border:0; cursor:pointer; }
    [data-game="millionaire"] .ml-banner-txt{ color:var(--ml-gold-bright); font-weight:900; font-size:16px; letter-spacing:.1em; text-transform:uppercase; text-shadow:0 1px 4px rgba(0,0,0,.5); }
    [data-game="millionaire"] .ml-banner:hover{ filter:brightness(1.08); }
    [data-game="millionaire"] .ml-banner:hover .ml-banner-txt{ color:#fff; }
    [data-game="millionaire"] .ml-banner:disabled{ opacity:.72; cursor:default; }

    /* ================= GAME ================= */
    [data-game="millionaire"] .ml-gamegrid{ position:relative; z-index:1; display:grid; grid-template-columns:1fr 300px; gap:22px; }
    [data-game="millionaire"] .ml-main{ min-width:0; }

    [data-game="millionaire"] .ml-hud{ display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:8px; }
    [data-game="millionaire"] .ml-lifeline{
        display:inline-flex; align-items:center; justify-content:center; width:58px; height:58px; border-radius:50%;
        border:2px solid var(--ml-gold); background:radial-gradient(circle at 50% 34%, #123a86, #061634);
        color:var(--ml-gold-bright); font-weight:800; font-size:12.5px; cursor:pointer;
        box-shadow:0 0 14px rgba(58,123,213,.4), inset 0 2px 6px rgba(255,255,255,.12);
    }
    [data-game="millionaire"] .ml-lifeline:hover:not(:disabled){ filter:brightness(1.16); }
    [data-game="millionaire"] .ml-lifeline:disabled{ opacity:.34; cursor:default; filter:grayscale(.4); text-decoration:line-through; }
    [data-game="millionaire"] .ml-prizebadge{ padding:9px 24px; }
    [data-game="millionaire"] .ml-prizebadge-txt{ color:var(--ml-gold-bright); font-weight:800; font-size:15px; }

    [data-game="millionaire"] .ml-progress{ text-align:center; color:var(--ml-gold); font-weight:700; font-size:11.5px; letter-spacing:.16em; text-transform:uppercase; margin:4px 0 0; }

    [data-game="millionaire"] .ml-qrow{ display:flex; align-items:center; margin-top:14px; }
    [data-game="millionaire"] .ml-qline{ flex:1 1 auto; height:2px; background:linear-gradient(90deg, transparent, rgba(240,192,74,.6)); }
    [data-game="millionaire"] .ml-qrow .ml-qline + .ml-qbar + .ml-qline{ background:linear-gradient(90deg, rgba(240,192,74,.6), transparent); }
    [data-game="millionaire"] .ml-qbar{ flex:0 1 auto; min-width:62%; max-width:80%; padding:16px 30px; margin:0 -10px; }
    [data-game="millionaire"] .ml-qtext{ display:block; text-align:center; color:#fff; font-weight:700; font-size:17px; line-height:1.4; }

    [data-game="millionaire"] .ml-options{ display:grid; grid-template-columns:1fr 1fr; gap:12px 20px; margin-top:18px; }
    [data-game="millionaire"] .ml-opt{ display:flex; align-items:center; min-height:54px; width:100%; border:0; background:transparent; cursor:pointer; text-align:left; }
    [data-game="millionaire"] .ml-optletter{ flex:0 0 auto; padding:0 14px 0 24px; font-weight:800; font-size:15px; color:var(--ml-gold-bright); position:relative; }
    [data-game="millionaire"] .ml-optletter::after{ content:""; position:absolute; right:0; top:50%; transform:translateY(-50%); height:22px; width:2px; background:rgba(240,192,74,.45); }
    [data-game="millionaire"] .ml-opttext{ padding:0 18px 0 14px; color:#eaf2ff; font-weight:600; font-size:14px; }
    /* Hover only on real pointers (touch "hover" sticks after a tap), and only
       once the mouse has MOVED since the question rendered — otherwise the new
       question's button under the resting cursor lights up and reads like the
       previous selection persisting. */
    @media (hover:hover) and (pointer:fine){
        [data-game="millionaire"] .ml-options:not(.ml-nohover) .ml-opt:hover:not(:disabled)::before{ background:linear-gradient(180deg,#f3b23e,#d97a10); }
        [data-game="millionaire"] .ml-options:not(.ml-nohover) .ml-opt:hover:not(:disabled) .ml-opttext{ color:#1a1000; }
        [data-game="millionaire"] .ml-options:not(.ml-nohover) .ml-opt:hover:not(:disabled) .ml-optletter{ color:#3a1e00; }
    }
    [data-game="millionaire"] .ml-opt.is-correct{ filter:drop-shadow(0 0 10px rgba(34,197,94,.7)); }
    [data-game="millionaire"] .ml-opt.is-correct::before{ background:linear-gradient(180deg,#2fbf5a,#12833a); }
    [data-game="millionaire"] .ml-opt.is-correct .ml-opttext,
    [data-game="millionaire"] .ml-opt.is-correct .ml-optletter{ color:#04210f; }
    [data-game="millionaire"] .ml-opt.is-wrong{ filter:drop-shadow(0 0 10px rgba(239,68,68,.7)); }
    [data-game="millionaire"] .ml-opt.is-wrong::before{ background:linear-gradient(180deg,#f0564a,#b3231a); }
    [data-game="millionaire"] .ml-opt.is-wrong .ml-opttext,
    [data-game="millionaire"] .ml-opt.is-wrong .ml-optletter{ color:#2a0605; }
    [data-game="millionaire"] .ml-opt.is-dim{ opacity:.28; }
    [data-game="millionaire"] .ml-opt.is-dim .ml-opttext{ text-decoration:line-through; }
    [data-game="millionaire"] .ml-opt:disabled{ cursor:default; }

    [data-game="millionaire"] .ml-feedback{ margin-top:16px; border-radius:10px; padding:10px 16px; font-size:13.5px; font-weight:600; text-align:center; }
    [data-game="millionaire"] .ml-feedback.is-correct{ background:rgba(34,197,94,.14); border:1px solid rgba(34,197,94,.5); color:#7ff0a8; }
    [data-game="millionaire"] .ml-feedback.is-wrong{ background:rgba(239,68,68,.14); border:1px solid rgba(239,68,68,.5); color:#ff9d97; }

    /* ---- prize ladder ---- */
    [data-game="millionaire"] .ml-ladderwrap{ align-self:start; background:linear-gradient(180deg, rgba(9,26,64,.6), rgba(4,12,34,.6)); border:1px solid rgba(120,170,255,.18); border-radius:14px; padding:12px; }
    [data-game="millionaire"] .ml-ladder-title{ text-align:center; color:var(--ml-gold); font-weight:800; font-size:12px; letter-spacing:.14em; text-transform:uppercase; margin-bottom:8px; }
    [data-game="millionaire"] .ml-ladder{ list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:4px; }
    [data-game="millionaire"] .ml-rung{
        display:flex; align-items:center; justify-content:space-between; gap:10px; padding:6px 16px;
        font-weight:700; font-size:13px; color:#e7b955; background:linear-gradient(180deg,#123a86,#0a2360);
        clip-path:polygon(0 50%, 12px 0, calc(100% - 12px) 0, 100% 50%, calc(100% - 12px) 100%, 12px 100%);
    }
    [data-game="millionaire"] .ml-rung .ml-rung-q{ color:#a7c4ef; font-weight:800; }
    [data-game="millionaire"] .ml-rung.is-won{ background:linear-gradient(180deg,#0d2148,#07152f); color:#5f81b8; }
    [data-game="millionaire"] .ml-rung.is-won .ml-rung-q{ color:#4d6a9c; }
    [data-game="millionaire"] .ml-rung.is-current{ background:linear-gradient(180deg,#f3b23e,#d97a10); color:#1a0f00; filter:drop-shadow(0 0 9px rgba(240,160,32,.75)); }
    [data-game="millionaire"] .ml-rung.is-current .ml-rung-q{ color:#3a1e00; }
    [data-game="millionaire"] .ml-rung.is-top{ color:#ffe08a; }
    [data-game="millionaire"] .ml-rung.is-top.is-up{ background:linear-gradient(180deg,#1a4a9e,#0e2f70); }

    /* ================= END ================= */
    [data-game="millionaire"] .ml-end-icon{ font-size:52px; line-height:1; filter:drop-shadow(0 0 18px rgba(255,200,80,.5)); }
    [data-game="millionaire"] .ml-end-title{ color:#fff; font-size:22px; font-weight:900; margin:10px 0 4px; }
    [data-game="millionaire"] .ml-end-detail{ color:#c3d6f5; font-size:14px; margin:0 0 10px; }
    [data-game="millionaire"] .ml-end-prize{ color:var(--ml-gold-bright); font-size:30px; font-weight:900; text-shadow:0 0 22px rgba(255,190,70,.5); margin-bottom:8px; }

    @media (max-width:900px){ [data-game="millionaire"] .ml-gamegrid{ grid-template-columns:1fr; } }
    @media (max-width:640px){
        [data-game="millionaire"] .ml-options{ grid-template-columns:1fr; }
        [data-game="millionaire"] .ml-controls{ grid-template-columns:1fr; }
        [data-game="millionaire"] .ml-qbar{ min-width:0; max-width:100%; margin:0; }
    }
</style>
@endverbatim

    {{-- ============ START SCREEN (splash) ============ --}}
    <div id="mlStart" class="ml-stage">
        <div class="ml-beams" aria-hidden="true"></div>
        <div class="ml-start-inner">
            <div class="ml-emblem" aria-hidden="true">
                <div class="ml-emblem-ring"></div>
                <div class="ml-emblem-core">
                    <span class="ml-emblem-sub">Who Wants To Be A</span>
                    <span class="ml-emblem-title">MILLIONAIRE</span>
                    <span class="ml-emblem-sub">Who Wants To Be A</span>
                </div>
            </div>

            <h3 class="ml-h3">Start a game</h3>
            <p class="ml-lead">Multiple-choice questions from your school's question bank. Climb the prize ladder — one wrong answer ends the run.</p>

            <div class="ml-scopebox">
                <div class="ml-scopelabel">Practicing</div>
                <div id="mlScope" class="ml-scopeval">All subjects</div>
                <div class="ml-scopehint">Change content from the &#9776; menu (top right).</div>
            </div>

            <div class="ml-controls">
                <div>
                    <label class="ml-label" for="mlCount">Questions</label>
                    <div class="ml-selectwrap">
                        <select id="mlCount" class="ml-select">
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="15" selected>15</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="ml-label" for="mlDiff">Difficulty</label>
                    <div class="ml-selectwrap">
                        <select id="mlDiff" class="ml-select">
                            <option value="average">Average</option>
                            <option value="advanced">Advanced</option>
                            <option value="mixed" selected>Mixed (average &rarr; advanced)</option>
                        </select>
                    </div>
                </div>
            </div>

            <div id="mlStartError" class="ml-error ml-hidden"></div>

            <button type="button" id="mlStartBtn" class="ml-banner ml-hex">
                <span class="ml-banner-txt" id="mlStartLabel">Let's Play!</span>
            </button>
        </div>
    </div>

    {{-- ============ GAME SCREEN ============ --}}
    <div id="mlGame" class="ml-stage ml-hidden">
        <div class="ml-beams" aria-hidden="true"></div>
        <div class="ml-gamegrid">
            <div class="ml-main">
                <div class="ml-hud">
                    <button type="button" id="mlFifty" class="ml-lifeline" title="50:50 — remove two wrong answers">50:50</button>
                    <div class="ml-prizebadge ml-hex"><span id="mlPrizeNow" class="ml-prizebadge-txt">&#8369;100</span></div>
                </div>

                <div id="mlProgress" class="ml-progress"></div>

                <div class="ml-qrow">
                    <span class="ml-qline"></span>
                    <div class="ml-qbar ml-hex"><span id="mlQuestion" class="ml-qtext"></span></div>
                    <span class="ml-qline"></span>
                </div>

                <div id="mlOptions" class="ml-options"></div>
                <div id="mlFeedback" class="ml-feedback ml-hidden"></div>
            </div>

            <aside class="ml-ladderwrap">
                <div class="ml-ladder-title">Prize Ladder</div>
                <ol id="mlLadder" class="ml-ladder"></ol>
            </aside>
        </div>
    </div>

    {{-- ============ END SCREEN ============ --}}
    <div id="mlEnd" class="ml-stage ml-hidden">
        <div class="ml-beams" aria-hidden="true"></div>
        <div class="ml-end-inner">
            <div id="mlEndIcon" class="ml-end-icon"></div>
            <h3 id="mlEndTitle" class="ml-end-title"></h3>
            <p id="mlEndDetail" class="ml-end-detail"></p>
            <div id="mlEndPrize" class="ml-end-prize"></div>
            <button type="button" id="mlAgainBtn" class="ml-banner ml-hex">
                <span class="ml-banner-txt">Play Again</span>
            </button>
        </div>
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
        el('mlStart').classList.toggle('ml-hidden', screen !== 'start');
        el('mlGame').classList.toggle('ml-hidden', screen !== 'game');
        el('mlEnd').classList.toggle('ml-hidden', screen !== 'end');
    }

    async function start() {
        const btn = el('mlStartBtn');
        const label = el('mlStartLabel');
        const err = el('mlStartError');
        err.classList.add('ml-hidden');
        btn.disabled = true;
        label.textContent = 'Loading…';

        try {
            const params = new URLSearchParams({
                type: 'mcq',
                limit: el('mlCount').value,
                difficulty: el('mlDiff').value,
            });
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
                    ? 'No multiple-choice questions found for this selection. Try a different difficulty or ask a teacher to add MCQ items to the Question Bank.'
                    : 'Only ' + questions.length + ' question(s) found — at least 3 are needed. Try "All subjects", a different difficulty, or add more MCQ items.';
                err.classList.remove('ml-hidden');
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
            err.classList.remove('ml-hidden');
        } finally {
            btn.disabled = false;
            label.textContent = "Let's Play!";
        }
    }

    function renderLadder() {
        const top = prizes.length - 1;
        el('mlLadder').innerHTML = prizes.map((amt, i) => {
            let cls = 'ml-rung';
            if (i === current) cls += ' is-current';
            else if (i < current) cls += ' is-won';
            else cls += ' is-up';
            if (i === top) cls += ' is-top';
            return '<li class="' + cls + '"><span class="ml-rung-q">Q' + (i + 1) + '</span><span class="ml-rung-amt">' + peso(amt) + '</span></li>';
        }).reverse().join('');
    }

    function renderQuestion() {
        const q = questions[current];
        locked = false;
        el('mlProgress').textContent = 'Question ' + (current + 1) + ' of ' + questions.length;
        el('mlPrizeNow').textContent = peso(prizes[current]);
        el('mlQuestion').textContent = q.question;
        const fb = el('mlFeedback');
        fb.className = 'ml-feedback ml-hidden';
        fb.textContent = '';
        el('mlOptions').innerHTML = q.choices.map((c, i) =>
            '<button type="button" data-idx="' + i + '" class="ml-opt ml-hex">'
            + '<span class="ml-optletter">' + (LETTERS[i] || '?') + '</span>'
            + '<span class="ml-opttext">' + escapeHtml(c) + '</span>'
            + '</button>'
        ).join('');
        el('mlOptions').querySelectorAll('.ml-opt').forEach(btn => {
            btn.addEventListener('click', () => answer(parseInt(btn.dataset.idx, 10)));
        });
        armHoverGuard();
        renderLadder();
    }

    // Fresh question: hold hover styling until the pointer actually moves, so
    // the button that renders under the resting cursor doesn't look selected.
    function armHoverGuard() {
        const box = el('mlOptions');
        box.classList.add('ml-nohover');
        document.addEventListener('mousemove', function unlock() {
            box.classList.remove('ml-nohover');
            document.removeEventListener('mousemove', unlock);
        });
    }

    function answer(idx) {
        if (locked) return;
        locked = true;
        const q = questions[current];
        el('mlOptions').querySelectorAll('.ml-opt').forEach(b => {
            const i = parseInt(b.dataset.idx, 10);
            b.disabled = true;
            if (i === q.answer) b.classList.add('is-correct');
            else if (i === idx) b.classList.add('is-wrong');
        });

        const fb = el('mlFeedback');
        if (idx === q.answer) {
            fb.className = 'ml-feedback is-correct';
            fb.textContent = 'Correct! ' + (q.explanation ? q.explanation : '');
            setTimeout(() => {
                current++;
                if (current >= questions.length) end(true);
                else renderQuestion();
            }, 1200);
        } else {
            fb.className = 'ml-feedback is-wrong';
            fb.textContent = 'Wrong! ' + (q.explanation ? q.explanation : '');
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
            if (parseInt(b.dataset.idx, 10) !== q.answer) wrong.push(b);
        });
        wrong.sort(() => Math.random() - 0.5).slice(0, Math.min(2, wrong.length - 1)).forEach(b => {
            b.disabled = true;
            b.classList.add('is-dim');
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
