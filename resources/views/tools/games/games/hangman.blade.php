{{-- Hangman Challenge — Identification words from the bank, played as a
     team-vs-team letter showdown.

     Question supply: /tools/games/api/questions?type=identification (school-
     scoped, GameScope ☰ filters); the identification answer is the word, its
     question is the clue. Falls back to a small built-in practice deck when
     the bank has none, so the game is always playable. Client-graded practice,
     like every catalog game.

     Pre-game setup is the shared <x-gamified-configuration> component
     (Constitution §11B): items / difficulty / mode live there, the game only
     listens for 'gamified-config:start'.

     Layout mirrors the approved key art: navy top bar (title • showdown name
     • timer • question counter • live badge), team scorecards flanking a
     segmented question bar with the turn pill, gallows + mistake meter +
     wrong-guess tray on the left, clue + word boxes + A–Z letter grid +
     GUESS WORD on the right, and a bottom bar with player benches, a guess
     feed, and pause / sound / settings. Player photos are cartoon SVG
     avatars generated in-page — placeholders until real student profile
     images are on file. Styling is scoped hm-* (stale-build rule). --}}

<div data-game="hangman" class="hm-root">

@verbatim
<style>
    [data-game="hangman"]{
        --hm-blue:#1f5fd0; --hm-blue-deep:#0f3aa0; --hm-blue-soft:#e8f0ff;
        --hm-orange:#e56a1c; --hm-orange-deep:#c04f0d; --hm-orange-soft:#fff0e6;
        --hm-navy:#0f2545; --hm-ink:#1f2a3a; --hm-line:#dfe6f0; --hm-soft:#f4f7fc;
        --hm-green:#16a34a; --hm-red:#dc2626; --hm-red-soft:#fdecef; --hm-gold:#f5b301;
    }
    [data-game="hangman"] *{ box-sizing:border-box; }
    [data-game="hangman"] .hm-hidden{ display:none !important; }
    [data-game="hangman"].hm-embedded .hm-stage{ position:relative; left:50%; transform:translateX(-50%); width:min(1500px,96vw); }

    [data-game="hangman"] .hm-stage{ background:#eef2f8; border:1px solid var(--hm-line); border-radius:16px; overflow:hidden; box-shadow:0 10px 30px rgba(15,37,69,.08); }

    /* ============ top bar ============ */
    [data-game="hangman"] .hm-top{ display:flex; align-items:center; justify-content:space-between; gap:14px; background:var(--hm-navy); color:#fff; padding:13px 18px; flex-wrap:wrap; }
    [data-game="hangman"] .hm-top-title{ font-size:17px; font-weight:900; letter-spacing:.05em; text-transform:uppercase; }
    [data-game="hangman"] .hm-top-mid{ font-size:15px; font-weight:800; flex:1; text-align:center; min-width:180px; }
    [data-game="hangman"] .hm-top-right{ display:flex; align-items:center; gap:12px; font-weight:800; font-size:14px; }
    [data-game="hangman"] .hm-timer{ display:inline-flex; align-items:center; gap:6px; font-variant-numeric:tabular-nums; }
    [data-game="hangman"] .hm-qno{ border-left:1px solid rgba(255,255,255,.25); padding-left:12px; font-size:13px; }
    [data-game="hangman"] .hm-live{ display:inline-flex; align-items:center; gap:7px; background:#123d2a; border:1px solid #2e7d54; border-radius:999px; padding:6px 13px; font-size:12px; font-weight:800; }
    [data-game="hangman"] .hm-live::before{ content:''; width:8px; height:8px; border-radius:50%; background:#34d17b; box-shadow:0 0 6px #34d17b; }

    /* ============ score row ============ */
    [data-game="hangman"] .hm-scorerow{ display:grid; grid-template-columns:minmax(250px,1fr) minmax(230px,1.15fr) minmax(250px,1fr); gap:12px; align-items:stretch; padding:12px 14px 4px; }
    [data-game="hangman"] .hm-teamcard{ display:flex; align-items:center; gap:12px; border-radius:14px; padding:10px 14px; background:#fff; border:2px solid var(--hm-blue); box-shadow:0 6px 16px rgba(15,37,69,.08); }
    [data-game="hangman"] .hm-teamcard.b{ border-color:var(--hm-orange); flex-direction:row-reverse; text-align:right; }
    [data-game="hangman"] .hm-teamcard.turn{ box-shadow:0 0 0 3px rgba(31,95,208,.25), 0 6px 16px rgba(15,37,69,.08); }
    [data-game="hangman"] .hm-teamcard.b.turn{ box-shadow:0 0 0 3px rgba(229,106,28,.3), 0 6px 16px rgba(15,37,69,.08); }
    [data-game="hangman"] .hm-badge{ width:52px; height:52px; flex:none; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:26px; color:#fff; background:linear-gradient(150deg,var(--hm-blue),var(--hm-blue-deep)); }
    [data-game="hangman"] .hm-teamcard.b .hm-badge{ background:linear-gradient(150deg,var(--hm-orange),var(--hm-orange-deep)); }
    [data-game="hangman"] .hm-tc-label{ font-size:12.5px; font-weight:900; letter-spacing:.05em; text-transform:uppercase; color:var(--hm-blue-deep); white-space:nowrap; }
    [data-game="hangman"] .hm-teamcard.b .hm-tc-label{ color:var(--hm-orange-deep); }
    [data-game="hangman"] .hm-tc-pts{ font-size:25px; font-weight:900; color:var(--hm-ink); line-height:1.05; }
    [data-game="hangman"] .hm-tc-pts small{ font-size:13px; font-weight:800; }
    [data-game="hangman"] .hm-chips{ display:flex; margin-left:auto; }
    [data-game="hangman"] .hm-teamcard.b .hm-chips{ margin-left:0; margin-right:auto; }
    [data-game="hangman"] .hm-chip{ width:34px; height:34px; border-radius:50%; overflow:hidden; border:2.5px solid var(--hm-blue); background:#dbeafe; margin-left:-8px; }
    [data-game="hangman"] .hm-chip:first-child{ margin-left:0; }
    [data-game="hangman"] .hm-teamcard.b .hm-chip{ border-color:var(--hm-orange); }
    [data-game="hangman"] .hm-chip img{ width:100%; height:100%; object-fit:cover; display:block; }
    [data-game="hangman"] .hm-chip.cpu{ display:flex; align-items:center; justify-content:center; font-size:17px; background:#ffe9d4; }
    [data-game="hangman"] .hm-tc-rounds{ flex:none; text-align:center; line-height:1.15; }
    [data-game="hangman"] .hm-tc-rounds b{ display:block; font-size:19px; font-weight:900; color:var(--hm-ink); }
    [data-game="hangman"] .hm-tc-rounds span{ font-size:10px; font-weight:800; color:#7a8aa0; text-transform:none; display:block; max-width:52px; }

    [data-game="hangman"] .hm-mid{ display:flex; flex-direction:column; align-items:center; justify-content:center; gap:6px; }
    [data-game="hangman"] .hm-seg{ display:flex; gap:4px; flex-wrap:wrap; justify-content:center; }
    [data-game="hangman"] .hm-seg i{ width:16px; height:20px; border-radius:4px; background:#fff; border:1.5px solid var(--hm-line); }
    [data-game="hangman"] .hm-seg i.a{ background:var(--hm-blue); border-color:var(--hm-blue-deep); }
    [data-game="hangman"] .hm-seg i.b{ background:var(--hm-orange); border-color:var(--hm-orange-deep); }
    [data-game="hangman"] .hm-seg i.x{ background:#cbd5e1; border-color:#94a3b8; }
    [data-game="hangman"] .hm-seg i.cur{ outline:2px solid var(--hm-gold); outline-offset:1px; }
    [data-game="hangman"] .hm-seglabels{ width:100%; display:flex; justify-content:space-between; font-size:11px; font-weight:800; color:#7a8aa0; padding:0 2px; }
    [data-game="hangman"] .hm-turnpill{ border-radius:999px; padding:6px 18px; font-size:13px; font-weight:900; color:#fff; background:var(--hm-blue); box-shadow:0 4px 10px rgba(31,95,208,.35); }
    [data-game="hangman"] .hm-turnpill.b{ background:var(--hm-orange); box-shadow:0 4px 10px rgba(229,106,28,.35); }

    [data-game="hangman"] .hm-note{ margin:8px 14px 0; border:1px solid #f6d98a; background:#fdf6e3; color:#8a6d1b; border-radius:10px; padding:8px 12px; font-size:12.5px; font-weight:600; }

    /* ============ main ============ */
    [data-game="hangman"] .hm-main{ display:grid; grid-template-columns:minmax(260px,340px) minmax(0,1fr); gap:12px; padding:8px 14px 12px; align-items:stretch; }
    [data-game="hangman"] .hm-stagecard{ background:#fff; border:1.5px solid #bcd0ea; border-radius:14px; padding:16px 14px; display:flex; flex-direction:column; align-items:center; gap:10px; }
    [data-game="hangman"] .hm-figure svg{ width:100%; max-width:210px; height:auto; color:var(--hm-navy); }
    [data-game="hangman"] .hm-gallows{ fill:none; stroke:var(--hm-navy); stroke-width:7; stroke-linecap:round; stroke-linejoin:round; }
    [data-game="hangman"] .hm-rope{ fill:none; stroke:var(--hm-navy); stroke-width:4; stroke-linecap:round; }
    [data-game="hangman"] .hm-part{ opacity:0; transition:opacity .3s ease; }
    [data-game="hangman"] .hm-part.on{ opacity:1; }
    [data-game="hangman"] .hm-part path, [data-game="hangman"] .hm-part circle.o{ fill:none; stroke:var(--hm-navy); stroke-width:5.5; stroke-linecap:round; stroke-linejoin:round; }
    [data-game="hangman"] #hmSvg .hm-frown{ display:none; }
    [data-game="hangman"] #hmSvg.lost .hm-smile{ display:none; }
    [data-game="hangman"] #hmSvg.lost .hm-frown{ display:inline; }
    [data-game="hangman"] .hm-meter-title{ font-size:13px; font-weight:900; letter-spacing:.08em; text-transform:uppercase; color:var(--hm-ink); margin-top:2px; }
    [data-game="hangman"] .hm-dots{ display:flex; gap:9px; }
    [data-game="hangman"] .hm-dots i{ width:18px; height:18px; border-radius:50%; background:#fff; border:2px solid #cbd5e1; }
    [data-game="hangman"] .hm-dots i.fl{ background:#f97316; border-color:#ea580c; }
    [data-game="hangman"] .hm-attempts{ font-size:13px; font-weight:700; color:#586a82; }
    [data-game="hangman"] .hm-wrongbox{ width:100%; border:1.5px solid var(--hm-line); border-radius:12px; padding:9px 12px 11px; text-align:center; }
    [data-game="hangman"] .hm-wrongbox .k{ font-size:12px; font-weight:800; color:#586a82; margin-bottom:6px; }
    [data-game="hangman"] .hm-wrongs{ display:flex; gap:7px; justify-content:center; flex-wrap:wrap; min-height:30px; }
    [data-game="hangman"] .hm-wrongs b{ width:30px; height:30px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:15px; font-weight:900; color:var(--hm-red); background:var(--hm-red-soft); border:1.5px solid #f5c2ca; }
    [data-game="hangman"] .hm-sidebtns{ width:100%; display:flex; gap:8px; margin-top:auto; }
    [data-game="hangman"] .hm-sidebtn{ flex:1; border:1.5px solid var(--hm-line); background:#fff; color:var(--hm-ink); border-radius:10px; padding:10px 6px; font-size:12.5px; font-weight:900; cursor:pointer; text-transform:uppercase; letter-spacing:.03em; }
    [data-game="hangman"] .hm-sidebtn:hover:not(:disabled){ background:var(--hm-soft); }
    [data-game="hangman"] .hm-sidebtn:disabled{ opacity:.5; cursor:default; }

    /* ============ question panel ============ */
    [data-game="hangman"] .hm-qcard{ position:relative; background:#fff; border:1.5px solid #bcd0ea; border-radius:14px; padding:16px 18px; display:flex; flex-direction:column; gap:10px; }
    [data-game="hangman"] .hm-chiprow{ display:flex; gap:8px; flex-wrap:wrap; }
    [data-game="hangman"] .hm-pill{ display:inline-flex; align-items:center; border-radius:9px; padding:5px 13px; font-size:12.5px; font-weight:800; background:var(--hm-blue-soft); color:var(--hm-blue-deep); border:1.5px solid #c9dcff; }
    [data-game="hangman"] .hm-cluelabel{ font-size:11px; font-weight:900; letter-spacing:.14em; text-transform:uppercase; color:#7a8aa0; }
    [data-game="hangman"] .hm-clue{ font-size:22px; font-weight:800; color:var(--hm-ink); line-height:1.3; }
    [data-game="hangman"] .hm-word{ display:flex; gap:8px; flex-wrap:wrap; padding:6px 0 2px; }
    [data-game="hangman"] .hm-box{ width:44px; height:50px; border-radius:10px; border:2px solid #cbd6e6; background:#fff; display:flex; align-items:center; justify-content:center; font-size:23px; font-weight:900; color:var(--hm-ink); text-transform:uppercase; box-shadow:0 2px 5px rgba(15,37,69,.06); }
    [data-game="hangman"] .hm-box.blank{ color:#93a0b5; }
    [data-game="hangman"] .hm-box.gap{ border:0; box-shadow:none; width:18px; background:transparent; }
    [data-game="hangman"] .hm-chooselabel{ text-align:center; font-size:15px; font-weight:800; color:var(--hm-ink); margin-top:2px; }
    [data-game="hangman"] .hm-keys{ display:grid; grid-template-columns:repeat(10,minmax(0,1fr)); gap:8px; }
    [data-game="hangman"] .hm-key{ position:relative; border:1.5px solid #cbd6e6; background:#fff; color:var(--hm-ink); border-radius:10px; min-height:48px; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:0; font-size:17px; font-weight:900; cursor:pointer; transition:transform .08s, background .15s, border-color .15s; }
    @media (hover:hover) and (pointer:fine){
        [data-game="hangman"] .hm-keys:not(.hm-nohover) .hm-key:hover:not(:disabled){ transform:translateY(-1px); border-color:var(--hm-blue); color:var(--hm-blue-deep); }
    }
    [data-game="hangman"] .hm-key:disabled{ cursor:default; }
    [data-game="hangman"] .hm-key .mk{ font-size:10px; font-weight:900; line-height:1; font-style:normal; }
    [data-game="hangman"] .hm-key.hit{ background:var(--hm-blue); border-color:var(--hm-blue-deep); color:#fff; }
    [data-game="hangman"] .hm-key.miss{ background:var(--hm-red-soft); border-color:#f5c2ca; color:var(--hm-red); }
    [data-game="hangman"] .hm-guessrow{ text-align:center; margin-top:2px; }
    [data-game="hangman"] .hm-guessbtn{ border:0; cursor:pointer; border-radius:11px; padding:13px 42px; font-size:14px; font-weight:900; letter-spacing:.07em; text-transform:uppercase; color:#fff; background:linear-gradient(120deg,var(--hm-blue),var(--hm-blue-deep)); box-shadow:0 6px 16px rgba(31,95,208,.3); }
    [data-game="hangman"] .hm-guessbtn:hover:not(:disabled){ filter:brightness(1.06); }
    [data-game="hangman"] .hm-guessbtn:disabled{ opacity:.55; cursor:default; }
    [data-game="hangman"] .hm-guessform{ display:flex; gap:8px; justify-content:center; align-items:center; flex-wrap:wrap; }
    [data-game="hangman"] .hm-guessform input{ width:min(320px,60%); border:2px solid var(--hm-blue); border-radius:10px; padding:11px 14px; font-size:16px; font-weight:800; color:var(--hm-ink); text-transform:uppercase; }
    [data-game="hangman"] .hm-guessform input:focus{ outline:none; box-shadow:0 0 0 3px rgba(31,95,208,.2); }
    [data-game="hangman"] .hm-guessform .go{ border:0; cursor:pointer; border-radius:10px; padding:12px 22px; font-size:13px; font-weight:900; color:#fff; background:var(--hm-blue); }
    [data-game="hangman"] .hm-guessform .no{ border:1.5px solid var(--hm-line); cursor:pointer; border-radius:10px; padding:11px 16px; font-size:13px; font-weight:900; color:#586a82; background:#fff; }

    [data-game="hangman"] .hm-toast{ position:absolute; right:14px; bottom:14px; z-index:5; display:flex; align-items:center; gap:9px; background:#e9f8ef; border:1.5px solid #7fd6a4; color:#116b3c; border-radius:12px; padding:11px 14px; font-size:14px; font-weight:700; box-shadow:0 10px 24px rgba(15,37,69,.18); max-width:min(400px,85%); animation:hmToastIn .25s ease; }
    [data-game="hangman"] .hm-toast.bad{ background:#fdeef0; border-color:#f0a8b2; color:#a01a2e; }
    [data-game="hangman"] .hm-toast .ic{ flex:none; width:22px; height:22px; border-radius:50%; background:var(--hm-green); color:#fff; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:900; }
    [data-game="hangman"] .hm-toast.bad .ic{ background:var(--hm-red); }
    [data-game="hangman"] .hm-toast .x{ flex:none; border:0; background:none; cursor:pointer; font-size:14px; font-weight:900; color:inherit; opacity:.6; padding:2px 4px; }
    @keyframes hmToastIn{ from{ transform:translateY(8px); opacity:0; } to{ transform:none; opacity:1; } }

    /* ============ bottom bar ============ */
    [data-game="hangman"] .hm-bottom{ display:grid; grid-template-columns:auto minmax(220px,1fr) auto; gap:14px; align-items:center; padding:10px 14px 14px; }
    [data-game="hangman"] .hm-benches{ display:flex; align-items:center; gap:10px; background:#fff; border:1.5px solid var(--hm-line); border-radius:14px; padding:8px 14px; }
    [data-game="hangman"] .hm-benchdiv{ width:1.5px; align-self:stretch; background:var(--hm-line); margin:2px 4px; }
    [data-game="hangman"] .hm-bench{ display:flex; gap:12px; }
    [data-game="hangman"] .hm-p{ text-align:center; width:58px; }
    [data-game="hangman"] .hm-p .av{ width:44px; height:44px; border-radius:50%; overflow:hidden; border:2.5px solid var(--hm-blue); background:#dbeafe; margin:0 auto; }
    [data-game="hangman"] .hm-p.b .av{ border-color:var(--hm-orange); }
    [data-game="hangman"] .hm-p .av img{ width:100%; height:100%; object-fit:cover; }
    [data-game="hangman"] .hm-p .av.cpu{ display:flex; align-items:center; justify-content:center; font-size:22px; background:#ffe9d4; }
    [data-game="hangman"] .hm-p .nm{ font-size:11px; font-weight:800; color:var(--hm-ink); margin-top:3px; white-space:nowrap; }
    [data-game="hangman"] .hm-p .st{ display:inline-block; font-size:9.5px; font-weight:900; border-radius:999px; padding:2px 8px; margin-top:2px; background:#e2e8f0; color:#586a82; }
    [data-game="hangman"] .hm-p .st.ok{ background:#dcf5e6; color:#0f7a40; }
    [data-game="hangman"] .hm-p .st.th{ background:#fdeecd; color:#a16207; }
    [data-game="hangman"] .hm-p .st.on{ background:#dbeafe; color:#1d4ed8; }
    [data-game="hangman"] .hm-feed{ display:flex; align-items:center; justify-content:center; gap:9px; background:var(--hm-blue-soft); border:1.5px solid #c9dcff; border-radius:14px; padding:10px 16px; min-height:56px; font-size:15px; color:var(--hm-ink); text-align:center; }
    [data-game="hangman"] .hm-feed .star{ color:var(--hm-blue); font-size:17px; }
    [data-game="hangman"] .hm-feed b{ font-weight:900; }
    [data-game="hangman"] .hm-feed .pts{ color:var(--hm-blue-deep); font-weight:900; }
    [data-game="hangman"] .hm-ctl{ display:flex; gap:8px; }
    [data-game="hangman"] .hm-ctlbtn{ width:62px; height:58px; border-radius:12px; border:1.5px solid var(--hm-line); background:#fff; cursor:pointer; font-size:17px; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:2px; color:var(--hm-ink); }
    [data-game="hangman"] .hm-ctlbtn span{ font-size:10px; font-weight:800; color:#586a82; }
    [data-game="hangman"] .hm-ctlbtn:hover{ background:var(--hm-soft); }

    /* ============ overlays ============ */
    [data-game="hangman"] .hm-overlay{ position:fixed; inset:0; z-index:60; background:rgba(7,22,46,.72); display:flex; align-items:center; justify-content:center; padding:16px; }
    [data-game="hangman"] .hm-panel{ width:min(94vw,480px); background:#fff; border-radius:18px; padding:26px; text-align:center; box-shadow:0 24px 70px rgba(0,0,0,.4); }
    [data-game="hangman"] .hm-panel h3{ margin:0 0 6px; font-size:24px; font-weight:900; color:var(--hm-ink); }
    [data-game="hangman"] .hm-panel p{ margin:4px 0; font-size:14px; color:#586a82; }
    [data-game="hangman"] .hm-panel .row{ display:flex; gap:10px; justify-content:center; margin:14px 0; flex-wrap:wrap; }
    [data-game="hangman"] .hm-stat{ min-width:100px; background:var(--hm-soft); border-radius:12px; padding:10px; }
    [data-game="hangman"] .hm-stat b{ display:block; font-size:22px; color:var(--hm-ink); }
    [data-game="hangman"] .hm-stat span{ font-size:11px; font-weight:800; color:#7a8aa0; text-transform:uppercase; }
    [data-game="hangman"] .hm-btn{ border:0; cursor:pointer; border-radius:999px; padding:12px 30px; margin:4px; font-size:14px; font-weight:900; color:#fff; background:linear-gradient(120deg,var(--hm-blue),var(--hm-blue-deep)); }
    [data-game="hangman"] .hm-btn.alt{ background:#475569; }

    @media (max-width:980px){
        [data-game="hangman"] .hm-main{ grid-template-columns:1fr; }
        [data-game="hangman"] .hm-scorerow{ grid-template-columns:1fr 1fr; }
        [data-game="hangman"] .hm-mid{ grid-column:1 / -1; order:3; }
        [data-game="hangman"] .hm-bottom{ grid-template-columns:1fr; }
        [data-game="hangman"] .hm-benches{ overflow-x:auto; }
        [data-game="hangman"] .hm-keys{ grid-template-columns:repeat(7,minmax(0,1fr)); }
    }
    @media (prefers-reduced-motion:reduce){ [data-game="hangman"] *{ animation:none !important; transition:none !important; } }
</style>
@endverbatim

    {{-- ============ CONFIG (shared component) ============ --}}
    <div id="hmConfig">
        <x-gamified-configuration
            title="Hangman Challenge"
            subtitle="Read the clue and guess the word one letter at a time — six mistakes and the round is lost."
            icon="🎯"
            :items="[10, 15, 20]"
            :items-default="10"
            :types="['identification']"
            :difficulty="true"
            :modes="['solo', 'team']"
            mode-default="solo"
            start-label="Start the challenge" />
    </div>

    {{-- ============ GAME ============ --}}
    <div id="hmGame" class="hm-stage hm-hidden">
        <div class="hm-top">
            <div class="hm-top-title">Hangman Challenge</div>
            <div id="hmTopMid" class="hm-top-mid">Word Showdown</div>
            <div class="hm-top-right">
                <span class="hm-timer">&#9201; <span id="hmTimer">--:--</span></span>
                <span class="hm-qno">Question <span id="hmQNow">1</span> of <span id="hmQTotal">0</span></span>
                <span class="hm-live">Practice Game</span>
            </div>
        </div>

        <div class="hm-scorerow">
            <div id="hmCardA" class="hm-teamcard a">
                <div class="hm-badge">&#127942;</div>
                <div>
                    <div class="hm-tc-label" id="hmALabel">Team Einstein</div>
                    <div class="hm-tc-pts"><span id="hmAPts">0</span> <small>pts</small></div>
                </div>
                <div class="hm-chips" id="hmAChips"></div>
                <div class="hm-tc-rounds"><b id="hmARounds">0</b><span>rounds won</span></div>
            </div>

            <div class="hm-mid">
                <div id="hmSeg" class="hm-seg"></div>
                <div class="hm-seglabels"><span>1</span><span id="hmSegEnd">0</span></div>
                <div id="hmTurn" class="hm-turnpill">&mdash;</div>
            </div>

            <div id="hmCardB" class="hm-teamcard b">
                <div class="hm-badge">&#127942;</div>
                <div>
                    <div class="hm-tc-label" id="hmBLabel">Team Newton</div>
                    <div class="hm-tc-pts"><span id="hmBPts">0</span> <small>pts</small></div>
                </div>
                <div class="hm-chips" id="hmBChips"></div>
                <div class="hm-tc-rounds"><b id="hmBRounds">0</b><span>rounds won</span></div>
            </div>
        </div>

        <div id="hmDeckNote" class="hm-note hm-hidden">Using built-in practice words &mdash; ask a teacher to add Identification questions for your subject to play your own.</div>

        <div class="hm-main">
            <div class="hm-stagecard">
                <div class="hm-figure">
                    <svg id="hmSvg" viewBox="0 0 220 240" role="img" aria-label="Hangman figure">
                        <path class="hm-gallows" d="M20 228 H150"/>
                        <path class="hm-gallows" d="M50 228 V16"/>
                        <path class="hm-gallows" d="M50 16 H150"/>
                        <path class="hm-rope" d="M150 16 V38"/>
                        <g class="hm-part" id="hmP0">
                            <circle class="o" cx="150" cy="60" r="20"/>
                            <circle cx="143" cy="56" r="2.4" fill="currentColor"/>
                            <circle cx="157" cy="56" r="2.4" fill="currentColor"/>
                            <path class="hm-smile" d="M142 66 Q150 74 158 66"/>
                            <path class="hm-frown" d="M142 72 Q150 64 158 72"/>
                        </g>
                        <g class="hm-part" id="hmP1"><path d="M150 80 V140"/></g>
                        <g class="hm-part" id="hmP2"><path d="M150 96 L122 122"/></g>
                        <g class="hm-part" id="hmP3"><path d="M150 96 L178 122"/></g>
                        <g class="hm-part" id="hmP4"><path d="M150 140 L128 180"/></g>
                        <g class="hm-part" id="hmP5"><path d="M150 140 L172 180"/></g>
                    </svg>
                </div>
                <div class="hm-meter-title">Mistake Meter</div>
                <div id="hmDots" class="hm-dots"></div>
                <div id="hmAttempts" class="hm-attempts">6 attempts remaining</div>
                <div class="hm-wrongbox">
                    <div class="k">Wrong guesses</div>
                    <div id="hmWrongs" class="hm-wrongs"></div>
                </div>
                <div class="hm-sidebtns">
                    <button type="button" id="hmHint" class="hm-sidebtn">&#128161; Hint (&minus;25)</button>
                    <button type="button" id="hmSkip" class="hm-sidebtn">Skip question</button>
                </div>
            </div>

            <div class="hm-qcard">
                <div class="hm-chiprow"><span class="hm-pill" id="hmScopePill">All subjects</span></div>
                <div class="hm-cluelabel">Clue</div>
                <div class="hm-clue" id="hmClue"></div>
                <div class="hm-chiprow">
                    <span class="hm-pill" id="hmTypePill">Identification</span>
                    <span class="hm-pill" id="hmLenPill">0 letters</span>
                </div>
                <div id="hmWord" class="hm-word"></div>
                <div class="hm-chooselabel">Choose a letter</div>
                <div id="hmKeys" class="hm-keys"></div>
                <div class="hm-guessrow" id="hmGuessRow">
                    <button type="button" id="hmGuessWord" class="hm-guessbtn">Guess word</button>
                </div>
                <div class="hm-guessform hm-hidden" id="hmGuessForm">
                    <input type="text" id="hmGuessInput" maxlength="60" autocomplete="off" spellcheck="false" placeholder="Type the whole word&hellip;">
                    <button type="button" class="go" id="hmGuessGo">Submit</button>
                    <button type="button" class="no" id="hmGuessCancel">Cancel</button>
                </div>
                <div id="hmToast" class="hm-toast hm-hidden"></div>
            </div>
        </div>

        <div class="hm-bottom">
            <div class="hm-benches">
                <div id="hmBenchA" class="hm-bench a"></div>
                <div class="hm-benchdiv"></div>
                <div id="hmBenchB" class="hm-bench b"></div>
            </div>
            <div class="hm-feed"><span class="star">&#9733;</span><span id="hmFeed">Guess your first letter!</span></div>
            <div class="hm-ctl">
                <button type="button" id="hmPause" class="hm-ctlbtn" aria-label="Pause">&#10073;&#10073;<span>Pause</span></button>
                <button type="button" id="hmSound" class="hm-ctlbtn" aria-label="Toggle sound">&#128266;<span>Sound</span></button>
                <button type="button" id="hmSettings" class="hm-ctlbtn" aria-label="Settings">&#9881;<span>Settings</span></button>
            </div>
        </div>
    </div>

    {{-- pause / end overlays --}}
    <div id="hmPauseOv" class="hm-overlay hm-hidden">
        <div class="hm-panel">
            <h3>Game paused</h3>
            <p>The timer is stopped. Take a breath!</p>
            <div style="margin-top:14px">
                <button type="button" id="hmResume" class="hm-btn">Resume</button>
                <button type="button" id="hmQuit" class="hm-btn alt">Exit game</button>
            </div>
        </div>
    </div>
    <div id="hmEndOv" class="hm-overlay hm-hidden"><div class="hm-panel" id="hmEndPanel"></div></div>
</div>

<script>
(function () {
    'use strict';
    const ENDPOINT = @json(route('tools.games.questions'));
    const CATALOG = @json(route('tools.games.index'));
    const el = (id) => document.getElementById(id);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));
    const MAX_WRONG = 6;
    const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('');

    if (window.self !== window.top) document.querySelector('[data-game="hangman"]').classList.add('hm-embedded');

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
    try { muted = localStorage.getItem('hm_muted') === '1'; } catch (e) {}
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
        win() { [523, 659, 784].forEach((f, i) => tone(f, .18, 'triangle', .07, i * .1)); },
        end() { [523, 659, 784, 1047].forEach((f, i) => tone(f, .2, 'triangle', .07, i * .12)); },
    };
    function paintSound() { el('hmSound').innerHTML = (muted ? '\u{1F507}' : '\u{1F50A}') + '<span>Sound</span>'; }
    paintSound();
    el('hmSound').addEventListener('click', () => {
        muted = !muted;
        try { localStorage.setItem('hm_muted', muted ? '1' : '0'); } catch (e) {}
        paintSound();
    });

    // ---------------- fallback deck (bank empty for this scope) --------------
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
        { question: 'The process plants use to make food using sunlight', answer: 'PHOTOSYNTHESIS' },
        { question: 'An animal that says "moo"', answer: 'COW' },
        { question: 'The season after winter', answer: 'SPRING' },
        { question: 'The force that pulls things toward the ground', answer: 'GRAVITY' },
        { question: 'The red planet', answer: 'MARS' },
        { question: 'Water falling from the sky in frozen flakes', answer: 'SNOW' },
        { question: 'The organ that pumps blood', answer: 'HEART' },
        { question: 'The star pattern you connect in the night sky', answer: 'CONSTELLATION' },
    ];

    // ---------------- state ----------------
    let questions = [], mode = 'solo';
    let roundIdx = 0, turn = 'A';
    let answer = '', guessed = new Set(), wrong = 0, roundOver = false, over = false, paused = false, active = false;
    let scores = { A: 0, B: 0 }, roundsWon = { A: 0, B: 0 };
    let segResult = [];              // per round: 'A' | 'B' | 'x'
    let seat = { A: 0, B: 0 };       // rotating "current player" per bench
    let lastGuesser = null;
    let timerLeft = 0, timerH = null, botH = null, nextH = null, toastH = null;

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
    const guesserName = () => {
        const bench = roster[turn];
        return bench[seat[turn] % bench.length].n;
    };

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
            questions = (data.questions || []).filter(q => /[a-z]/i.test(q.answer));
            if (questions.length === 0) { questions = pickFallback(limit); usedFallback = true; }
        } catch (e) {
            questions = pickFallback(limit);
            usedFallback = true;
        } finally {
            window.GamifiedConfig.loading(false);
        }

        el('hmDeckNote').classList.toggle('hm-hidden', !usedFallback);
        buildRoster();
        roundIdx = 0; scores = { A: 0, B: 0 }; roundsWon = { A: 0, B: 0 };
        segResult = new Array(questions.length).fill(null);
        seat = { A: 0, B: 0 }; over = false; paused = false;

        const scopeTxt = (window.GameScope && window.GameScope.summary) ? window.GameScope.summary() : 'All subjects';
        el('hmScopePill').textContent = scopeTxt;
        el('hmTopMid').textContent = questions.length + '-Question '
            + (scopeTxt && scopeTxt !== 'All subjects' ? scopeTxt + ' ' : '') + 'Showdown';
        el('hmQTotal').textContent = questions.length;
        el('hmSegEnd').textContent = questions.length;
        el('hmALabel').textContent = teamLabel('A');
        el('hmBLabel').textContent = teamLabel('B');
        renderChips();
        feed('First to solve takes the round — good luck!');

        timerLeft = questions.length * 45;
        clearInterval(timerH);
        timerH = setInterval(tick, 1000);
        paintTimer();

        el('hmConfig').classList.add('hm-hidden');
        el('hmGame').classList.remove('hm-hidden');
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
        if (timerLeft <= 0) endGame(true);
    }
    function paintTimer() {
        const t = Math.max(0, timerLeft);
        el('hmTimer').textContent = String(Math.floor(t / 60)).padStart(2, '0') + ':' + String(t % 60).padStart(2, '0');
    }

    // ---------------- rounds ----------------
    function startRound() {
        const q = questions[roundIdx];
        answer = String(q.answer).toUpperCase();
        guessed = new Set();
        wrong = 0;
        roundOver = false;
        turn = roundIdx % 2 === 0 ? 'A' : 'B';
        lastGuesser = null;
        el('hmSvg').classList.remove('lost');
        hideGuessForm();

        el('hmQNow').textContent = roundIdx + 1;
        el('hmClue').textContent = q.question;
        const letters = answer.replace(/[^A-Z0-9]/g, '').length;
        el('hmLenPill').textContent = letters + (letters === 1 ? ' letter' : ' letters');
        renderAll();

        clearInterval(botH);
        if (isBotTurn()) botH = setInterval(botGuess, 950);
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

    // ---------------- guessing ----------------
    function occurrences(letter) { return answer.split('').filter(ch => ch === letter).length; }
    const wordComplete = () => answer.split('').every(ch => !/[A-Z0-9]/.test(ch) || guessed.has(ch));

    function guess(letter) {
        if (!active || roundOver || paused || over || guessed.has(letter)) return;
        guessed.add(letter);
        const who = guesserName();
        lastGuesser = seat[turn] % roster[turn].length;
        seat[turn]++;
        const occ = occurrences(letter);
        if (occ > 0) {
            const pts = 20 * occ;
            scores[turn] += pts;
            sfx.ok();
            toast(true, 'Correct! The letter <b>' + esc(letter) + '</b> appears ' + (occ > 1 ? occ + ' times' : 'once') + '. <b>+' + pts + ' pts</b>');
            feed('<b>' + esc(who) + '</b> guessed <b>' + esc(letter) + '</b> <span class="pts">+' + pts + ' pts</span>');
            if (wordComplete()) return winRound();
        } else {
            wrong++;
            sfx.bad();
            toast(false, 'Sorry — no letter <b>' + esc(letter) + '</b> in this word.');
            feed('<b>' + esc(who) + '</b> guessed <b>' + esc(letter) + '</b> — not in the word');
            if (wrong >= MAX_WRONG) return loseRound();
        }
        renderAll();
    }

    function winRound() {
        roundOver = true;
        clearInterval(botH);
        scores[turn] += 100;
        roundsWon[turn]++;
        segResult[roundIdx] = turn;
        sfx.win();
        feed('✨ <b>' + esc(teamLabel(turn)) + '</b> solved <b>' + esc(answer) + '</b> <span class="pts">+100 pts</span>');
        renderAll();
        scheduleNext(1800);
    }

    function loseRound() {
        roundOver = true;
        clearInterval(botH);
        segResult[roundIdx] = 'x';
        el('hmSvg').classList.add('lost');
        toast(false, 'Out of attempts — the word was <b>' + esc(answer) + '</b>.');
        feed('The word was <b>' + esc(answer) + '</b> — no points this round');
        renderAll();
        scheduleNext(2200);
    }

    function skipRound() {
        if (!active || roundOver || paused || over || isBotTurn()) return;
        roundOver = true;
        clearInterval(botH);
        segResult[roundIdx] = 'x';
        feed('<b>' + esc(teamLabel(turn)) + '</b> skipped — the word was <b>' + esc(answer) + '</b>');
        renderAll();
        scheduleNext(1200);
    }

    function hint() {
        if (!active || roundOver || paused || over || isBotTurn()) return;
        const unrevealed = ALPHABET.filter(l => answer.includes(l) && !guessed.has(l));
        if (!unrevealed.length) return;
        const letter = unrevealed[Math.floor(Math.random() * unrevealed.length)];
        scores[turn] = Math.max(0, scores[turn] - 25);
        guessed.add(letter);
        feed('\u{1F4A1} <b>' + esc(teamLabel(turn)) + '</b> used a hint: <b>' + esc(letter) + '</b> <span class="pts">−25 pts</span>');
        toast(true, 'Hint revealed the letter <b>' + esc(letter) + '</b>. <b>−25 pts</b>');
        if (wordComplete()) return winRound();
        renderAll();
    }

    // full-word guess
    function showGuessForm() {
        if (!active || roundOver || paused || over || isBotTurn()) return;
        el('hmGuessRow').classList.add('hm-hidden');
        el('hmGuessForm').classList.remove('hm-hidden');
        el('hmGuessInput').value = '';
        el('hmGuessInput').focus();
    }
    function hideGuessForm() {
        el('hmGuessForm').classList.add('hm-hidden');
        el('hmGuessRow').classList.remove('hm-hidden');
    }
    function submitWordGuess() {
        const norm = (s) => String(s).toUpperCase().replace(/[^A-Z0-9]/g, '');
        const typed = norm(el('hmGuessInput').value);
        if (!typed) return;
        hideGuessForm();
        if (typed === norm(answer)) {
            answer.split('').forEach(ch => { if (/[A-Z0-9]/.test(ch)) guessed.add(ch); });
            toast(true, '<b>' + esc(guesserName()) + '</b> guessed the whole word! <b>+100 pts</b>');
            return winRound();
        }
        wrong++;
        sfx.bad();
        toast(false, 'Not quite — that’s not the word. One attempt lost.');
        feed('<b>' + esc(teamLabel(turn)) + '</b> tried a full guess — wrong!');
        if (wrong >= MAX_WRONG) return loseRound();
        renderAll();
    }

    // rival bot (solo mode): paced guesses with imperfect accuracy
    function botGuess() {
        if (paused || over || roundOver || !isBotTurn()) return;
        const good = ALPHABET.filter(l => answer.includes(l) && !guessed.has(l));
        const bad = ALPHABET.filter(l => !answer.includes(l) && !guessed.has(l));
        if (!good.length && !bad.length) return;
        const pick = (Math.random() < 0.7 && good.length) || !bad.length
            ? good[Math.floor(Math.random() * good.length)]
            : bad[Math.floor(Math.random() * bad.length)];
        guess(pick);
    }

    // ---------------- rendering ----------------
    function renderAll() {
        renderWord();
        renderKeys();
        renderFigure();
        renderScore();
        renderSeg();
        renderTurn();
        renderBenches();
    }

    function renderWord() {
        el('hmWord').innerHTML = answer.split('').map(ch => {
            if (ch === ' ') return '<span class="hm-box gap"></span>';
            if (!/[A-Z0-9]/.test(ch)) return '<span class="hm-box">' + esc(ch) + '</span>';
            const shown = guessed.has(ch) || roundOver;
            return '<span class="hm-box' + (shown ? '' : ' blank') + '">' + (shown ? esc(ch) : '_') + '</span>';
        }).join('');
    }

    function renderKeys() {
        const locked = roundOver || isBotTurn() || paused || over;
        const box = el('hmKeys');
        box.innerHTML = ALPHABET.map(l => {
            const used = guessed.has(l), inWord = answer.includes(l);
            let cls = 'hm-key', mk = '';
            if (used && inWord) { cls += ' hit'; mk = '<i class="mk">✓</i>'; }
            else if (used) { cls += ' miss'; mk = '<i class="mk">✕</i>'; }
            return '<button type="button" data-letter="' + l + '" class="' + cls + '" ' + ((used || locked) ? 'disabled' : '') + '>' + l + mk + '</button>';
        }).join('');
        // sticky-hover guard: a rebuilt key under the resting pointer must not light up
        box.classList.add('hm-nohover');
        box.querySelectorAll('.hm-key').forEach(b => b.addEventListener('click', () => guess(b.dataset.letter)));
        el('hmGuessWord').disabled = locked;
        el('hmHint').disabled = locked;
        el('hmSkip').disabled = locked;
    }
    document.addEventListener('mousemove', () => el('hmKeys').classList.remove('hm-nohover'));

    function renderFigure() {
        for (let i = 0; i < MAX_WRONG; i++) {
            const part = el('hmP' + i);
            if (part) part.classList.toggle('on', i < wrong);
        }
        el('hmDots').innerHTML = Array.from({ length: MAX_WRONG }, (_, i) => '<i class="' + (i < wrong ? 'fl' : '') + '"></i>').join('');
        const left = MAX_WRONG - wrong;
        el('hmAttempts').textContent = left + (left === 1 ? ' attempt' : ' attempts') + ' remaining';
        const misses = [...guessed].filter(l => !answer.includes(l)).sort();
        el('hmWrongs').innerHTML = misses.length
            ? misses.map(l => '<b>' + l + '</b>').join('')
            : '<span style="font-size:12px;color:#93a0b5;align-self:center">None yet</span>';
    }

    function renderScore() {
        el('hmAPts').textContent = scores.A; el('hmBPts').textContent = scores.B;
        el('hmARounds').textContent = roundsWon.A; el('hmBRounds').textContent = roundsWon.B;
        el('hmCardA').classList.toggle('turn', turn === 'A' && !over);
        el('hmCardB').classList.toggle('turn', turn === 'B' && !over);
    }

    function renderSeg() {
        el('hmSeg').innerHTML = questions.map((_, i) => {
            let cls = segResult[i] === 'A' ? 'a' : segResult[i] === 'B' ? 'b' : segResult[i] === 'x' ? 'x' : '';
            if (i === roundIdx && !over) cls += ' cur';
            return '<i class="' + cls.trim() + '"></i>';
        }).join('');
    }

    function renderTurn() {
        const pill = el('hmTurn');
        pill.textContent = turnText(turn);
        pill.classList.toggle('b', turn === 'B');
    }

    function renderChips() {
        ['A', 'B'].forEach(t => {
            el('hm' + t + 'Chips').innerHTML = roster[t].map(p => p.cpu
                ? '<span class="hm-chip cpu">\u{1F916}</span>'
                : '<span class="hm-chip"><img src="' + p.img + '" alt="' + esc(p.n) + '"></span>').join('');
        });
    }

    function renderBenches() {
        ['A', 'B'].forEach(t => {
            const activeTeam = t === turn && !roundOver && !over;
            el('hmBench' + t).innerHTML = roster[t].map((p, i) => {
                let st = '<span class="st on">Online</span>';
                if (activeTeam) {
                    st = i === seat[t] % roster[t].length ? '<span class="st th">Thinking</span>' : '<span class="st ok">Ready</span>';
                } else if (t === turn && roundOver && lastGuesser === i) {
                    st = '<span class="st ok">Answered</span>';
                }
                const av = p.cpu
                    ? '<div class="av cpu">\u{1F916}</div>'
                    : '<div class="av"><img src="' + p.img + '" alt=""></div>';
                return '<div class="hm-p ' + (t === 'B' ? 'b' : 'a') + '">' + av + '<div class="nm">' + esc(p.n) + '</div>' + st + '</div>';
            }).join('');
        });
    }

    // ---------------- feed + toast ----------------
    function feed(html) { el('hmFeed').innerHTML = html; }
    function toast(good, html) {
        const t = el('hmToast');
        t.className = 'hm-toast' + (good ? '' : ' bad');
        t.innerHTML = '<span class="ic">' + (good ? '✓' : '✕') + '</span><span>' + html + '</span><button type="button" class="x" aria-label="Dismiss">✕</button>';
        t.querySelector('.x').addEventListener('click', () => t.classList.add('hm-hidden'));
        clearTimeout(toastH);
        toastH = setTimeout(() => t.classList.add('hm-hidden'), 2600);
    }

    // ---------------- pause / end ----------------
    function setPaused(on) {
        paused = on;
        el('hmPauseOv').classList.toggle('hm-hidden', !on);
        if (!on && isBotTurn() && !roundOver) { clearInterval(botH); botH = setInterval(botGuess, 950); }
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
        el('hmEndPanel').innerHTML =
            '<h3>' + esc(headline) + '</h3>'
            + '<p>' + (timeUp ? "Time's up! Final tally:" : 'All ' + questions.length + ' questions played. Final tally:') + '</p>'
            + '<div class="row">'
            +   '<div class="hm-stat"><b>' + a + '</b><span>' + esc(teamLabel('A')) + '</span></div>'
            +   '<div class="hm-stat"><b>' + b + '</b><span>' + esc(teamLabel('B')) + '</span></div>'
            +   '<div class="hm-stat"><b>' + roundsWon.A + ' – ' + roundsWon.B + '</b><span>Rounds won</span></div>'
            + '</div>'
            + '<p style="font-size:12px">Practice game — nothing here is graded.</p>'
            + '<div><button type="button" id="hmAgain" class="hm-btn">Play again</button>'
            + '<button type="button" id="hmExit" class="hm-btn alt">Exit</button></div>';
        el('hmEndOv').classList.remove('hm-hidden');
        el('hmAgain').addEventListener('click', () => window.location.reload());
        el('hmExit').addEventListener('click', exitGame);
        renderAll();
    }

    function exitGame() {
        if (window.parent !== window) window.parent.postMessage({ type: 'schoollms:game-exit' }, '*');
        else window.location.href = CATALOG;
    }

    // ---------------- wiring ----------------
    document.addEventListener('gamified-config:start', (e) => start(e.detail));
    el('hmHint').addEventListener('click', hint);
    el('hmSkip').addEventListener('click', skipRound);
    el('hmGuessWord').addEventListener('click', showGuessForm);
    el('hmGuessGo').addEventListener('click', submitWordGuess);
    el('hmGuessCancel').addEventListener('click', hideGuessForm);
    el('hmGuessInput').addEventListener('keydown', (e) => { if (e.key === 'Enter') submitWordGuess(); });
    el('hmPause').addEventListener('click', () => setPaused(true));
    el('hmSettings').addEventListener('click', () => setPaused(true));
    el('hmResume').addEventListener('click', () => setPaused(false));
    el('hmQuit').addEventListener('click', exitGame);

    document.addEventListener('keydown', (e) => {
        if (!active || roundOver || paused || over || isBotTurn()) return;
        if (document.activeElement === el('hmGuessInput')) return;
        const l = e.key.toUpperCase();
        if (ALPHABET.includes(l)) guess(l);
    });
})();
</script>
</div>
