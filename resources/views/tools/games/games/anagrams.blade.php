{{-- Anagram Challenge — Identification words from the bank, played as a
     team-vs-team unscramble showdown.

     Question supply: /tools/games/api/questions?type=identification (school-
     scoped, GameScope ☰ filters); the identification answer is scrambled into
     letter tiles, its question is the prompt. Falls back to a small built-in
     practice deck when the bank has none. Client-graded practice, like every
     catalog game.

     Pre-game setup is the shared <x-gamified-configuration> component
     (Constitution §11B): items / difficulty / mode live there, the game only
     listens for 'gamified-config:start'.

     Layout mirrors the approved key art: navy top bar (title • showdown name
     • timer • question counter • live badge), solid blue/orange team cards
     with avatar chips + words-solved cells flanking a numbered question bar
     and the turn pill, one big question card (scrambled tiles + SHUFFLE,
     answer slots, SUBMIT / HINT / CLEAR, attempt counter, hint callout) with
     a ROUND SCORE sidebar (time bonus, no-hints bonus, streak, completion
     donut), and a bottom bar with player benches, a placed-letter feed, and
     pause / sound / settings. Player photos are cartoon SVG avatars generated
     in-page — placeholders until real student profile images are on file.
     Styling is scoped ag-* (stale-build rule). --}}

<div data-game="anagrams" class="ag-root">

