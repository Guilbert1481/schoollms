{{-- Crossword Challenge — subject-vocabulary crossword built from the bank.

     Question supply: /tools/games/api/questions?type=identification (school-
     scoped, GameScope ☰ filters) — single-word answers become the grid, their
     questions become the clues. Client-graded practice, like every catalog
     game; the puzzle is generated in-browser per run.

     Pre-game setup is the shared <x-gamified-configuration> component
     (Constitution §11B): items / difficulty / mode live there, the game only
     listens for 'gamified-config:start'.

     Layout mirrors the approved key art: navy top bar (title • challenge name
     • timer • round • live badge), team scorecards flanking the solve
     progress, grid + clues panel (current-clue letter boxes, SUBMIT / HINT /
     SKIP, full clue list), and a bottom bar with team chips, a solve feed,
     and pause / sound controls. Styling is scoped cw-* (stale-build rule). --}}

<div data-game="crossword" class="cw-root">

@verbatim
<style>
    [data-game="crossword"]{
        --cw-blue:#1f5fd0; --cw-blue-deep:#0f3aa0; --cw-blue-soft:#e8f0ff;
        --cw-orange:#e56a1c; --cw-orange-deep:#c04f0d; --cw-orange-soft:#fff0e6;
        --cw-navy:#0f2545; --cw-ink:#1f2a3a; --cw-line:#dfe6f0; --cw-soft:#f4f7fc;
        --cw-green:#16a34a; --cw-gold:#f5b301;
    }
    [data-game="crossword"] *{ box-sizing:border-box; }
    [data-game="crossword"] .cw-hidden{ display:none !important; }
    [data-game="crossword"].cw-embedded .cw-stage{ position:relative; left:50%; transform:translateX(-50%); width:min(1400px,96vw); }

    [data-game="crossword"] .cw-stage{ background:#eef2f8; border:1px solid var(--cw-line); border-radius:16px; overflow:hidden; box-shadow:0 10px 30px rgba(15,37,69,.08); }

    /* ============ top bar ============ */
    [data-game="crossword"] .cw-top{ display:flex; align-items:center; justify-content:space-between; gap:14px; background:var(--cw-navy); color:#fff; padding:13px 18px; flex-wrap:wrap; }
    [data-game="crossword"] .cw-top-title{ font-size:17px; font-weight:900; letter-spacing:.05em; text-transform:uppercase; }
    [data-game="crossword"] .cw-top-mid{ font-size:15px; font-weight:800; flex:1; text-align:center; min-width:180px; }
    [data-game="crossword"] .cw-top-right{ display:flex; align-items:center; gap:12px; font-weight:800; font-size:14px; }
    [data-game="crossword"] .cw-timer{ display:inline-flex; align-items:center; gap:6px; font-variant-numeric:tabular-nums; }
    [data-game="crossword"] .cw-round{ border-left:1px solid rgba(255,255,255,.25); padding-left:12px; font-size:13px; }
    [data-game="crossword"] .cw-live{ display:inline-flex; align-items:center; gap:7px; background:#123d2a; border:1px solid #2e7d54; border-radius:999px; padding:6px 13px; font-size:12px; font-weight:800; }
    [data-game="crossword"] .cw-live::before{ content:''; width:8px; height:8px; border-radius:50%; background:#34d17b; box-shadow:0 0 6px #34d17b; }

    /* ============ score row ============ */
    [data-game="crossword"] .cw-scorerow{ display:grid; grid-template-columns:minmax(230px,1fr) minmax(230px,1.2fr) minmax(230px,1fr); gap:12px; align-items:stretch; padding:12px 14px 4px; }
    [data-game="crossword"] .cw-teamcard{ display:flex; align-items:center; gap:12px; border-radius:14px; padding:10px 14px; background:#fff; border:2px solid var(--cw-blue); box-shadow:0 6px 16px rgba(15,37,69,.08); }
    [data-game="crossword"] .cw-teamcard.b{ border-color:var(--cw-orange); flex-direction:row-reverse; text-align:right; }
    [data-game="crossword"] .cw-teamcard.turn{ box-shadow:0 0 0 3px rgba(31,95,208,.25), 0 6px 16px rgba(15,37,69,.08); }
    [data-game="crossword"] .cw-teamcard.b.turn{ box-shadow:0 0 0 3px rgba(229,106,28,.3), 0 6px 16px rgba(15,37,69,.08); }
    [data-game="crossword"] .cw-badge{ width:56px; height:56px; flex:none; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:28px; color:#fff; background:linear-gradient(150deg,var(--cw-blue),var(--cw-blue-deep)); }
    [data-game="crossword"] .cw-teamcard.b .cw-badge{ background:linear-gradient(150deg,var(--cw-orange),var(--cw-orange-deep)); }
    [data-game="crossword"] .cw-tc-label{ font-size:13px; font-weight:900; letter-spacing:.05em; text-transform:uppercase; color:var(--cw-blue-deep); }
    [data-game="crossword"] .cw-teamcard.b .cw-tc-label{ color:var(--cw-orange-deep); }
    [data-game="crossword"] .cw-tc-pts{ font-size:26px; font-weight:900; color:var(--cw-ink); line-height:1.05; }
    [data-game="crossword"] .cw-tc-pts small{ font-size:14px; font-weight:800; }
    [data-game="crossword"] .cw-tc-solved{ font-size:11px; font-weight:800; color:#c8860a; margin-top:2px; }
    [data-game="crossword"] .cw-chips{ display:flex; }
    [data-game="crossword"] .cw-chip{ width:34px; height:34px; border-radius:50%; overflow:hidden; border:2.5px solid var(--cw-blue); background:#dbeafe; margin-left:-8px; }
    [data-game="crossword"] .cw-chip:first-child{ margin-left:0; }
    [data-game="crossword"] .cw-teamcard.b .cw-chip{ border-color:var(--cw-orange); }
    [data-game="crossword"] .cw-chip img{ width:100%; height:100%; object-fit:cover; display:block; }
    [data-game="crossword"] .cw-chip.cpu{ display:flex; align-items:center; justify-content:center; font-size:17px; background:#ffe9d4; }

    [data-game="crossword"] .cw-mid{ display:flex; flex-direction:column; align-items:center; justify-content:center; gap:6px; }
    [data-game="crossword"] .cw-mid-q{ font-size:14px; font-weight:900; letter-spacing:.06em; color:var(--cw-ink); text-transform:uppercase; }
    [data-game="crossword"] .cw-mid-s{ font-size:12px; font-weight:800; color:#586a82; }
    [data-game="crossword"] .cw-seg{ display:flex; gap:4px; flex-wrap:wrap; justify-content:center; }
    [data-game="crossword"] .cw-seg i{ width:16px; height:20px; border-radius:4px; background:#fff; border:1.5px solid var(--cw-line); }
    [data-game="crossword"] .cw-seg i.a{ background:var(--cw-blue); border-color:var(--cw-blue-deep); }
    [data-game="crossword"] .cw-seg i.b{ background:var(--cw-orange); border-color:var(--cw-orange-deep); }
    [data-game="crossword"] .cw-seg i.cur{ outline:2px solid var(--cw-gold); outline-offset:1px; }

    /* ============ main ============ */
    [data-game="crossword"] .cw-main{ display:grid; grid-template-columns:minmax(0,1.6fr) minmax(300px,1fr); gap:12px; padding:8px 14px 12px; align-items:start; }
    [data-game="crossword"] .cw-gridwrap{ background:#fff; border:1.5px solid #bcd0ea; border-radius:14px; padding:14px; overflow:auto; }
    [data-game="crossword"] .cw-grid{ display:grid; width:max-content; margin:0 auto; gap:0; border:2px solid var(--cw-navy); }
    [data-game="crossword"] .cw-cell{ position:relative; width:var(--cell,34px); height:var(--cell,34px); display:flex; align-items:center; justify-content:center; background:#fff; border:1px solid #c8d6ea; font-weight:900; font-size:calc(var(--cell,34px)*.5); color:var(--cw-ink); text-transform:uppercase; cursor:pointer; }
    [data-game="crossword"] .cw-cell.blk{ background:var(--cw-navy); border-color:var(--cw-navy); cursor:default; }
    [data-game="crossword"] .cw-cell .n{ position:absolute; top:1px; left:2.5px; font-size:calc(var(--cell,34px)*.26); font-weight:800; color:#587099; }
    [data-game="crossword"] .cw-cell.act{ background:var(--cw-blue-soft); border-color:var(--cw-blue); }
    [data-game="crossword"] .cw-cell.ok{ color:var(--cw-ink); }
    [data-game="crossword"] .cw-cell.ok.byB{ color:var(--cw-orange-deep); }

    /* ============ clues panel ============ */
    [data-game="crossword"] .cw-clues{ background:#fff; border:1.5px solid #bcd0ea; border-radius:14px; padding:14px; display:flex; flex-direction:column; gap:10px; max-height:640px; }
    [data-game="crossword"] .cw-clues-head{ display:flex; align-items:center; justify-content:space-between; }
    [data-game="crossword"] .cw-clues-title{ font-size:19px; font-weight:900; letter-spacing:.03em; color:var(--cw-ink); }
    [data-game="crossword"] .cw-pager{ display:flex; align-items:center; gap:8px; font-weight:900; color:var(--cw-ink); font-size:14px; }
    [data-game="crossword"] .cw-pgbtn{ width:32px; height:32px; border-radius:9px; border:1.5px solid var(--cw-line); background:#fff; cursor:pointer; font-size:15px; font-weight:900; color:var(--cw-blue-deep); }
    [data-game="crossword"] .cw-pgbtn:hover{ background:var(--cw-blue-soft); }
    [data-game="crossword"] .cw-tabs{ display:flex; border-bottom:2px solid var(--cw-line); }
    [data-game="crossword"] .cw-tab{ flex:1; text-align:center; padding:7px 4px; font-size:13px; font-weight:900; color:#7a8aa0; cursor:pointer; border-bottom:3px solid transparent; margin-bottom:-2px; background:none; border-top:0; border-left:0; border-right:0; }
    [data-game="crossword"] .cw-tab.on{ color:var(--cw-blue-deep); border-bottom-color:var(--cw-blue); }

    [data-game="crossword"] .cw-current{ background:var(--cw-soft); border:1.5px solid #d7e3f6; border-radius:12px; padding:12px; }
    [data-game="crossword"] .cw-cur-num{ font-size:15px; font-weight:900; color:var(--cw-blue-deep); }
    [data-game="crossword"] .cw-cur-clue{ font-size:14.5px; font-weight:700; color:var(--cw-ink); margin:4px 0 2px; }
    [data-game="crossword"] .cw-cur-len{ font-size:12px; color:#7a8aa0; font-weight:700; margin-bottom:8px; }
    [data-game="crossword"] .cw-boxes{ display:flex; gap:6px; flex-wrap:wrap; margin-bottom:10px; }
    [data-game="crossword"] .cw-box{ width:40px; height:44px; border-radius:9px; border:2px solid var(--cw-line); background:#fff; display:flex; align-items:center; justify-content:center; font-size:20px; font-weight:900; color:var(--cw-ink); text-transform:uppercase; }
    [data-game="crossword"] .cw-box.lock{ background:#eef3fb; color:#587099; }
    [data-game="crossword"] .cw-box.cur{ border-color:var(--cw-blue); box-shadow:0 0 0 2.5px rgba(31,95,208,.2); }
    [data-game="crossword"] .cw-box.bad{ border-color:#dc2626; animation:cwShake .35s ease; }
    @keyframes cwShake{ 25%{ transform:translateX(-3px) } 75%{ transform:translateX(3px) } }
    [data-game="crossword"] .cw-submit{ width:100%; border:0; cursor:pointer; border-radius:11px; padding:12px; font-size:14px; font-weight:900; letter-spacing:.06em; text-transform:uppercase; color:#fff; background:linear-gradient(120deg,var(--cw-blue),var(--cw-blue-deep)); box-shadow:0 6px 16px rgba(31,95,208,.3); }
    [data-game="crossword"] .cw-submit:hover{ filter:brightness(1.06); }
    [data-game="crossword"] .cw-minor{ display:flex; gap:8px; margin-top:8px; }
    [data-game="crossword"] .cw-minorbtn{ flex:1; border:1.5px solid var(--cw-line); background:#fff; color:#586a82; border-radius:10px; padding:8px; font-size:12.5px; font-weight:900; cursor:pointer; }
    [data-game="crossword"] .cw-minorbtn:hover{ background:var(--cw-soft); }

    [data-game="crossword"] .cw-list{ overflow-y:auto; flex:1; min-height:90px; display:flex; flex-direction:column; gap:3px; }
    [data-game="crossword"] .cw-li{ display:flex; gap:8px; align-items:baseline; padding:6px 9px; border-radius:9px; font-size:13px; cursor:pointer; border:1.5px solid transparent; }
    [data-game="crossword"] .cw-li:hover{ background:var(--cw-soft); }
    [data-game="crossword"] .cw-li.on{ background:var(--cw-blue-soft); border-color:var(--cw-blue); }
    [data-game="crossword"] .cw-li.done{ opacity:.55; text-decoration:line-through; }
    [data-game="crossword"] .cw-li .k{ font-weight:900; color:var(--cw-blue-deep); white-space:nowrap; }
    [data-game="crossword"] .cw-li .t{ color:var(--cw-ink); font-weight:600; }
    [data-game="crossword"] .cw-li .who{ margin-left:auto; font-size:11px; font-weight:900; }
    [data-game="crossword"] .cw-li .who.a{ color:var(--cw-blue-deep); }
    [data-game="crossword"] .cw-li .who.b{ color:var(--cw-orange-deep); }
    [data-game="crossword"] .cw-list-foot{ text-align:center; font-size:11.5px; font-weight:700; color:#7a8aa0; border-top:1px solid var(--cw-line); padding-top:8px; }

    /* ============ bottom bar ============ */
    [data-game="crossword"] .cw-bottom{ display:grid; grid-template-columns:1fr minmax(240px,1.2fr) 1fr auto; gap:12px; align-items:center; padding:10px 14px 14px; }
    [data-game="crossword"] .cw-bench{ display:flex; gap:10px; align-items:center; }
    [data-game="crossword"] .cw-bench.b{ justify-content:flex-end; }
    [data-game="crossword"] .cw-p{ text-align:center; }
    [data-game="crossword"] .cw-p .av{ width:44px; height:44px; border-radius:50%; overflow:hidden; border:2.5px solid var(--cw-blue); background:#dbeafe; margin:0 auto; }
    [data-game="crossword"] .cw-p.b .av{ border-color:var(--cw-orange); }
    [data-game="crossword"] .cw-p .av img{ width:100%; height:100%; object-fit:cover; }
    [data-game="crossword"] .cw-p .av.cpu{ display:flex; align-items:center; justify-content:center; font-size:22px; background:#ffe9d4; }
    [data-game="crossword"] .cw-p .nm{ font-size:11px; font-weight:800; color:var(--cw-ink); margin-top:3px; }
    [data-game="crossword"] .cw-p .st{ display:inline-block; font-size:10px; font-weight:900; border-radius:999px; padding:2px 9px; margin-top:2px; background:#e2e8f0; color:#586a82; }
    [data-game="crossword"] .cw-p .st.go{ background:var(--cw-blue); color:#fff; }
    [data-game="crossword"] .cw-p.b .st.go{ background:var(--cw-orange); }
    [data-game="crossword"] .cw-feed{ display:flex; align-items:center; justify-content:center; gap:9px; background:#fffdf2; border:1.5px solid #f3e3ad; border-radius:14px; padding:10px 16px; min-height:56px; font-size:15px; color:var(--cw-ink); text-align:center; }
    [data-game="crossword"] .cw-feed b{ font-weight:900; }
    [data-game="crossword"] .cw-feed .pts{ color:#c8860a; font-weight:900; }
    [data-game="crossword"] .cw-ctl{ display:flex; gap:8px; }
    [data-game="crossword"] .cw-ctlbtn{ width:46px; height:46px; border-radius:12px; border:1.5px solid var(--cw-line); background:#fff; cursor:pointer; font-size:17px; display:flex; align-items:center; justify-content:center; }
    [data-game="crossword"] .cw-ctlbtn:hover{ background:var(--cw-soft); }

    /* ============ overlays ============ */
    [data-game="crossword"] .cw-overlay{ position:fixed; inset:0; z-index:60; background:rgba(7,22,46,.72); display:flex; align-items:center; justify-content:center; padding:16px; }
    [data-game="crossword"] .cw-panel{ width:min(94vw,480px); background:#fff; border-radius:18px; padding:26px; text-align:center; box-shadow:0 24px 70px rgba(0,0,0,.4); }
    [data-game="crossword"] .cw-panel h3{ margin:0 0 6px; font-size:24px; font-weight:900; color:var(--cw-ink); }
    [data-game="crossword"] .cw-panel p{ margin:4px 0; font-size:14px; color:#586a82; }
    [data-game="crossword"] .cw-panel .row{ display:flex; gap:10px; justify-content:center; margin:14px 0; flex-wrap:wrap; }
    [data-game="crossword"] .cw-stat{ min-width:110px; background:var(--cw-soft); border-radius:12px; padding:10px; }
    [data-game="crossword"] .cw-stat b{ display:block; font-size:22px; color:var(--cw-ink); }
    [data-game="crossword"] .cw-stat span{ font-size:11px; font-weight:800; color:#7a8aa0; text-transform:uppercase; }
    [data-game="crossword"] .cw-btn{ border:0; cursor:pointer; border-radius:999px; padding:12px 30px; margin:4px; font-size:14px; font-weight:900; color:#fff; background:linear-gradient(120deg,var(--cw-blue),var(--cw-blue-deep)); }
    [data-game="crossword"] .cw-btn.alt{ background:#475569; }

    @media (max-width:980px){
        [data-game="crossword"] .cw-main{ grid-template-columns:1fr; }
        [data-game="crossword"] .cw-scorerow{ grid-template-columns:1fr 1fr; }
        [data-game="crossword"] .cw-mid{ grid-column:1 / -1; order:3; }
        [data-game="crossword"] .cw-bottom{ grid-template-columns:1fr; }
        [data-game="crossword"] .cw-bench.b{ justify-content:flex-start; }
    }
</style>
@endverbatim

    {{-- ============ CONFIG (shared component) ============ --}}
    @php $cwIsTeacher = ($ctx['role'] ?? 'student') === 'teacher'; @endphp
    <div id="cwConfig">
        <x-gamified-configuration
            title="Crossword Challenge"
            subtitle="Your subject's vocabulary, woven into one grid — solve every clue to finish the board."
            icon="🧩"
            :is-teacher="$cwIsTeacher"
            :items="[8, 12, 16]"
            :items-default="12"
            :types="['identification']"
            :difficulty="true"
            :modes="['solo', 'team']"
            mode-default="solo"
            start-label="Build my puzzle" />
    </div>

    {{-- ============ GAME ============ --}}
    <div id="cwGame" class="cw-stage cw-hidden">
        <div class="cw-top">
            <div class="cw-top-title">Crossword Challenge</div>
            <div id="cwTopMid" class="cw-top-mid">Challenge</div>
            <div class="cw-top-right">
                <span class="cw-timer">&#9201; <span id="cwTimer">--:--</span></span>
                <span class="cw-round">Round <span id="cwRound">1 of 1</span></span>
                <span id="cwLive" class="cw-live">Practice Game</span>
            </div>
        </div>

        <div class="cw-scorerow">
            <div id="cwCardA" class="cw-teamcard a">
                <div class="cw-badge">&#9883;</div>
                <div>
                    <div class="cw-tc-label" id="cwALabel">Team Einstein</div>
                    <div class="cw-tc-pts"><span id="cwAPts">0</span> <small>pts</small></div>
                    <div class="cw-tc-solved"><span id="cwASolved">0</span> words solved</div>
                </div>
                <div class="cw-chips" id="cwAChips" style="margin-left:auto;"></div>
            </div>

            <div class="cw-mid">
                <div class="cw-mid-q">Question <span id="cwQNow">1</span> of <span id="cwQTotal">0</span></div>
                <div class="cw-mid-s"><span id="cwSolvedN">0</span> solved &bull; <span id="cwRemainN">0</span> remaining</div>
                <div id="cwSeg" class="cw-seg"></div>
            </div>

            <div id="cwCardB" class="cw-teamcard b">
                <div class="cw-badge">&#127822;</div>
                <div>
                    <div class="cw-tc-label" id="cwBLabel">Team Newton</div>
                    <div class="cw-tc-pts"><span id="cwBPts">0</span> <small>pts</small></div>
                    <div class="cw-tc-solved"><span id="cwBSolved">0</span> words solved</div>
                </div>
                <div class="cw-chips" id="cwBChips" style="margin-right:auto;"></div>
            </div>
        </div>

        <div class="cw-main">
            <div class="cw-gridwrap"><div id="cwGrid" class="cw-grid" role="grid" aria-label="Crossword grid"></div></div>

            <aside class="cw-clues">
                <div class="cw-clues-head">
                    <div class="cw-clues-title">CLUES</div>
                    <div class="cw-pager">
                        <button type="button" id="cwPrev" class="cw-pgbtn" aria-label="Previous clue">&#8249;</button>
                        <span><span id="cwPagerNow">1</span> / <span id="cwPagerTotal">0</span></span>
                        <button type="button" id="cwNext" class="cw-pgbtn" aria-label="Next clue">&#8250;</button>
                    </div>
                </div>
                <div class="cw-tabs">
                    <button type="button" id="cwTabA" class="cw-tab on">Across</button>
                    <button type="button" id="cwTabD" class="cw-tab">Down</button>
                </div>

                <div class="cw-current">
                    <div class="cw-cur-num" id="cwCurNum">&mdash;</div>
                    <div class="cw-cur-clue" id="cwCurClue">Pick a clue to begin.</div>
                    <div class="cw-cur-len" id="cwCurLen"></div>
                    <div class="cw-boxes" id="cwBoxes"></div>
                    <button type="button" id="cwSubmit" class="cw-submit">Submit Answer</button>
                    <div class="cw-minor">
                        <button type="button" id="cwHint" class="cw-minorbtn">&#128161; Hint (&minus;25)</button>
                        <button type="button" id="cwSkip" class="cw-minorbtn">&#187; Skip</button>
                    </div>
                </div>

                <div id="cwList" class="cw-list"></div>
                <div class="cw-list-foot"><span id="cwFootTotal">0</span> total clues &bull; <span id="cwFootA">0</span> Across &bull; <span id="cwFootD">0</span> Down</div>
            </aside>
        </div>

        <div class="cw-bottom">
            <div id="cwBenchA" class="cw-bench a"></div>
            <div class="cw-feed"><span>&#10024;</span><span id="cwFeed">Solve your first word!</span></div>
            <div id="cwBenchB" class="cw-bench b"></div>
            <div class="cw-ctl">
                <button type="button" id="cwPause" class="cw-ctlbtn" title="Pause" aria-label="Pause">&#10073;&#10073;</button>
                <button type="button" id="cwSound" class="cw-ctlbtn" title="Sound" aria-label="Toggle sound">&#128266;</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';
    const ENDPOINT = @json(route('tools.games.questions'));
    const CATALOG = @json(route('tools.games.index'));
    const AVATAR = @json(asset('images/games/avatar-placeholder.svg'));
    const el = (id) => document.getElementById(id);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));

    if (window.self !== window.top) document.querySelector('[data-game="crossword"]').classList.add('cw-embedded');

    // ---------------- audio (generated blips, mute persisted) ----------------
    let actx = null, muted = false;
    try { muted = localStorage.getItem('cw_muted') === '1'; } catch (e) {}
    function tone(f, d, type, g, when) {
        if (muted) return;
        try {
            actx = actx || new (window.AudioContext || window.webkitAudioContext)();
            const t = actx.currentTime + (when || 0), o = actx.createOscillator(), gn = actx.createGain();
            o.type = type || 'sine'; o.frequency.value = f;
            gn.gain.setValueAtTime(g || .07, t); gn.gain.exponentialRampToValueAtTime(.0001, t + d);
            o.connect(gn).connect(actx.destination); o.start(t); o.stop(t + d + .02);
        } catch (e) {}
    }
    const sfx = {
        ok() { tone(660, .12, 'triangle'); tone(880, .16, 'triangle', .07, .09); },
        bad() { tone(190, .25, 'sawtooth', .05); },
        rival() { tone(420, .12, 'square', .04); },
        end() { [523, 659, 784, 1047].forEach((f, i) => tone(f, .2, 'triangle', .07, i * .12)); },
    };
    el('cwSound').textContent = muted ? '\u{1F507}' : '\u{1F50A}';
    el('cwSound').addEventListener('click', () => {
        muted = !muted;
        try { localStorage.setItem('cw_muted', muted ? '1' : '0'); } catch (e) {}
        el('cwSound').textContent = muted ? '\u{1F507}' : '\u{1F50A}';
    });

    // ---------------- state ----------------
    let words = [];        // {id,num,dir:'A'|'D',row,col,answer,clue,len,solvedBy}
    let grid = {};         // "r,c" -> {ch, wordIds:[], solved, by, num?}
    let rows = 0, cols = 0;
    let curIdx = -1;       // index into the ordered clue list
    let typed = [];        // player letters for the current word (null = empty)
    let mode = 'solo';
    let scores = { A: 0, B: 0 };
    let solvedCount = { A: 0, B: 0 };
    let turn = 'A';
    let paused = false, over = false;
    let timerLeft = 0, timerH = null, cpuH = null;

    const orderedWords = () => words.slice().sort((x, y) => (x.dir === y.dir ? x.num - y.num : (x.dir === 'A' ? -1 : 1)));

    // ---------------- crossword generator ----------------
    // Greedy placement with overlap scoring; several shuffled attempts, the
    // board that fits the most words wins.
    function generate(entries, target) {
        let best = null;
        for (let attempt = 0; attempt < 14; attempt++) {
            const pool = entries.slice().sort(() => Math.random() - .5).sort((a, b) => b.answer.length - a.answer.length);
            const g = {};
            const placed = [];
            const put = (w, r, c, dir) => {
                for (let i = 0; i < w.answer.length; i++) {
                    const k = dir === 'A' ? (r + ',' + (c + i)) : ((r + i) + ',' + c);
                    g[k] = { ch: w.answer[i] };
                }
                placed.push({ answer: w.answer, clue: w.clue, row: r, col: c, dir: dir });
            };
            const fits = (w, r, c, dir) => {
                let overlap = 0;
                for (let i = 0; i < w.answer.length; i++) {
                    const rr = dir === 'A' ? r : r + i, cc = dir === 'A' ? c + i : c;
                    const cell = g[rr + ',' + cc];
                    if (cell) {
                        if (cell.ch !== w.answer[i]) return -1;
                        overlap++;
                    } else {
                        // keep parallel words apart: side neighbours must be empty
                        const s1 = dir === 'A' ? (rr - 1) + ',' + cc : rr + ',' + (cc - 1);
                        const s2 = dir === 'A' ? (rr + 1) + ',' + cc : rr + ',' + (cc + 1);
                        if (g[s1] || g[s2]) return -1;
                    }
                }
                const before = dir === 'A' ? r + ',' + (c - 1) : (r - 1) + ',' + c;
                const after = dir === 'A' ? r + ',' + (c + w.answer.length) : (r + w.answer.length) + ',' + c;
                if (g[before] || g[after]) return -1;
                return overlap;
            };

            put(pool[0], 0, 0, 'A');
            for (let p = 1; p < pool.length && placed.length < target; p++) {
                const w = pool[p];
                let spot = null;
                for (const key of Object.keys(g)) {
                    const parts = key.split(','), gr = +parts[0], gc = +parts[1];
                    for (let i = 0; i < w.answer.length; i++) {
                        if (g[key].ch !== w.answer[i]) continue;
                        for (const cand of [[gr - i, gc, 'D'], [gr, gc - i, 'A']]) {
                            const s = fits(w, cand[0], cand[1], cand[2]);
                            if (s > 0 && (!spot || s > spot.s)) spot = { r: cand[0], c: cand[1], dir: cand[2], s: s };
                        }
                    }
                }
                if (spot) put(w, spot.r, spot.c, spot.dir);
            }
            if (!best || placed.length > best.placed.length) best = { placed: placed };
            if (best.placed.length >= target) break;
        }
        return best;
    }

    function buildBoard(placed) {
        let minR = 1e9, minC = 1e9, maxR = -1e9, maxC = -1e9;
        placed.forEach(w => {
            const endR = w.dir === 'A' ? w.row : w.row + w.answer.length - 1;
            const endC = w.dir === 'A' ? w.col + w.answer.length - 1 : w.col;
            minR = Math.min(minR, w.row); maxR = Math.max(maxR, endR);
            minC = Math.min(minC, w.col); maxC = Math.max(maxC, endC);
        });
        rows = maxR - minR + 1; cols = maxC - minC + 1;
        grid = {};
        words = placed.map((w, i) => ({
            id: i, answer: w.answer, clue: w.clue, dir: w.dir,
            row: w.row - minR, col: w.col - minC, len: w.answer.length,
            solvedBy: null, num: 0,
        }));
        words.forEach(w => {
            for (let i = 0; i < w.len; i++) {
                const r = w.dir === 'A' ? w.row : w.row + i, c = w.dir === 'A' ? w.col + i : w.col;
                const k = r + ',' + c;
                grid[k] = grid[k] || { ch: w.answer[i], wordIds: [], solved: false, by: null };
                grid[k].wordIds.push(w.id);
            }
        });
        // standard numbering: row-major scan, a cell starting any word numbers it
        let n = 0;
        for (let r = 0; r < rows; r++) {
            for (let c = 0; c < cols; c++) {
                const starts = words.filter(w => w.row === r && w.col === c);
                if (starts.length) { n++; starts.forEach(w => { w.num = n; }); grid[r + ',' + c].num = n; }
            }
        }
    }

    // ---------------- rendering ----------------
    function renderGrid() {
        const box = el('cwGrid');
        const cell = Math.max(24, Math.min(38, Math.floor(560 / Math.max(rows, cols))));
        box.style.setProperty('--cell', cell + 'px');
        box.style.gridTemplateColumns = 'repeat(' + cols + ', ' + cell + 'px)';
        let html = '';
        const list = orderedWords();
        const cur = curIdx >= 0 ? list[curIdx] : null;
        for (let r = 0; r < rows; r++) {
            for (let c = 0; c < cols; c++) {
                const k = r + ',' + c, cd = grid[k];
                if (!cd) { html += '<div class="cw-cell blk" aria-hidden="true"></div>'; continue; }
                const inCur = cur && cd.wordIds.indexOf(cur.id) >= 0;
                const cls = 'cw-cell' + (inCur ? ' act' : '') + (cd.solved ? ' ok' : '') + (cd.by === 'B' ? ' byB' : '');
                html += '<div class="' + cls + '" data-k="' + k + '" role="gridcell">'
                    + (cd.num ? '<span class="n">' + cd.num + '</span>' : '')
                    + (cd.solved ? esc(cd.ch) : '')
                    + '</div>';
            }
        }
        box.innerHTML = html;
        box.querySelectorAll('.cw-cell[data-k]').forEach(cl => cl.addEventListener('click', () => {
            const ids = grid[cl.dataset.k].wordIds;
            const l = orderedWords();
            for (const id of ids) {
                const i = l.findIndex(w => w.id === id && !w.solvedBy);
                if (i >= 0) return selectClue(i);
            }
        }));
    }

    function renderScore() {
        el('cwAPts').textContent = scores.A; el('cwBPts').textContent = scores.B;
        el('cwASolved').textContent = solvedCount.A; el('cwBSolved').textContent = solvedCount.B;
        const done = words.filter(w => w.solvedBy).length;
        el('cwSolvedN').textContent = done;
        el('cwRemainN').textContent = words.length - done;
        el('cwQNow').textContent = Math.min(done + 1, words.length);
        el('cwQTotal').textContent = words.length;
        el('cwSeg').innerHTML = orderedWords().map((w, i) =>
            '<i class="' + (w.solvedBy === 'A' ? 'a' : w.solvedBy === 'B' ? 'b' : '') + (i === curIdx ? ' cur' : '') + '"></i>').join('');
        el('cwCardA').classList.toggle('turn', turn === 'A' && !over);
        el('cwCardB').classList.toggle('turn', turn === 'B' && !over);
        renderBench();
    }

    function chip(cpu) {
        return '<div class="cw-chip' + (cpu ? ' cpu' : '') + '">' + (cpu ? '&#129302;' : '<img src="' + AVATAR + '" alt="">') + '</div>';
    }
    function renderBench() {
        const soloRival = mode === 'solo';
        el('cwAChips').innerHTML = chip(false) + (mode === 'team' ? chip(false) + chip(false) : '');
        el('cwBChips').innerHTML = soloRival ? chip(true) : chip(false) + chip(false) + chip(false);
        el('cwBenchA').innerHTML =
            '<div class="cw-p a"><div class="av"><img src="' + AVATAR + '" alt=""></div>' +
            '<div class="nm">' + (mode === 'team' ? 'Team Einstein' : 'You') + '</div>' +
            '<div class="st' + (turn === 'A' ? ' go' : '') + '">' + (turn === 'A' ? 'Answering' : 'Ready') + '</div></div>';
        el('cwBenchB').innerHTML =
            '<div class="cw-p b"><div class="av' + (soloRival ? ' cpu' : '') + '">' + (soloRival ? '&#129302;' : '<img src="' + AVATAR + '" alt="">') + '</div>' +
            '<div class="nm">' + (soloRival ? 'Rival Bot' : 'Team Newton') + '</div>' +
            '<div class="st' + (turn === 'B' ? ' go' : '') + '">' + (turn === 'B' ? 'Answering' : (soloRival ? 'Thinking' : 'Ready')) + '</div></div>';
    }

    function renderList() {
        const list = orderedWords();
        el('cwList').innerHTML = list.map((w, i) =>
            '<div class="cw-li' + (i === curIdx ? ' on' : '') + (w.solvedBy ? ' done' : '') + '" data-i="' + i + '">' +
            '<span class="k">' + w.num + ' ' + (w.dir === 'A' ? 'Across' : 'Down') + '</span>' +
            '<span class="t">' + esc(w.clue) + '</span>' +
            (w.solvedBy ? '<span class="who ' + w.solvedBy.toLowerCase() + '">&#10003;</span>' : '') +
            '</div>').join('');
        el('cwList').querySelectorAll('.cw-li').forEach(li =>
            li.addEventListener('click', () => selectClue(parseInt(li.dataset.i, 10))));
        el('cwFootTotal').textContent = list.length;
        el('cwFootA').textContent = list.filter(w => w.dir === 'A').length;
        el('cwFootD').textContent = list.filter(w => w.dir === 'D').length;
        el('cwPagerTotal').textContent = list.length;
        el('cwPagerNow').textContent = curIdx >= 0 ? (curIdx + 1) : '0';
        el('cwTabA').classList.toggle('on', curIdx < 0 || list[curIdx].dir === 'A');
        el('cwTabD').classList.toggle('on', curIdx >= 0 && list[curIdx].dir === 'D');
    }

    function lockedLetters(w) {
        const out = [];
        for (let i = 0; i < w.len; i++) {
            const r = w.dir === 'A' ? w.row : w.row + i, c = w.dir === 'A' ? w.col + i : w.col;
            const cell = grid[r + ',' + c];
            out.push(cell.solved ? cell.ch : null);
        }
        return out;
    }

    function renderCurrent() {
        const list = orderedWords();
        const w = curIdx >= 0 ? list[curIdx] : null;
        if (!w) {
            el('cwCurNum').textContent = '—';
            el('cwCurClue').textContent = 'Pick a clue to begin.';
            el('cwCurLen').textContent = '';
            el('cwBoxes').innerHTML = '';
            return;
        }
        el('cwCurNum').textContent = w.num + ' ' + (w.dir === 'A' ? 'Across' : 'Down');
        el('cwCurClue').textContent = w.clue;
        el('cwCurLen').textContent = w.len + ' letters';
        const locks = lockedLetters(w);
        let curSet = false;
        el('cwBoxes').innerHTML = locks.map((lk, i) => {
            const t = lk || typed[i];
            let cur = false;
            if (!lk && !typed[i] && !curSet) { cur = true; curSet = true; }
            return '<div class="cw-box' + (lk ? ' lock' : '') + (cur ? ' cur' : '') + '">' + (t ? esc(t) : '&nbsp;') + '</div>';
        }).join('');
    }

    function refresh() { renderGrid(); renderScore(); renderList(); renderCurrent(); }

    function selectClue(i) {
        const list = orderedWords();
        if (i < 0 || i >= list.length) return;
        curIdx = i;
        typed = new Array(list[i].len).fill(null);
        refresh();
    }
    function nextUnsolved(from, step) {
        const list = orderedWords();
        for (let s = 1; s <= list.length; s++) {
            const i = ((from + step * s) % list.length + list.length) % list.length;
            if (!list[i].solvedBy) return i;
        }
        return from;
    }

    // ---------------- answering ----------------
    function feed(html) { el('cwFeed').innerHTML = html; }

    function submit() {
        if (over || paused || curIdx < 0) return;
        const w = orderedWords()[curIdx];
        if (w.solvedBy) return;
        const locks = lockedLetters(w);
        const guess = locks.map((lk, i) => lk || typed[i] || '').join('');
        if (guess.length < w.len) { sfx.bad(); return shakeBoxes(); }

        if (guess === w.answer) {
            solveWord(w, mode === 'team' ? turn : 'A');
        } else {
            sfx.bad(); shakeBoxes();
            feed('<b>' + esc(guess) + '</b> is not it &mdash; keep thinking!');
            if (mode === 'team') passTurn();
            typed = new Array(w.len).fill(null);
            renderCurrent();
        }
    }
    function shakeBoxes() {
        el('cwBoxes').querySelectorAll('.cw-box:not(.lock)').forEach(b => {
            b.classList.remove('bad'); void b.offsetWidth; b.classList.add('bad');
        });
    }

    function solveWord(w, by) {
        w.solvedBy = by;
        for (let i = 0; i < w.len; i++) {
            const r = w.dir === 'A' ? w.row : w.row + i, c = w.dir === 'A' ? w.col + i : w.col;
            const cell = grid[r + ',' + c];
            cell.solved = true; cell.by = cell.by || by;
        }
        scores[by] += 100; solvedCount[by]++;
        if (by === 'A') sfx.ok(); else sfx.rival();
        const who = by === 'A' ? (mode === 'team' ? 'Team Einstein' : 'You') : (mode === 'solo' ? 'Rival Bot' : 'Team Newton');
        feed('&#10024; <b>' + esc(who) + '</b> solved <b>' + esc(w.answer) + '</b> <span class="pts">+100 pts</span>');
        if (mode === 'team') passTurn();

        if (words.every(x => x.solvedBy)) return endGame(false);
        const list = orderedWords();
        if (curIdx < 0 || list[curIdx].solvedBy) selectClue(nextUnsolved(curIdx, 1));
        else refresh();
    }

    function passTurn() { turn = turn === 'A' ? 'B' : 'A'; }

    function useHint() {
        if (over || paused || curIdx < 0) return;
        const w = orderedWords()[curIdx];
        if (w.solvedBy) return;
        const locks = lockedLetters(w);
        let i = -1;
        for (let x = 0; x < locks.length; x++) if (!locks[x]) { i = x; break; }
        if (i < 0) return;
        const r = w.dir === 'A' ? w.row : w.row + i, c = w.dir === 'A' ? w.col + i : w.col;
        grid[r + ',' + c].solved = true;
        const side = mode === 'team' ? turn : 'A';
        scores[side] = Math.max(0, scores[side] - 25);
        typed[i] = null;
        feed('&#128161; Hint used <span class="pts">&minus;25 pts</span> &mdash; a letter is revealed.');
        refresh();
    }

    // ---------------- solo rival bot ----------------
    function cpuTick(intervalMs) {
        cpuH = setInterval(() => {
            if (paused || over) return;
            const open = words.filter(w => !w.solvedBy);
            if (!open.length) return;
            solveWord(open[Math.floor(Math.random() * open.length)], 'B');
        }, intervalMs);
    }

    // ---------------- timer / lifecycle ----------------
    function startTimer(total) {
        timerLeft = total;
        const draw = () => {
            const m = Math.floor(timerLeft / 60), s = timerLeft % 60;
            el('cwTimer').textContent = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
        };
        draw();
        timerH = setInterval(() => {
            if (paused || over) return;
            timerLeft--; draw();
            if (timerLeft <= 0) endGame(true);
        }, 1000);
    }

    function endGame(timeUp) {
        if (over) return;
        over = true;
        clearInterval(timerH); clearInterval(cpuH);
        sfx.end();
        const winner = scores.A === scores.B ? null : (scores.A > scores.B ? 'A' : 'B');
        const nameA = mode === 'team' ? 'Team Einstein' : 'You';
        const nameB = mode === 'solo' ? 'Rival Bot' : 'Team Newton';
        const ov = document.createElement('div');
        ov.className = 'cw-overlay';
        ov.innerHTML = '<div class="cw-panel">' +
            '<h3>' + (timeUp ? '&#9201; Time&#8217;s up!' : '&#127942; Puzzle complete!') + '</h3>' +
            '<p>' + (winner === null ? 'It&#8217;s a tie!' : '<b>' + esc(winner === 'A' ? nameA : nameB) + '</b> wins the board!') + '</p>' +
            '<div class="row">' +
            '<div class="cw-stat"><b>' + scores.A + '</b><span>' + esc(nameA) + '</span></div>' +
            '<div class="cw-stat"><b>' + scores.B + '</b><span>' + esc(nameB) + '</span></div>' +
            '<div class="cw-stat"><b>' + (solvedCount.A + solvedCount.B) + '/' + words.length + '</b><span>Words solved</span></div>' +
            '</div>' +
            '<button type="button" class="cw-btn" data-act="again">Play Again</button>' +
            '<button type="button" class="cw-btn alt" data-act="exit">Exit</button>' +
            '</div>';
        document.querySelector('[data-game="crossword"]').appendChild(ov);
        ov.querySelector('[data-act="again"]').addEventListener('click', () => window.location.reload());
        ov.querySelector('[data-act="exit"]').addEventListener('click', () => {
            if (window.parent !== window) window.parent.postMessage({ type: 'schoollms:game-exit' }, '*');
            else window.location.href = CATALOG;
        });
    }

    el('cwPause').addEventListener('click', () => {
        if (over) return;
        paused = !paused;
        el('cwPause').innerHTML = paused ? '&#9654;' : '&#10073;&#10073;';
        feed(paused ? '&#9208; Paused &mdash; the clock is stopped.' : 'Back to it!');
    });

    // ---------------- typing ----------------
    document.addEventListener('keydown', (e) => {
        if (el('cwGame').classList.contains('cw-hidden') || over || paused || curIdx < 0) return;
        if (e.target && /^(input|textarea|select)$/i.test(e.target.tagName)) return;
        if (/^[a-zA-Z]$/.test(e.key)) {
            const w = orderedWords()[curIdx];
            const locks = lockedLetters(w);
            for (let i = 0; i < w.len; i++) {
                if (!locks[i] && !typed[i]) { typed[i] = e.key.toUpperCase(); break; }
            }
            renderCurrent();
            e.preventDefault();
        } else if (e.key === 'Backspace') {
            for (let i = typed.length - 1; i >= 0; i--) if (typed[i]) { typed[i] = null; break; }
            renderCurrent(); e.preventDefault();
        } else if (e.key === 'Enter') { submit(); e.preventDefault(); }
    });

    el('cwSubmit').addEventListener('click', submit);
    el('cwHint').addEventListener('click', useHint);
    el('cwSkip').addEventListener('click', () => { if (curIdx >= 0) selectClue(nextUnsolved(curIdx, 1)); });
    el('cwPrev').addEventListener('click', () => { if (curIdx >= 0) selectClue(nextUnsolved(curIdx, -1)); });
    el('cwNext').addEventListener('click', () => { if (curIdx >= 0) selectClue(nextUnsolved(curIdx, 1)); });
    el('cwTabA').addEventListener('click', () => {
        const i = orderedWords().findIndex(w => w.dir === 'A' && !w.solvedBy);
        if (i >= 0) selectClue(i);
    });
    el('cwTabD').addEventListener('click', () => {
        const i = orderedWords().findIndex(w => w.dir === 'D' && !w.solvedBy);
        if (i >= 0) selectClue(i);
    });

    // ---------------- start (shared config hands off here) ----------------
    document.addEventListener('gamified-config:start', async (e) => {
        const cfg = e.detail;
        mode = cfg.mode === 'team' ? 'team' : 'solo';
        window.GamifiedConfig.loading(true, 'Building your puzzle…');
        try {
            const params = new URLSearchParams({ type: 'identification', limit: '30', difficulty: cfg.difficulty || 'mixed' });
            const scope = window.GameScope || {};
            ['subject_id', 'topic_id', 'lesson_id', 'competency_id', 'academic_level_id'].forEach(k => { if (scope[k]) params.set(k, scope[k]); });
            const res = await fetch(ENDPOINT + '?' + params.toString(), { headers: { Accept: 'application/json' } });
            if (!res.ok) throw new Error('Could not load questions (' + res.status + ').');
            const data = await res.json();

            // Only single-word alphabetic answers can live on a grid.
            const entries = (data.questions || [])
                .map(q => ({ clue: q.question, answer: String(q.answer || '').trim().toUpperCase() }))
                .filter(q => /^[A-Z]{3,12}$/.test(q.answer));
            if (entries.length < 4) {
                throw new Error('Not enough one-word Identification answers for this selection (found ' + entries.length + ', need at least 4). Try a broader scope or ask a teacher to add short-answer items.');
            }

            const target = Math.min(cfg.items || 12, entries.length);
            const best = generate(entries, target);
            if (!best || best.placed.length < 4) throw new Error('Could not weave these words into a grid — try again or widen the scope.');
            buildBoard(best.placed);

            const scopeLine = (window.GameScope && window.GameScope.summary) ? window.GameScope.summary() : 'Practice';
            el('cwTopMid').textContent = words.length + '-Question ' + scopeLine + ' Challenge';
            el('cwLive').textContent = mode === 'team' ? 'Live Classroom Game' : 'Practice Game';
            el('cwALabel').textContent = mode === 'team' ? 'Team Einstein' : 'You';
            el('cwBLabel').textContent = mode === 'solo' ? 'Rival Bot' : 'Team Newton';

            el('cwConfig').classList.add('cw-hidden');
            el('cwGame').classList.remove('cw-hidden');

            turn = 'A';
            selectClue(0);
            startTimer(words.length * 45);
            if (mode === 'solo') {
                const pace = { average: 60, mixed: 45, advanced: 32 }[cfg.difficulty] || 45;
                cpuTick(pace * 1000);
            }
        } catch (err) {
            window.GamifiedConfig.error(err.message);
        } finally {
            window.GamifiedConfig.loading(false);
        }
    });
})();
</script>