@verbatim
<style>
    [data-game="anagrams"]{
        --ag-blue:#1f5fd0; --ag-blue-deep:#0f3aa0; --ag-blue-soft:#e8f0ff;
        --ag-orange:#e56a1c; --ag-orange-deep:#c04f0d;
        --ag-navy:#0f2545; --ag-ink:#1f2a3a; --ag-line:#dfe6f0; --ag-soft:#f4f7fc;
        --ag-green:#16a34a; --ag-green-soft:#e6f6ec; --ag-red:#dc2626;
    }
    [data-game="anagrams"] *{ box-sizing:border-box; }
    [data-game="anagrams"] .ag-hidden{ display:none !important; }
    [data-game="anagrams"].ag-embedded .ag-stage{ position:relative; left:50%; transform:translateX(-50%); width:min(1500px,96vw); }

    [data-game="anagrams"] .ag-stage{ background:#eef2f8; border:1px solid var(--ag-line); border-radius:16px; overflow:hidden; box-shadow:0 10px 30px rgba(15,37,69,.08); }

    /* ============ top bar ============ */
    [data-game="anagrams"] .ag-top{ display:flex; align-items:center; justify-content:space-between; gap:14px; background:var(--ag-navy); color:#fff; padding:13px 18px; flex-wrap:wrap; }
    [data-game="anagrams"] .ag-top-title{ font-size:17px; font-weight:900; letter-spacing:.05em; text-transform:uppercase; }
    [data-game="anagrams"] .ag-top-mid{ font-size:15px; font-weight:800; flex:1; text-align:center; min-width:180px; }
    [data-game="anagrams"] .ag-top-right{ display:flex; align-items:center; gap:12px; font-weight:800; font-size:14px; }
    [data-game="anagrams"] .ag-timer{ display:inline-flex; align-items:center; gap:6px; font-variant-numeric:tabular-nums; }
    [data-game="anagrams"] .ag-qno{ border-left:1px solid rgba(255,255,255,.25); padding-left:12px; font-size:13px; }
    [data-game="anagrams"] .ag-live{ display:inline-flex; align-items:center; gap:7px; background:#123d2a; border:1px solid #2e7d54; border-radius:999px; padding:6px 13px; font-size:12px; font-weight:800; }
    [data-game="anagrams"] .ag-live::before{ content:''; width:8px; height:8px; border-radius:50%; background:#34d17b; box-shadow:0 0 6px #34d17b; }

    /* ============ score row (solid team cards) ============ */
    [data-game="anagrams"] .ag-scorerow{ display:grid; grid-template-columns:minmax(250px,1fr) minmax(240px,1.25fr) minmax(250px,1fr); gap:12px; align-items:stretch; padding:12px 14px 4px; }
    [data-game="anagrams"] .ag-teamcard{ display:flex; align-items:stretch; border-radius:16px; overflow:hidden; color:#fff; background:linear-gradient(140deg,var(--ag-blue),var(--ag-blue-deep)); box-shadow:0 8px 20px rgba(15,58,160,.3); }
    [data-game="anagrams"] .ag-teamcard.b{ background:linear-gradient(140deg,var(--ag-orange),var(--ag-orange-deep)); box-shadow:0 8px 20px rgba(192,79,13,.3); flex-direction:row-reverse; }
    [data-game="anagrams"] .ag-tc-main{ flex:1; padding:12px 14px 10px; }
    [data-game="anagrams"] .ag-tc-head{ display:flex; align-items:center; gap:10px; }
    [data-game="anagrams"] .ag-teamcard.b .ag-tc-head{ flex-direction:row-reverse; text-align:right; }
    [data-game="anagrams"] .ag-tc-trophy{ width:44px; height:44px; flex:none; border-radius:50%; background:rgba(255,255,255,.22); display:flex; align-items:center; justify-content:center; font-size:22px; }
    [data-game="anagrams"] .ag-tc-label{ font-size:12.5px; font-weight:900; letter-spacing:.06em; text-transform:uppercase; opacity:.95; white-space:nowrap; }
    [data-game="anagrams"] .ag-tc-pts{ font-size:24px; font-weight:900; line-height:1.05; }
    [data-game="anagrams"] .ag-tc-pts small{ font-size:13px; font-weight:800; }
    [data-game="anagrams"] .ag-chips{ display:flex; margin-top:9px; }
    [data-game="anagrams"] .ag-teamcard.b .ag-chips{ justify-content:flex-end; }
    [data-game="anagrams"] .ag-chip{ width:36px; height:36px; border-radius:50%; overflow:hidden; border:2.5px solid rgba(255,255,255,.9); background:#dbeafe; margin-left:-8px; }
    [data-game="anagrams"] .ag-chip:first-child{ margin-left:0; }
    [data-game="anagrams"] .ag-chip img{ width:100%; height:100%; object-fit:cover; display:block; }
    [data-game="anagrams"] .ag-chip.cpu{ display:flex; align-items:center; justify-content:center; font-size:18px; background:#ffe9d4; }
    [data-game="anagrams"] .ag-tc-solved{ flex:none; width:76px; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:1px; border-left:1.5px solid rgba(255,255,255,.28); padding:8px; text-align:center; }
    [data-game="anagrams"] .ag-teamcard.b .ag-tc-solved{ border-left:0; border-right:1.5px solid rgba(255,255,255,.28); }
    [data-game="anagrams"] .ag-tc-solved b{ font-size:26px; font-weight:900; line-height:1; }
    [data-game="anagrams"] .ag-tc-solved span{ font-size:10.5px; font-weight:800; line-height:1.2; }

    [data-game="anagrams"] .ag-mid{ display:flex; flex-direction:column; align-items:center; justify-content:center; gap:8px; }
    [data-game="anagrams"] .ag-seg{ display:flex; gap:5px; flex-wrap:wrap; justify-content:center; }
    [data-game="anagrams"] .ag-seg .s{ display:flex; flex-direction:column; align-items:center; gap:2px; }
    [data-game="anagrams"] .ag-seg i{ width:17px; height:20px; border-radius:4px; background:#dfe6f0; border:1.5px solid #c9d4e4; }
    [data-game="anagrams"] .ag-seg .s.a i{ background:var(--ag-blue); border-color:var(--ag-blue-deep); }
    [data-game="anagrams"] .ag-seg .s.b i{ background:var(--ag-orange); border-color:var(--ag-orange-deep); }
    [data-game="anagrams"] .ag-seg .s.x i{ background:#aab6c8; border-color:#8fa0b8; }
    [data-game="anagrams"] .ag-seg .s.cur i{ background:#fff; border:2px solid var(--ag-blue); }
    [data-game="anagrams"] .ag-seg .s em{ font-style:normal; font-size:10px; font-weight:800; color:#7a8aa0; }
    [data-game="anagrams"] .ag-seg .s.cur em{ color:var(--ag-blue-deep); }
    [data-game="anagrams"] .ag-turnpill{ display:inline-flex; align-items:center; gap:7px; border-radius:999px; padding:7px 20px; font-size:13.5px; font-weight:900; color:#fff; background:var(--ag-blue); box-shadow:0 4px 10px rgba(31,95,208,.35); }
    [data-game="anagrams"] .ag-turnpill.b{ background:var(--ag-orange); box-shadow:0 4px 10px rgba(229,106,28,.35); }

    [data-game="anagrams"] .ag-note{ margin:8px 14px 0; border:1px solid #f6d98a; background:#fdf6e3; color:#8a6d1b; border-radius:10px; padding:8px 12px; font-size:12.5px; font-weight:600; }

    /* ============ question card ============ */
    [data-game="anagrams"] .ag-main{ margin:8px 14px 12px; background:#fff; border:1.5px solid #bcd0ea; border-radius:14px; display:grid; grid-template-columns:minmax(0,1fr) minmax(230px,290px); }
    [data-game="anagrams"] .ag-q{ padding:18px 20px; min-width:0; }
    [data-game="anagrams"] .ag-q-head{ display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
    [data-game="anagrams"] .ag-q-label{ font-size:14px; font-weight:900; letter-spacing:.08em; text-transform:uppercase; color:var(--ag-blue-deep); }
    [data-game="anagrams"] .ag-pill{ display:inline-flex; align-items:center; border-radius:999px; padding:4px 13px; font-size:12px; font-weight:800; background:var(--ag-blue-soft); color:var(--ag-blue-deep); border:1.5px solid #c9dcff; }
    [data-game="anagrams"] .ag-pill.green{ background:var(--ag-green-soft); color:#0f7a40; border-color:#b5e2c6; }
    [data-game="anagrams"] .ag-q-text{ font-size:25px; font-weight:800; color:var(--ag-ink); line-height:1.25; margin:10px 0 4px; }
    [data-game="anagrams"] .ag-q-sub{ font-size:14px; color:#586a82; }
    [data-game="anagrams"] .ag-hr{ border:0; border-top:1.5px solid var(--ag-line); margin:14px 0; }
    [data-game="anagrams"] .ag-secrow{ display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:10px; }
    [data-game="anagrams"] .ag-sec{ font-size:13px; font-weight:900; letter-spacing:.09em; text-transform:uppercase; color:var(--ag-blue-deep); }
    [data-game="anagrams"] .ag-shuffle{ display:inline-flex; align-items:center; gap:7px; border:1.5px solid #c9dcff; background:#fff; color:var(--ag-blue-deep); border-radius:10px; padding:8px 16px; font-size:12px; font-weight:900; letter-spacing:.06em; text-transform:uppercase; cursor:pointer; }
    [data-game="anagrams"] .ag-shuffle:hover:not(:disabled){ background:var(--ag-blue-soft); }
    [data-game="anagrams"] .ag-shuffle:disabled{ opacity:.5; cursor:default; }
    [data-game="anagrams"] .ag-tiles{ display:flex; gap:9px; flex-wrap:wrap; }
    [data-game="anagrams"] .ag-tile{ width:52px; height:52px; border-radius:11px; border:1.5px solid #cbd6e6; background:#fff; display:flex; align-items:center; justify-content:center; font-size:24px; font-weight:900; color:var(--ag-ink); cursor:pointer; box-shadow:0 2px 6px rgba(15,37,69,.08); transition:transform .08s, opacity .15s; }
    @media (hover:hover) and (pointer:fine){
        [data-game="anagrams"] .ag-tiles:not(.ag-nohover) .ag-tile:hover:not(:disabled){ transform:translateY(-2px); border-color:var(--ag-blue); color:var(--ag-blue-deep); }
    }
    [data-game="anagrams"] .ag-tile:disabled{ opacity:.25; cursor:default; box-shadow:none; }
    [data-game="anagrams"] .ag-caption{ font-size:12.5px; color:#7a8aa0; margin-top:8px; }
    [data-game="anagrams"] .ag-slots{ display:flex; gap:9px; flex-wrap:wrap; margin-top:10px; }
    [data-game="anagrams"] .ag-slot{ width:48px; height:52px; border-radius:11px; border:1.5px solid #cbd6e6; background:#fff; display:flex; align-items:center; justify-content:center; font-size:23px; font-weight:900; color:var(--ag-ink); cursor:pointer; box-shadow:0 2px 5px rgba(15,37,69,.06); }
    [data-game="anagrams"] .ag-slot.empty{ border:2px dashed #a9bbd4; background:var(--ag-soft); box-shadow:none; cursor:default; }
    [data-game="anagrams"] .ag-slot.bad{ border-color:var(--ag-red); animation:agShake .35s ease; }
    @keyframes agShake{ 25%{ transform:translateX(-3px) } 75%{ transform:translateX(3px) } }
    [data-game="anagrams"] .ag-lenpill{ display:inline-block; border:1.5px solid var(--ag-line); background:var(--ag-soft); color:#586a82; border-radius:9px; padding:4px 12px; font-size:12px; font-weight:800; margin-top:10px; }
    [data-game="anagrams"] .ag-actions{ display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-top:12px; }
    [data-game="anagrams"] .ag-submit{ border:0; cursor:pointer; border-radius:11px; padding:13px 30px; font-size:14px; font-weight:900; letter-spacing:.06em; text-transform:uppercase; color:#fff; background:linear-gradient(120deg,var(--ag-blue),var(--ag-blue-deep)); box-shadow:0 6px 16px rgba(31,95,208,.3); }
    [data-game="anagrams"] .ag-submit:hover:not(:disabled){ filter:brightness(1.06); }
    [data-game="anagrams"] .ag-submit:disabled{ opacity:.55; cursor:default; }
    [data-game="anagrams"] .ag-minor{ border:1.5px solid #c9dcff; background:#fff; color:var(--ag-blue-deep); border-radius:11px; padding:12px 20px; font-size:13px; font-weight:900; letter-spacing:.04em; text-transform:uppercase; cursor:pointer; }
    [data-game="anagrams"] .ag-minor:hover:not(:disabled){ background:var(--ag-blue-soft); }
    [data-game="anagrams"] .ag-minor:disabled{ opacity:.5; cursor:default; }
    [data-game="anagrams"] .ag-attempts{ font-size:13.5px; font-weight:700; color:#586a82; }
    [data-game="anagrams"] .ag-attempts b{ color:var(--ag-ink); }
    [data-game="anagrams"] .ag-hint{ display:flex; align-items:flex-start; gap:10px; background:var(--ag-blue-soft); border:1.5px solid #c9dcff; border-radius:12px; padding:12px 14px; margin-top:12px; font-size:13.5px; color:var(--ag-ink); max-width:520px; }
    [data-game="anagrams"] .ag-hint .ic{ flex:none; width:22px; height:22px; border-radius:50%; background:var(--ag-blue); color:#fff; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:900; font-style:normal; }
    [data-game="anagrams"] .ag-hint b{ color:var(--ag-blue-deep); }

    /* round score sidebar */
    [data-game="anagrams"] .ag-side{ border-left:1.5px solid var(--ag-line); padding:18px; display:flex; flex-direction:column; gap:4px; }
    [data-game="anagrams"] .ag-side-title{ font-size:15px; font-weight:900; letter-spacing:.05em; text-transform:uppercase; color:var(--ag-blue-deep); margin-bottom:8px; }
    [data-game="anagrams"] .ag-srow{ display:flex; align-items:center; justify-content:space-between; gap:10px; padding:9px 0; border-bottom:1.5px solid var(--ag-line); font-size:14px; color:#586a82; font-weight:600; }
    [data-game="anagrams"] .ag-srow b{ font-size:16px; font-weight:900; }
    [data-game="anagrams"] .ag-srow b.plus{ color:var(--ag-green); }
    [data-game="anagrams"] .ag-srow b.blue{ color:var(--ag-blue-deep); }
    [data-game="anagrams"] .ag-ring{ margin:16px auto 4px; position:relative; width:150px; height:150px; }
    [data-game="anagrams"] .ag-ring svg{ width:100%; height:100%; transform:rotate(-90deg); }
    [data-game="anagrams"] .ag-ring circle{ fill:none; stroke-width:14; }
    [data-game="anagrams"] .ag-ring .bg{ stroke:#e3eaf4; }
    [data-game="anagrams"] .ag-ring .fg{ stroke:var(--ag-blue); stroke-linecap:round; transition:stroke-dashoffset .3s ease; }
    [data-game="anagrams"] .ag-ring .pct{ position:absolute; inset:0; display:flex; align-items:center; justify-content:center; font-size:30px; font-weight:900; color:var(--ag-ink); }
    [data-game="anagrams"] .ag-ring .pct small{ font-size:15px; margin-top:6px; }

    /* ============ bottom bar ============ */
    [data-game="anagrams"] .ag-bottom{ display:grid; grid-template-columns:auto minmax(220px,1fr) auto; gap:14px; align-items:center; padding:2px 14px 14px; }
    [data-game="anagrams"] .ag-benches{ display:flex; align-items:center; gap:10px; background:#fff; border:1.5px solid var(--ag-line); border-radius:16px; padding:9px 16px; }
    [data-game="anagrams"] .ag-benchdiv{ width:1.5px; align-self:stretch; background:var(--ag-line); margin:2px 4px; }
    [data-game="anagrams"] .ag-bench{ display:flex; gap:14px; }
    [data-game="anagrams"] .ag-p{ text-align:center; width:62px; }
    [data-game="anagrams"] .ag-p .av{ position:relative; width:48px; height:48px; border-radius:50%; border:2.5px solid var(--ag-blue); background:#dbeafe; margin:0 auto; }
    [data-game="anagrams"] .ag-p.b .av{ border-color:var(--ag-orange); }
    [data-game="anagrams"] .ag-p .av img{ width:100%; height:100%; border-radius:50%; object-fit:cover; display:block; }
    [data-game="anagrams"] .ag-p .av.cpu{ display:flex; align-items:center; justify-content:center; font-size:24px; background:#ffe9d4; }
    [data-game="anagrams"] .ag-p .av .dot{ position:absolute; right:-1px; bottom:-1px; width:12px; height:12px; border-radius:50%; border:2px solid #fff; background:#22c55e; }
    [data-game="anagrams"] .ag-p .av .dot.y{ background:#f59e0b; }
    [data-game="anagrams"] .ag-p .av .dot.bl{ background:var(--ag-blue); }
    [data-game="anagrams"] .ag-p .nm{ font-size:12px; font-weight:800; color:var(--ag-ink); margin-top:4px; white-space:nowrap; }
    [data-game="anagrams"] .ag-p .st{ font-size:10.5px; font-weight:800; color:#7a8aa0; }
    [data-game="anagrams"] .ag-p .st.arr{ color:var(--ag-blue-deep); }
    [data-game="anagrams"] .ag-p .st.th{ color:#b45309; }
    [data-game="anagrams"] .ag-p .st.rd{ color:#0f7a40; }
    [data-game="anagrams"] .ag-feed{ display:flex; align-items:center; gap:12px; background:#fff; border:1.5px solid var(--ag-line); border-radius:16px; padding:10px 18px; min-height:60px; font-size:15px; font-weight:700; color:var(--ag-ink); }
    [data-game="anagrams"] .ag-feed .lt{ flex:none; width:38px; height:38px; border-radius:50%; background:var(--ag-blue); color:#fff; display:flex; align-items:center; justify-content:center; font-size:18px; font-weight:900; }
    [data-game="anagrams"] .ag-feed .pts{ color:var(--ag-blue-deep); font-weight:900; }
    [data-game="anagrams"] .ag-ctl{ display:flex; gap:8px; }
    [data-game="anagrams"] .ag-ctlbtn{ width:62px; height:60px; border-radius:12px; border:1.5px solid var(--ag-line); background:#fff; cursor:pointer; font-size:17px; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:2px; color:var(--ag-ink); }
    [data-game="anagrams"] .ag-ctlbtn span{ font-size:10px; font-weight:800; color:#586a82; }
    [data-game="anagrams"] .ag-ctlbtn:hover{ background:var(--ag-soft); }

    /* ============ overlays ============ */
    [data-game="anagrams"] .ag-overlay{ position:fixed; inset:0; z-index:60; background:rgba(7,22,46,.72); display:flex; align-items:center; justify-content:center; padding:16px; }
    [data-game="anagrams"] .ag-panel{ width:min(94vw,480px); background:#fff; border-radius:18px; padding:26px; text-align:center; box-shadow:0 24px 70px rgba(0,0,0,.4); }
    [data-game="anagrams"] .ag-panel h3{ margin:0 0 6px; font-size:24px; font-weight:900; color:var(--ag-ink); }
    [data-game="anagrams"] .ag-panel p{ margin:4px 0; font-size:14px; color:#586a82; }
    [data-game="anagrams"] .ag-panel .row{ display:flex; gap:10px; justify-content:center; margin:14px 0; flex-wrap:wrap; }
    [data-game="anagrams"] .ag-stat{ min-width:100px; background:var(--ag-soft); border-radius:12px; padding:10px; }
    [data-game="anagrams"] .ag-stat b{ display:block; font-size:22px; color:var(--ag-ink); }
    [data-game="anagrams"] .ag-stat span{ font-size:11px; font-weight:800; color:#7a8aa0; text-transform:uppercase; }
    [data-game="anagrams"] .ag-btn{ border:0; cursor:pointer; border-radius:999px; padding:12px 30px; margin:4px; font-size:14px; font-weight:900; color:#fff; background:linear-gradient(120deg,var(--ag-blue),var(--ag-blue-deep)); }
    [data-game="anagrams"] .ag-btn.alt{ background:#475569; }

    @media (max-width:980px){
        [data-game="anagrams"] .ag-main{ grid-template-columns:1fr; }
        [data-game="anagrams"] .ag-side{ border-left:0; border-top:1.5px solid var(--ag-line); }
        [data-game="anagrams"] .ag-scorerow{ grid-template-columns:1fr 1fr; }
        [data-game="anagrams"] .ag-mid{ grid-column:1 / -1; order:3; }
        [data-game="anagrams"] .ag-bottom{ grid-template-columns:1fr; }
        [data-game="anagrams"] .ag-benches{ overflow-x:auto; }
    }
    @media (prefers-reduced-motion:reduce){ [data-game="anagrams"] *{ animation:none !important; transition:none !important; } }
</style>
@endverbatim

    {{-- ============ CONFIG (shared component) ============ --}}
    <div id="agConfig">
        <x-gamified-configuration
            title="Anagram Challenge"
            subtitle="Unscramble the jumbled letters back into the correct answer — three attempts per word."
            icon="🔀"
            :items="[10, 15, 20]"
            :items-default="10"
            :types="['identification']"
            :difficulty="true"
            :modes="['solo', 'team']"
            mode-default="solo"
            start-label="Start the challenge" />
    </div>

    {{-- ============ GAME ============ --}}
    <div id="agGame" class="ag-stage ag-hidden">
        <div class="ag-top">
            <div class="ag-top-title">Anagram Challenge</div>
            <div id="agTopMid" class="ag-top-mid">Word Showdown</div>
            <div class="ag-top-right">
                <span class="ag-timer">&#9201; <span id="agTimer">--:--</span></span>
                <span class="ag-qno">Question <span id="agQNow">1</span> of <span id="agQTotal">0</span></span>
                <span class="ag-live">Practice Game</span>
            </div>
        </div>

        <div class="ag-scorerow">
            <div id="agCardA" class="ag-teamcard a">
                <div class="ag-tc-main">
                    <div class="ag-tc-head">
                        <div class="ag-tc-trophy">&#127942;</div>
                        <div>
                            <div class="ag-tc-label" id="agALabel">Team Einstein</div>
                            <div class="ag-tc-pts"><span id="agAPts">0</span> <small>pts</small></div>
                        </div>
                    </div>
                    <div class="ag-chips" id="agAChips"></div>
                </div>
                <div class="ag-tc-solved"><b id="agASolved">0</b><span>words solved</span></div>
            </div>

            <div class="ag-mid">
                <div id="agSeg" class="ag-seg"></div>
                <div id="agTurn" class="ag-turnpill">&#8635; <span id="agTurnTxt">&mdash;</span></div>
            </div>

            <div id="agCardB" class="ag-teamcard b">
                <div class="ag-tc-main">
                    <div class="ag-tc-head">
                        <div class="ag-tc-trophy">&#127942;</div>
                        <div>
                            <div class="ag-tc-label" id="agBLabel">Team Newton</div>
                            <div class="ag-tc-pts"><span id="agBPts">0</span> <small>pts</small></div>
                        </div>
                    </div>
                    <div class="ag-chips" id="agBChips"></div>
                </div>
                <div class="ag-tc-solved"><b id="agBSolved">0</b><span>words solved</span></div>
            </div>
        </div>

        <div id="agDeckNote" class="ag-note ag-hidden">Using built-in practice words &mdash; ask a teacher to add Identification questions for your subject to play your own.</div>

        <div class="ag-main">
            <div class="ag-q">
                <div class="ag-q-head">
                    <span class="ag-q-label">Question <span id="agQLabelN">1</span></span>
                    <span class="ag-pill" id="agScopePill">All subjects</span>
                    <span class="ag-pill green" id="agDiffPill">Mixed</span>
                </div>
                <div class="ag-q-text" id="agQText"></div>
                <div class="ag-q-sub">Unscramble the letters to form the correct answer.</div>
                <hr class="ag-hr">
                <div class="ag-secrow">
                    <span class="ag-sec">Scrambled letters</span>
                    <button type="button" id="agShuffle" class="ag-shuffle">&#8635; Shuffle</button>
                </div>
                <div id="agTiles" class="ag-tiles"></div>
                <div class="ag-caption">Click or type the letters to fill the answer slots.</div>
                <hr class="ag-hr">
                <span class="ag-sec">Your answer</span>
                <div id="agSlots" class="ag-slots"></div>
                <span class="ag-lenpill" id="agLenPill">0 letters</span>
                <div class="ag-actions">
                    <button type="button" id="agSubmit" class="ag-submit">Submit answer</button>
                    <button type="button" id="agHint" class="ag-minor">&#128161; Hint (&minus;25)</button>
                    <button type="button" id="agClear" class="ag-minor">Clear</button>
                    <span class="ag-attempts">Attempt <b id="agAttemptN">1</b> of 3</span>
                </div>
                <div id="agHintBox" class="ag-hint ag-hidden"><i class="ic">i</i><div><b>Hint:</b> <span id="agHintTxt"></span></div></div>
            </div>

            <aside class="ag-side">
                <div class="ag-side-title">Round score</div>
                <div class="ag-srow"><span>Time bonus</span><b class="plus" id="agTimeBonus">+80</b></div>
                <div class="ag-srow"><span>No hints used</span><b class="plus" id="agNoHint">+25</b></div>
                <div class="ag-srow"><span>Current streak</span><b class="blue" id="agStreak">0</b></div>
                <div class="ag-ring">
                    <svg viewBox="0 0 120 120">
                        <circle class="bg" cx="60" cy="60" r="52"/>
                        <circle class="fg" id="agRingFg" cx="60" cy="60" r="52"/>
                    </svg>
                    <div class="pct"><span id="agRingPct">0</span><small>%</small></div>
                </div>
            </aside>
        </div>

        <div class="ag-bottom">
            <div class="ag-benches">
                <div id="agBenchA" class="ag-bench a"></div>
                <div class="ag-benchdiv"></div>
                <div id="agBenchB" class="ag-bench b"></div>
            </div>
            <div class="ag-feed"><span class="lt" id="agFeedLt">?</span><span id="agFeed">Place your first letter!</span></div>
            <div class="ag-ctl">
                <button type="button" id="agPause" class="ag-ctlbtn" aria-label="Pause">&#10073;&#10073;<span>Pause</span></button>
                <button type="button" id="agSound" class="ag-ctlbtn" aria-label="Toggle sound">&#128266;<span>Sound</span></button>
                <button type="button" id="agSettings" class="ag-ctlbtn" aria-label="Settings">&#9881;<span>Settings</span></button>
            </div>
        </div>
    </div>

    {{-- pause / end overlays --}}
    <div id="agPauseOv" class="ag-overlay ag-hidden">
        <div class="ag-panel">
            <h3>Game paused</h3>
            <p>The timer is stopped. Take a breath!</p>
            <div style="margin-top:14px">
                <button type="button" id="agResume" class="ag-btn">Resume</button>
                <button type="button" id="agQuit" class="ag-btn alt">Exit game</button>
            </div>
        </div>
    </div>
    <div id="agEndOv" class="ag-overlay ag-hidden"><div class="ag-panel" id="agEndPanel"></div></div>
</div>

<script>
(function () {
    'use strict';
    const ENDPOINT = @json(route('tools.games.questions'));
    const CATALOG = @json(route('tools.games.index'));
    const el = (id) => document.getElementById(id);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));
    const MAX_ATTEMPTS = 3;
    const RING_C = 2 * Math.PI * 52;

    if (window.self !== window.top) document.querySelector('[data-game="anagrams"]').classList.add('ag-embedded');

    // ---------------- cartoon avatars (placeholder until real student photos) --
    // Inline SVG data-URIs — varied skin tones, hair colours, and hairstyles so
    // the bench looks like the key art without shipping image assets.
    function avatarUri(p) {
        const hairBack = {
            long:  '<path d="M14 30 Q14 8 32 8 Q50 8 50 30 L50 46 Q44 40 42 30 L22 30 Q20 40 14 46 Z" fill="' + p.hair + '"/>',
            curly: '<circle cx="20" cy="20" r="8" fill="' + p.hair + '"/><circle cx="32" cy="14" r="9" fill="' + p.hair + '"/><circle cx="44" cy="20" r="8" fill="' + p.hair + '"/>',
            buns:  '<circle cx="14" cy="16" r="7" fill="' + p.hair + '"/><circle cx="50" cy="16" r="7" fill="' + p.hair + '"/><path d="M16 26 Q16 10 32 10 Q48 10 48 26 Z" fill="' + p.hair + '"/>',
            short: '<path d="M16 26 Q16 8 32 8 Q48 8 48 26 Z" fill="' + p.hair + '"/>',
        }[p.style] || '';
        const fringe = '<path d="M19 26 Q22 15 32 15 Q42 15 45 26 Q38 21 32 22 Q26 21 19 26 Z" fill="' + p.hair + '"/>';
        const svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">'
            + '<rect width="64" height="64" fill="' + p.bg + '"/>'
            + hairBack
            + '<circle cx="32" cy="30" r="14" fill="' + p.skin + '"/>'
            + fringe
            + '<circle cx="27" cy="30" r="1.8" fill="#243247"/><circle cx="37" cy="30" r="1.8" fill="#243247"/>'
            + '<path d="M27 36 Q32 40 37 36" stroke="#243247" stroke-width="1.8" fill="none" stroke-linecap="round"/>'
            + '<path d="M12 64 Q32 46 52 64 Z" fill="' + p.shirt + '"/>'
            + '</svg>';
        return 'data:image/svg+xml;utf8,' + encodeURIComponent(svg);
    }
    const LOOKS = [
        { skin:'#f6cfae', hair:'#4a2f1d', style:'long',  bg:'#dbeafe', shirt:'#2563eb' },
        { skin:'#eab890', hair:'#2b1c10', style:'curly', bg:'#dbeafe', shirt:'#0ea5e9' },
        { skin:'#c68a5a', hair:'#1d1208', style:'buns',  bg:'#dbeafe', shirt:'#4f46e5' },
        { skin:'#f3c39d', hair:'#8a5a2b', style:'short', bg:'#dbeafe', shirt:'#0891b2' },
        { skin:'#eab890', hair:'#3c2513', style:'long',  bg:'#ffe9d4', shirt:'#ea580c' },
        { skin:'#8d5a3b', hair:'#150d05', style:'curly', bg:'#ffe9d4', shirt:'#d97706' },
        { skin:'#f6cfae', hair:'#a5682a', style:'buns',  bg:'#ffe9d4', shirt:'#dc2626' },
        { skin:'#c68a5a', hair:'#241505', style:'short', bg:'#ffe9d4', shirt:'#b45309' },
    ];

    // ---------------- audio (generated blips, mute persisted) ----------------
    let actx = null, muted = false;
    try { muted = localStorage.getItem('ag_muted') === '1'; } catch (e) {}
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
        place() { tone(520, .07, 'triangle', .05); },
        ok() { tone(660, .12, 'triangle'); tone(880, .16, 'triangle', .07, .09); },
        bad() { tone(190, .25, 'sawtooth', .05); },
        win() { [523, 659, 784].forEach((f, i) => tone(f, .18, 'triangle', .07, i * .1)); },
        end() { [523, 659, 784, 1047].forEach((f, i) => tone(f, .2, 'triangle', .07, i * .12)); },
    };
    function paintSound() { el('agSound').innerHTML = (muted ? '\u{1F507}' : '\u{1F50A}') + '<span>Sound</span>'; }
    paintSound();
    el('agSound').addEventListener('click', () => {
        muted = !muted;
        try { localStorage.setItem('ag_muted', muted ? '1' : '0'); } catch (e) {}
        paintSound();
    });

    // ---------------- fallback deck (bank empty for this scope) --------------
    const FALLBACK = [
        { question: 'The process by which liquid water changes into water vapor', answer: 'EVAPORATION', explanation: 'It is part of the water cycle.' },
        { question: 'The process plants use to make food using sunlight', answer: 'PHOTOSYNTHESIS', explanation: 'It happens in the leaves.' },
        { question: 'The force that pulls things toward the ground', answer: 'GRAVITY' },
        { question: 'The planet we live on', answer: 'EARTH' },
        { question: "Earth's natural satellite", answer: 'MOON' },
        { question: 'The largest land animal', answer: 'ELEPHANT' },
        { question: 'A place where you go to learn', answer: 'SCHOOL' },
        { question: 'A large body of salt water', answer: 'OCEAN' },
        { question: 'Water falling from clouds', answer: 'RAIN' },
        { question: 'The organ that pumps blood', answer: 'HEART' },
        { question: 'The red planet', answer: 'MARS' },
        { question: 'The season after winter', answer: 'SPRING' },
        { question: 'Frozen water', answer: 'ICE' },
        { question: 'The colour of a clear daytime sky', answer: 'BLUE' },
        { question: 'You read these to learn new things', answer: 'BOOK' },
        { question: 'Animals without a backbone', answer: 'INVERTEBRATES' },
        { question: 'The path a planet takes around the sun', answer: 'ORBIT' },
        { question: 'The gas humans need to breathe', answer: 'OXYGEN' },
        { question: 'The smallest unit of matter', answer: 'ATOM' },
        { question: 'The centre of a cell', answer: 'NUCLEUS' },
    ];

    // ---------------- state ----------------
    let questions = [], mode = 'solo';
    let roundIdx = 0, turn = 'A';
    let answer = '', letters = [];   // letters = answer stripped to A–Z0–9
    let tiles = [];                  // [{ch, used}]
    let slots = [];                  // per letter position: tile index | null
    let attempts = 0, hintsUsed = false, roundStart = 0, roundOver = false;
    let over = false, paused = false, active = false;
    let scores = { A: 0, B: 0 }, solved = { A: 0, B: 0 }, streak = { A: 0, B: 0 };
    let segResult = [];              // per round: 'A' | 'B' | 'x'
    let seat = { A: 0, B: 0 };
    let timerLeft = 0, timerH = null, botH = null, nextH = null;
    let botPlan = null;

    const TEAM_NAMES = { A: 'Team Einstein', B: 'Team Newton' };
    let roster = { A: [], B: [] };

    function buildRoster() {
        if (mode === 'solo') {
            roster.A = [{ n: 'You', img: avatarUri(LOOKS[0]) }];
            roster.B = [{ n: 'Rival Bot', cpu: true }];
        } else {
            roster.A = ['Mia', 'Leo', 'Zoe', 'Noah'].map((n, i) => ({ n: n, img: avatarUri(LOOKS[i]) }));
            roster.B = ['Ava', 'Ethan', 'Lily', 'Lucas'].map((n, i) => ({ n: n, img: avatarUri(LOOKS[4 + i]) }));
        }
    }
    const teamLabel = (t) => mode === 'solo' ? (t === 'A' ? 'Your Team' : 'Rival Bot') : TEAM_NAMES[t];
    const turnText = (t) => mode === 'solo'
        ? (t === 'A' ? 'Your Turn' : "Rival Bot's Turn")
        : (t === 'A' ? "Einstein's Turn" : "Newton's Turn");
    const isBotTurn = () => mode === 'solo' && turn === 'B';
    const guesserName = () => roster[turn][seat[turn] % roster[turn].length].n;

    // ---------------- start ----------------
    async function start(cfg) {
        window.GamifiedConfig.clearError();
        window.GamifiedConfig.loading(true, 'Loading…');
        mode = cfg.mode || 'solo';
        const limit = cfg.items || 10;
        let usedFallback = false;
        try {
            const params = new URLSearchParams({ type: 'identification', limit: String(limit) });
            if (cfg.difficulty) params.set('difficulty', cfg.difficulty);
            const scope = window.GameScope || {};
            ['subject_id', 'topic_id', 'lesson_id', 'competency_id', 'academic_level_id'].forEach(k => {
                if (scope[k]) params.set(k, scope[k]);
            });
            const res = await fetch(ENDPOINT + '?' + params.toString(), { headers: { 'Accept': 'application/json' } });
            if (!res.ok) throw new Error('Request failed (' + res.status + ')');
            const data = await res.json();
            questions = (data.questions || []).filter(q => String(q.answer).replace(/[^a-z0-9]/gi, '').length >= 3);
            if (questions.length === 0) { questions = pickFallback(limit); usedFallback = true; }
        } catch (e) {
            questions = pickFallback(limit);
            usedFallback = true;
        } finally {
            window.GamifiedConfig.loading(false);
        }

        el('agDeckNote').classList.toggle('ag-hidden', !usedFallback);
        buildRoster();
        roundIdx = 0; scores = { A: 0, B: 0 }; solved = { A: 0, B: 0 }; streak = { A: 0, B: 0 };
        segResult = new Array(questions.length).fill(null);
        seat = { A: 0, B: 0 }; over = false; paused = false;

        const scopeTxt = (window.GameScope && window.GameScope.summary) ? window.GameScope.summary() : 'All subjects';
        el('agScopePill').textContent = scopeTxt;
        el('agDiffPill').textContent = cfg.difficulty ? cfg.difficulty.charAt(0).toUpperCase() + cfg.difficulty.slice(1) : 'Mixed';
        el('agTopMid').textContent = questions.length + '-Question '
            + (scopeTxt && scopeTxt !== 'All subjects' ? scopeTxt + ' ' : '') + 'Showdown';
        el('agQTotal').textContent = questions.length;
        el('agALabel').textContent = teamLabel('A');
        el('agBLabel').textContent = teamLabel('B');
        renderChips();
        feed('?', 'First to unscramble takes the round — good luck!');

        timerLeft = questions.length * 45;
        clearInterval(timerH);
        timerH = setInterval(tick, 1000);
        paintTimer();

        el('agConfig').classList.add('ag-hidden');
        el('agGame').classList.remove('ag-hidden');
        active = true;
        startRound();
    }

    function pickFallback(limit) {
        const pool = FALLBACK.slice();
        for (let i = pool.length - 1; i > 0; i--) { const j = Math.floor(Math.random() * (i + 1)); [pool[i], pool[j]] = [pool[j], pool[i]]; }
        return pool.slice(0, Math.min(parseInt(limit, 10) || 10, pool.length));
    }

    // ---------------- timer ----------------
    function tick() {
        if (paused || over) return;
        timerLeft--;
        paintTimer();
        if (!roundOver) renderScorePanel(); // live time-bonus countdown
        if (timerLeft <= 0) endGame(true);
    }
    function paintTimer() {
        const t = Math.max(0, timerLeft);
        el('agTimer').textContent = String(Math.floor(t / 60)).padStart(2, '0') + ':' + String(t % 60).padStart(2, '0');
    }

    // ---------------- rounds ----------------
    function scramble(src) {
        for (let tries = 0; tries < 10; tries++) {
            const arr = src.slice();
            for (let i = arr.length - 1; i > 0; i--) { const j = Math.floor(Math.random() * (i + 1)); [arr[i], arr[j]] = [arr[j], arr[i]]; }
            if (arr.join('') !== src.join('')) return arr;
        }
        return src.slice().reverse();
    }

    function startRound() {
        const q = questions[roundIdx];
        answer = String(q.answer).toUpperCase();
        letters = answer.replace(/[^A-Z0-9]/g, '').split('');
        tiles = scramble(letters).map(ch => ({ ch: ch, used: false }));
        slots = new Array(letters.length).fill(null);
        attempts = 0; hintsUsed = false; roundOver = false;
        roundStart = timerLeft;
        turn = roundIdx % 2 === 0 ? 'A' : 'B';
        botPlan = null;

        el('agQNow').textContent = roundIdx + 1;
        el('agQLabelN').textContent = roundIdx + 1;
        el('agQText').textContent = q.question;
        el('agLenPill').textContent = letters.length + (letters.length === 1 ? ' letter' : ' letters');
        el('agAttemptN').textContent = '1';
        el('agHintBox').classList.add('ag-hidden');
        renderAll();

        clearInterval(botH);
        if (isBotTurn()) {
            botPlan = { i: 0, willWin: Math.random() < 0.75 };
            botH = setInterval(botStep, 1050);
        }
    }

    function nextRound() {
        roundIdx++;
        if (roundIdx >= questions.length) return endGame(false);
        startRound();
    }
    function scheduleNext(ms) {
        clearTimeout(nextH);
        nextH = setTimeout(() => { if (!over) nextRound(); }, ms);
    }

    // ---------------- placing letters ----------------
    function placeTile(ti) {
        if (!active || roundOver || paused || over) return;
        if (tiles[ti].used) return;
        const si = slots.indexOf(null);
        if (si < 0) return;
        tiles[ti].used = true;
        slots[si] = ti;
        sfx.place();
        feed(tiles[ti].ch, '<b>' + esc(guesserName()) + '</b> placed the letter ' + esc(tiles[ti].ch));
        renderBoard();
        renderScorePanel();
    }
    function removeSlot(si) {
        if (!active || roundOver || paused || over || isBotTurn()) return;
        const ti = slots[si];
        if (ti === null) return;
        tiles[ti].used = false;
        slots[si] = null;
        renderBoard();
        renderScorePanel();
    }
    function clearSlots() {
        if (!active || roundOver || paused || over || isBotTurn()) return;
        slots.forEach(ti => { if (ti !== null) tiles[ti].used = false; });
        slots = slots.map(() => null);
        renderBoard();
        renderScorePanel();
    }
    function shuffleTiles() {
        if (!active || roundOver || paused || over || isBotTurn()) return;
        const order = scramble(tiles.map((_, i) => i));
        const remap = new Array(tiles.length);
        tiles = order.map((oldIdx, newIdx) => { remap[oldIdx] = newIdx; return tiles[oldIdx]; });
        slots = slots.map(ti => ti === null ? null : remap[ti]);
        renderBoard();
    }

    const currentWord = () => slots.map(ti => ti === null ? '' : tiles[ti].ch).join('');
    const timeBonus = () => Math.max(0, 80 - (roundStart - timerLeft) * 4);

    function submit() {
        if (!active || roundOver || paused || over) return;
        if (slots.indexOf(null) >= 0) {
            shakeSlots();
            return;
        }
        if (currentWord() === letters.join('')) return winRound();
        attempts++;
        sfx.bad();
        shakeSlots();
        if (attempts >= MAX_ATTEMPTS) return loseRound();
        el('agAttemptN').textContent = String(attempts + 1);
        feed('✕', '<b>' + esc(teamLabel(turn)) + '</b> submitted a wrong arrangement — ' + (MAX_ATTEMPTS - attempts) + ' left');
    }
    function shakeSlots() {
        document.querySelectorAll('#agSlots .ag-slot').forEach(s => {
            s.classList.remove('bad'); void s.offsetWidth; s.classList.add('bad');
        });
    }

    function hint() {
        if (!active || roundOver || paused || over || isBotTurn()) return;
        // fix the first wrong/empty position with the correct letter
        let i = 0;
        while (i < letters.length && slots[i] !== null && tiles[slots[i]].ch === letters[i]) i++;
        if (i >= letters.length) return;
        for (let k = i; k < letters.length; k++) { if (slots[k] !== null) { tiles[slots[k]].used = false; slots[k] = null; } }
        const ti = tiles.findIndex(t => !t.used && t.ch === letters[i]);
        if (ti < 0) return;
        tiles[ti].used = true;
        slots[i] = ti;
        hintsUsed = true;
        scores[turn] = Math.max(0, scores[turn] - 25);
        const q = questions[roundIdx];
        if (q.explanation) {
            el('agHintTxt').textContent = q.explanation;
            el('agHintBox').classList.remove('ag-hidden');
        }
        feed(letters[i], '\u{1F4A1} <b>' + esc(teamLabel(turn)) + '</b> used a hint: letter ' + esc(letters[i]) + ' <span class="pts">−25 pts</span>');
        if (slots.indexOf(null) < 0 && currentWord() === letters.join('')) return winRound();
        renderBoard();
        renderScore();
        renderScorePanel();
    }

    function winRound() {
        roundOver = true;
        clearInterval(botH);
        streak[turn]++;
        const gain = 100 + timeBonus() + (hintsUsed ? 0 : 25) + Math.min((streak[turn] - 1) * 10, 50);
        scores[turn] += gain;
        solved[turn]++;
        segResult[roundIdx] = turn;
        streak[turn === 'A' ? 'B' : 'A'] = 0;
        sfx.win();
        feed('★', '✨ <b>' + esc(teamLabel(turn)) + '</b> solved <b>' + esc(answer) + '</b> <span class="pts">+' + gain + ' pts</span>');
        renderAll();
        scheduleNext(1800);
    }

    function loseRound() {
        roundOver = true;
        clearInterval(botH);
        segResult[roundIdx] = 'x';
        streak[turn] = 0;
        feed('✕', 'Out of attempts — the word was <b>' + esc(answer) + '</b>');
        // reveal the correct arrangement in the slots
        tiles.forEach(t => { t.used = false; });
        slots = letters.map(ch => { const ti = tiles.findIndex(t => !t.used && t.ch === ch); tiles[ti].used = true; return ti; });
        renderAll();
        scheduleNext(2200);
    }

    // rival bot (solo mode): places correct letters at a visible pace
    function botStep() {
        if (paused || over || roundOver || !isBotTurn() || !botPlan) return;
        if (botPlan.i < letters.length) {
            const want = botPlan.willWin || botPlan.i !== 1 ? letters[botPlan.i]
                : letters[Math.min(letters.length - 1, 2)]; // planted mistake on a losing run
            let ti = tiles.findIndex(t => !t.used && t.ch === want);
            if (ti < 0) ti = tiles.findIndex(t => !t.used);
            tiles[ti].used = true;
            slots[botPlan.i] = ti;
            botPlan.i++;
            sfx.place();
            feed(tiles[ti].ch, '<b>Rival Bot</b> placed the letter ' + esc(tiles[ti].ch));
            renderBoard();
            renderScorePanel();
            return;
        }
        if (currentWord() === letters.join('')) return winRound();
        attempts++;
        sfx.bad();
        shakeSlots();
        if (attempts >= MAX_ATTEMPTS) return loseRound();
        el('agAttemptN').textContent = String(attempts + 1);
        feed('✕', '<b>Rival Bot</b> submitted a wrong arrangement');
    }

    // ---------------- rendering ----------------
    function renderAll() {
        renderBoard();
        renderScore();
        renderSeg();
        renderTurn();
        renderBenches();
        renderScorePanel();
    }

    function renderBoard() {
        const locked = roundOver || isBotTurn() || paused || over;
        const tbox = el('agTiles');
        tbox.innerHTML = tiles.map((t, i) =>
            '<button type="button" class="ag-tile" data-i="' + i + '" ' + ((t.used || locked) ? 'disabled' : '') + '>' + esc(t.ch) + '</button>'
        ).join('');
        // sticky-hover guard: a rebuilt tile under the resting pointer must not light up
        tbox.classList.add('ag-nohover');
        tbox.querySelectorAll('.ag-tile').forEach(b => b.addEventListener('click', () => placeTile(parseInt(b.dataset.i, 10))));

        el('agSlots').innerHTML = slots.map((ti, si) =>
            ti === null
                ? '<span class="ag-slot empty" data-s="' + si + '"></span>'
                : '<button type="button" class="ag-slot" data-s="' + si + '">' + esc(tiles[ti].ch) + '</button>'
        ).join('');
        el('agSlots').querySelectorAll('button.ag-slot').forEach(b => b.addEventListener('click', () => removeSlot(parseInt(b.dataset.s, 10))));

        el('agSubmit').disabled = locked || slots.indexOf(null) >= 0;
        el('agHint').disabled = locked;
        el('agClear').disabled = locked;
        el('agShuffle').disabled = locked;
    }
    document.addEventListener('mousemove', () => el('agTiles').classList.remove('ag-nohover'));

    function renderScore() {
        el('agAPts').textContent = scores.A; el('agBPts').textContent = scores.B;
        el('agASolved').textContent = solved.A; el('agBSolved').textContent = solved.B;
    }

    function renderSeg() {
        el('agSeg').innerHTML = questions.map((_, i) => {
            let cls = segResult[i] === 'A' ? 'a' : segResult[i] === 'B' ? 'b' : segResult[i] === 'x' ? 'x' : '';
            if (i === roundIdx && !over) cls += ' cur';
            return '<span class="s ' + cls.trim() + '"><i></i><em>' + (i + 1) + '</em></span>';
        }).join('');
    }

    function renderTurn() {
        el('agTurnTxt').textContent = turnText(turn);
        el('agTurn').classList.toggle('b', turn === 'B');
    }

    function renderChips() {
        ['A', 'B'].forEach(t => {
            el('ag' + t + 'Chips').innerHTML = roster[t].map(p => p.cpu
                ? '<span class="ag-chip cpu">\u{1F916}</span>'
                : '<span class="ag-chip"><img src="' + p.img + '" alt="' + esc(p.n) + '"></span>').join('');
        });
    }

    function renderBenches() {
        ['A', 'B'].forEach(t => {
            const activeTeam = t === turn && !roundOver && !over;
            el('agBench' + t).innerHTML = roster[t].map((p, i) => {
                let st = '<span class="st">Online</span>', dot = '';
                if (activeTeam) {
                    if (i === seat[t] % roster[t].length) { st = '<span class="st arr">Arranging</span>'; dot = ' bl'; }
                    else if (i === (seat[t] + 1) % roster[t].length && roster[t].length > 1) { st = '<span class="st th">Thinking</span>'; dot = ' y'; }
                    else { st = '<span class="st rd">Ready</span>'; }
                }
                const av = p.cpu
                    ? '<div class="av cpu">\u{1F916}<span class="dot' + dot + '"></span></div>'
                    : '<div class="av"><img src="' + p.img + '" alt=""><span class="dot' + dot + '"></span></div>';
                return '<div class="ag-p ' + (t === 'B' ? 'b' : 'a') + '">' + av + '<div class="nm">' + esc(p.n) + '</div>' + st + '</div>';
            }).join('');
        });
    }

    function renderScorePanel() {
        el('agTimeBonus').textContent = '+' + (roundOver ? 0 : timeBonus());
        el('agNoHint').textContent = hintsUsed ? '+0' : '+25';
        el('agStreak').textContent = streak[turn];
        const filled = slots.filter(s => s !== null).length;
        const pct = letters.length ? Math.round(filled / letters.length * 100) : 0;
        el('agRingPct').textContent = pct;
        const fg = el('agRingFg');
        fg.style.strokeDasharray = String(RING_C);
        fg.style.strokeDashoffset = String(RING_C * (1 - pct / 100));
    }

    // ---------------- feed ----------------
    function feed(letter, html) {
        el('agFeedLt').textContent = String(letter).slice(0, 1);
        el('agFeed').innerHTML = html;
    }

    // ---------------- pause / end ----------------
    function setPaused(on) {
        paused = on;
        el('agPauseOv').classList.toggle('ag-hidden', !on);
        if (!on && isBotTurn() && !roundOver) { clearInterval(botH); botH = setInterval(botStep, 1050); }
        renderBoard();
    }

    function endGame(timeUp) {
        if (over) return;
        over = true; active = false;
        clearInterval(timerH); clearInterval(botH); clearTimeout(nextH);
        sfx.end();
        const a = scores.A, b = scores.B;
        const winner = a === b ? null : (a > b ? 'A' : 'B');
        const headline = winner === null
            ? "It's a tie!"
            : (mode === 'solo'
                ? (winner === 'A' ? '\u{1F3C6} You win!' : '\u{1F916} Rival Bot wins!')
                : '\u{1F3C6} ' + teamLabel(winner) + ' wins!');
        el('agEndPanel').innerHTML =
            '<h3>' + esc(headline) + '</h3>'
            + '<p>' + (timeUp ? "Time's up! Final tally:" : 'All ' + questions.length + ' questions played. Final tally:') + '</p>'
            + '<div class="row">'
            +   '<div class="ag-stat"><b>' + a + '</b><span>' + esc(teamLabel('A')) + '</span></div>'
            +   '<div class="ag-stat"><b>' + b + '</b><span>' + esc(teamLabel('B')) + '</span></div>'
            +   '<div class="ag-stat"><b>' + solved.A + ' – ' + solved.B + '</b><span>Words solved</span></div>'
            + '</div>'
            + '<p style="font-size:12px">Practice game — nothing here is graded.</p>'
            + '<div><button type="button" id="agAgain" class="ag-btn">Play again</button>'
            + '<button type="button" id="agExit" class="ag-btn alt">Exit</button></div>';
        el('agEndOv').classList.remove('ag-hidden');
        el('agAgain').addEventListener('click', () => window.location.reload());
        el('agExit').addEventListener('click', exitGame);
        renderAll();
    }

    function exitGame() {
        if (window.parent !== window) window.parent.postMessage({ type: 'schoollms:game-exit' }, '*');
        else window.location.href = CATALOG;
    }

    // ---------------- wiring ----------------
    document.addEventListener('gamified-config:start', (e) => start(e.detail));
    el('agSubmit').addEventListener('click', submit);
    el('agHint').addEventListener('click', hint);
    el('agClear').addEventListener('click', clearSlots);
    el('agShuffle').addEventListener('click', shuffleTiles);
    el('agPause').addEventListener('click', () => setPaused(true));
    el('agSettings').addEventListener('click', () => setPaused(true));
    el('agResume').addEventListener('click', () => setPaused(false));
    el('agQuit').addEventListener('click', exitGame);

    document.addEventListener('keydown', (e) => {
        if (!active || roundOver || paused || over || isBotTurn()) return;
        if (e.key === 'Enter') return submit();
        if (e.key === 'Backspace') {
            for (let i = slots.length - 1; i >= 0; i--) { if (slots[i] !== null) { removeSlot(i); break; } }
            return;
        }
        const l = e.key.toUpperCase();
        if (!/^[A-Z0-9]$/.test(l)) return;
        const ti = tiles.findIndex(t => !t.used && t.ch === l);
        if (ti >= 0) placeTile(ti);
    });
})();
</script>
</div>
