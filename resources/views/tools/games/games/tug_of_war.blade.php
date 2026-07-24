{{-- Tug-of-War (The Momentum Battle) — live multiplayer quiz.

     Server-authoritative: the match lives in game_sessions and is driven through
     /tools/games/api/sessions/* (GameSessionController). The correct answer is
     graded on the server; the client only renders state. Phase 1 is single-screen
     host-driven with polling; Phase 2 layers Echo presence for multi-device play.

     Presentation is a build-independent scoped theme: all styling lives in the
     scoped style block below, under [data-game="tug-of-war"] with tug- prefixed
     classes, so it never depends on the shared Tailwind build. --}}
<div data-game="tug-of-war" class="tug-root">

@verbatim
<style>
    [data-game="tug-of-war"]{
        --tug-blue:#1f5fd0; --tug-blue-deep:#0f3aa0; --tug-blue-soft:#e8f0ff;
        --tug-orange:#e56a1c; --tug-orange-deep:#c04f0d; --tug-orange-soft:#fff0e6;
        --tug-navy:#0f2545; --tug-ink:#1f2a3a; --tug-line:#dfe6f0;
    }
    [data-game="tug-of-war"] .tug-hidden{ display:none !important; }
    [data-game="tug-of-war"] *{ box-sizing:border-box; }

    /* Widen the game stage beyond the catalog iframe's readable cap. */
    [data-game="tug-of-war"].tug-embedded .tug-stage{ position:relative; left:50%; transform:translateX(-50%); width:min(1200px,95vw); }

    /* ============ shared card ============ */
    [data-game="tug-of-war"] .tug-card{ background:#fff; border:1px solid var(--tug-line); border-radius:16px; box-shadow:0 10px 30px rgba(15,37,69,.08); overflow:hidden; }

    /* ============ team builder (slot inside the shared config component) ============ */
    [data-game="tug-of-war"] .tug-field label{ display:block; color:#586a82; font-size:10px; font-weight:800; letter-spacing:.1em; text-transform:uppercase; margin-bottom:5px; }
    [data-game="tug-of-war"] .tug-select{ width:100%; appearance:none; background:#fff; color:var(--tug-ink); border:1.5px solid var(--tug-line); border-radius:10px; padding:10px 12px; font-size:14px; font-weight:600; cursor:pointer; }
    [data-game="tug-of-war"] .tug-select:focus{ outline:none; border-color:var(--tug-blue); box-shadow:0 0 0 3px rgba(31,95,208,.15); }

    [data-game="tug-of-war"] .tug-teamwrap{ margin-top:16px; border:1px dashed #cfd8e6; border-radius:12px; padding:14px; }
    [data-game="tug-of-war"] .tug-teamwrap-title{ font-size:11px; font-weight:800; letter-spacing:.08em; text-transform:uppercase; color:#586a82; margin-bottom:10px; }
    [data-game="tug-of-war"] .tug-roster{ display:flex; flex-direction:column; gap:7px; max-height:230px; overflow:auto; }
    [data-game="tug-of-war"] .tug-rrow{ display:flex; align-items:center; justify-content:space-between; gap:10px; background:#f7f9fc; border:1px solid var(--tug-line); border-radius:10px; padding:7px 10px; }
    [data-game="tug-of-war"] .tug-rname{ font-size:13px; font-weight:700; color:var(--tug-ink); min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    [data-game="tug-of-war"] .tug-teambtns{ display:flex; gap:5px; flex:0 0 auto; }
    [data-game="tug-of-war"] .tug-tb{ border:1.5px solid var(--tug-line); background:#fff; color:#7a8aa0; font-size:11px; font-weight:800; border-radius:8px; padding:5px 10px; cursor:pointer; }
    [data-game="tug-of-war"] .tug-tb.is-a{ border-color:var(--tug-blue); color:#fff; background:var(--tug-blue); }
    [data-game="tug-of-war"] .tug-tb.is-b{ border-color:var(--tug-orange); color:#fff; background:var(--tug-orange); }
    [data-game="tug-of-war"] .tug-empty{ font-size:12px; color:#8a97a8; text-align:center; padding:14px; }

    /* ============ GAME ============ */
    [data-game="tug-of-war"] .tug-top{ display:flex; align-items:center; justify-content:space-between; gap:12px; background:var(--tug-navy); color:#fff; padding:12px 18px; }
    [data-game="tug-of-war"] .tug-top-title{ font-size:16px; font-weight:900; letter-spacing:.06em; }
    [data-game="tug-of-war"] .tug-top-mid{ display:flex; align-items:center; gap:12px; font-size:14px; font-weight:700; }
    [data-game="tug-of-war"] .tug-pill{ display:inline-flex; align-items:center; gap:6px; background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.2); border-radius:999px; padding:5px 12px; font-size:13px; font-weight:800; }
    [data-game="tug-of-war"] .tug-code{ font-family:ui-monospace,monospace; letter-spacing:.15em; }

    [data-game="tug-of-war"] .tug-arena{ display:grid; grid-template-columns:170px 1fr 170px; gap:10px; align-items:center; padding:16px 18px 4px; background:linear-gradient(180deg,#f4f7fc,#fff); }
    [data-game="tug-of-war"] .tug-team{ border-radius:14px; padding:12px; color:#fff; text-align:center; }
    [data-game="tug-of-war"] .tug-team.a{ background:linear-gradient(160deg,var(--tug-blue),var(--tug-blue-deep)); }
    [data-game="tug-of-war"] .tug-team.b{ background:linear-gradient(160deg,var(--tug-orange),var(--tug-orange-deep)); }
    [data-game="tug-of-war"] .tug-team-label{ font-size:12px; font-weight:800; letter-spacing:.06em; text-transform:uppercase; opacity:.95; }
    [data-game="tug-of-war"] .tug-team-score{ font-size:30px; font-weight:900; line-height:1.1; margin-top:2px; }
    [data-game="tug-of-war"] .tug-team-score small{ font-size:13px; font-weight:700; opacity:.85; }
    [data-game="tug-of-war"] .tug-team-correct{ font-size:12px; font-weight:700; opacity:.9; margin-top:2px; }
    [data-game="tug-of-war"] .tug-mascot{ width:52px; height:52px; margin:0 auto 6px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:24px; font-weight:900; color:#fff; background:rgba(255,255,255,.18); border:2px solid rgba(255,255,255,.6); }

    /* rope */
    [data-game="tug-of-war"] .tug-rope{ position:relative; padding:6px 4px; }
    [data-game="tug-of-war"] .tug-rope-line{ position:absolute; left:0; right:0; top:50%; height:6px; transform:translateY(-50%); border-radius:6px; background:linear-gradient(90deg,var(--tug-blue) 0%, #c9a06a 45%, #c9a06a 55%, var(--tug-orange) 100%); }
    [data-game="tug-of-war"] .tug-nodes{ position:relative; display:flex; align-items:center; justify-content:space-between; }
    [data-game="tug-of-war"] .tug-node{ width:12px; height:12px; border-radius:50%; background:#cbd5e3; border:2px solid #fff; box-shadow:0 1px 2px rgba(0,0,0,.15); z-index:1; }
    [data-game="tug-of-war"] .tug-node.a{ background:var(--tug-blue); }
    [data-game="tug-of-war"] .tug-node.b{ background:var(--tug-orange); }
    [data-game="tug-of-war"] .tug-knot{ position:absolute; top:50%; width:20px; height:26px; border-radius:5px; background:#0f2545; border:2px solid #ffd86b; transform:translate(-50%,-50%); transition:left .5s cubic-bezier(.3,1.2,.5,1); z-index:2; box-shadow:0 3px 8px rgba(0,0,0,.3); }
    /* Flag on the knot so the rope position reads at a glance: a pole with a
       gold tip (::before) and a waving red pennant (::after). Pure CSS — it
       rides the knot's left transition, so every pull visibly marches the flag. */
    [data-game="tug-of-war"] .tug-knot::before{ content:''; position:absolute; left:50%; bottom:100%; width:3px; height:32px; transform:translateX(-50%); background:linear-gradient(180deg,#ffd86b 0 5px,#8a5a2b 5px,#5d3a15); border-radius:2px; box-shadow:0 1px 2px rgba(0,0,0,.25); }
    [data-game="tug-of-war"] .tug-knot::after{ content:''; position:absolute; left:calc(50% + 1px); bottom:calc(100% + 13px); width:24px; height:17px; background:linear-gradient(180deg,#ff5252,#d81f1f); clip-path:polygon(0 0, 100% 50%, 0 100%); filter:drop-shadow(0 1px 2px rgba(0,0,0,.35)); transform-origin:left center; animation:tug-flag-wave 1.1s ease-in-out infinite; }
    @keyframes tug-flag-wave{ 0%,100%{ transform:scaleX(1) skewY(0deg); } 50%{ transform:scaleX(.8) skewY(5deg); } }
    @media (prefers-reduced-motion: reduce){ [data-game="tug-of-war"] .tug-knot::after{ animation:none; } }
    /* Team pullers hauling each end of the rope, rocking as they strain. The
       pivot sits on the hands (the rope grip) so the body leans, not the grip. */
    [data-game="tug-of-war"] .tug-puller{ position:absolute; top:50%; width:52px; height:52px; z-index:3; filter:drop-shadow(0 3px 4px rgba(15,37,69,.25)); pointer-events:none; }
    [data-game="tug-of-war"] .tug-puller.a{ left:-6px; transform-origin:92% 52%; animation:tug-strain-a 1.3s ease-in-out infinite; }
    /* B is pre-mirrored INSIDE its SVG (a scale(-1,1) group), so its hands sit
       at 8% and no CSS scaleX is needed — an off-center CSS flip used to shove
       the whole figure sideways into the orange team card. */
    [data-game="tug-of-war"] .tug-puller.b{ right:-6px; transform-origin:8% 52%; animation:tug-strain-b 1.3s ease-in-out .65s infinite; }
    @keyframes tug-strain-a{ 0%,100%{ transform:translateY(-52%) rotate(-8deg); } 50%{ transform:translateY(-52%) rotate(-16deg); } }
    @keyframes tug-strain-b{ 0%,100%{ transform:translateY(-52%) rotate(8deg); } 50%{ transform:translateY(-52%) rotate(16deg); } }
    @media (prefers-reduced-motion: reduce){
        [data-game="tug-of-war"] .tug-puller.a{ animation:none; transform:translateY(-52%) rotate(-10deg); }
        [data-game="tug-of-war"] .tug-puller.b{ animation:none; transform:translateY(-52%) rotate(10deg); }
    }
    [data-game="tug-of-war"] .tug-finish{ display:flex; justify-content:space-between; font-size:10px; font-weight:800; letter-spacing:.08em; text-transform:uppercase; margin-top:6px; }
    [data-game="tug-of-war"] .tug-finish .a{ color:var(--tug-blue-deep); }
    [data-game="tug-of-war"] .tug-finish .b{ color:var(--tug-orange-deep); }

    /* question */
    [data-game="tug-of-war"] .tug-qcard{ margin:12px 18px; border:1px solid var(--tug-line); border-radius:16px; padding:18px 20px; background:#fff; box-shadow:0 6px 18px rgba(15,37,69,.06); }
    [data-game="tug-of-war"] .tug-qnum{ text-align:center; color:#8a97a8; font-size:11px; font-weight:800; letter-spacing:.16em; text-transform:uppercase; }
    [data-game="tug-of-war"] .tug-qtext{ text-align:center; color:var(--tug-ink); font-size:22px; font-weight:800; line-height:1.35; margin:6px 0 14px; }
    [data-game="tug-of-war"] .tug-arm{ display:flex; justify-content:center; gap:10px; margin-bottom:12px; }
    [data-game="tug-of-war"] .tug-armbtn{ border:2px solid var(--tug-line); background:#fff; color:#586a82; font-size:12px; font-weight:800; border-radius:10px; padding:7px 16px; cursor:pointer; }
    [data-game="tug-of-war"] .tug-armbtn.a.is-armed{ border-color:var(--tug-blue); color:#fff; background:var(--tug-blue); }
    [data-game="tug-of-war"] .tug-armbtn.b.is-armed{ border-color:var(--tug-orange); color:#fff; background:var(--tug-orange); }
    [data-game="tug-of-war"] .tug-armbtn:disabled{ opacity:.4; cursor:default; text-decoration:line-through; }
    [data-game="tug-of-war"] .tug-opts{ display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    [data-game="tug-of-war"] .tug-opt{ display:flex; align-items:center; gap:12px; border:1.5px solid var(--tug-line); background:#fff; border-radius:12px; padding:13px 16px; cursor:pointer; text-align:left; }
    /* Hover only on real pointers (touch "hover" sticks after a tap), and only
       after the mouse moves post-render — otherwise the next question's button
       under the resting cursor looks like the previous pick persisting. */
    @media (hover:hover) and (pointer:fine){
        [data-game="tug-of-war"] .tug-opts:not(.tug-nohover) .tug-opt:hover:not(:disabled){ border-color:var(--tug-blue); background:var(--tug-blue-soft); }
    }
    [data-game="tug-of-war"] .tug-optletter{ flex:0 0 auto; width:30px; height:30px; border-radius:8px; background:var(--tug-blue); color:#fff; font-weight:900; display:flex; align-items:center; justify-content:center; font-size:14px; }
    [data-game="tug-of-war"] .tug-opttext{ font-size:15px; font-weight:600; color:var(--tug-ink); }
    [data-game="tug-of-war"] .tug-opt.is-correct{ border-color:#16a34a; background:#e9faef; }
    [data-game="tug-of-war"] .tug-opt.is-correct .tug-optletter{ background:#16a34a; }
    [data-game="tug-of-war"] .tug-opt.is-wrong{ border-color:#dc2626; background:#fdecea; }
    [data-game="tug-of-war"] .tug-opt.is-wrong .tug-optletter{ background:#dc2626; }
    [data-game="tug-of-war"] .tug-opt:disabled{ cursor:default; }
    [data-game="tug-of-war"] .tug-idrow{ display:flex; gap:10px; }
    [data-game="tug-of-war"] .tug-idinput{ flex:1 1 auto; border:1.5px solid var(--tug-line); border-radius:10px; padding:12px 14px; font-size:15px; }
    [data-game="tug-of-war"] .tug-idsubmit{ border:0; background:var(--tug-blue); color:#fff; font-weight:800; border-radius:10px; padding:0 18px; cursor:pointer; }
    [data-game="tug-of-war"] .tug-hint{ text-align:center; color:#8a97a8; font-size:12px; margin-top:10px; }
    [data-game="tug-of-war"] .tug-feedback{ text-align:center; font-size:13.5px; font-weight:700; margin-top:10px; border-radius:10px; padding:8px 12px; }
    [data-game="tug-of-war"] .tug-feedback.ok{ background:#e9faef; color:#137a37; }
    [data-game="tug-of-war"] .tug-feedback.bad{ background:#fdecea; color:#b3261a; }

    /* bottom roster */
    [data-game="tug-of-war"] .tug-bench{ display:grid; grid-template-columns:1fr auto 1fr; gap:12px; align-items:center; padding:12px 18px 18px; }
    [data-game="tug-of-war"] .tug-benchside{ display:flex; align-items:center; gap:8px; padding:8px 10px; border-radius:14px; }
    [data-game="tug-of-war"] .tug-benchside.a{ background:var(--tug-blue-soft); justify-content:flex-start; }
    [data-game="tug-of-war"] .tug-benchside.b{ background:var(--tug-orange-soft); justify-content:flex-end; }
    [data-game="tug-of-war"] .tug-shield{ width:34px; height:34px; border-radius:9px; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:900; font-size:15px; flex:0 0 auto; }
    [data-game="tug-of-war"] .tug-shield.a{ background:var(--tug-blue); }
    [data-game="tug-of-war"] .tug-shield.b{ background:var(--tug-orange); }
    [data-game="tug-of-war"] .tug-players{ display:flex; gap:10px; flex-wrap:wrap; }
    [data-game="tug-of-war"] .tug-player{ text-align:center; width:60px; }
    [data-game="tug-of-war"] .tug-avatar{ position:relative; width:42px; height:42px; margin:0 auto 3px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:14px; color:#fff; border:2px solid #fff; box-shadow:0 2px 6px rgba(0,0,0,.15); }
    [data-game="tug-of-war"] .tug-avatar.a{ background:var(--tug-blue); }
    [data-game="tug-of-war"] .tug-avatar.b{ background:var(--tug-orange); }
    [data-game="tug-of-war"] .tug-dot{ position:absolute; right:-1px; bottom:-1px; width:12px; height:12px; border-radius:50%; border:2px solid #fff; }
    [data-game="tug-of-war"] .tug-dot.ready{ background:#3b82f6; } [data-game="tug-of-war"] .tug-dot.thinking{ background:#f59e0b; } [data-game="tug-of-war"] .tug-dot.answered{ background:#22c55e; }
    [data-game="tug-of-war"] .tug-pname{ font-size:10px; font-weight:700; color:var(--tug-ink); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    [data-game="tug-of-war"] .tug-pstatus{ font-size:9px; font-weight:700; text-transform:capitalize; }
    [data-game="tug-of-war"] .tug-pstatus.ready{ color:#3b82f6; } [data-game="tug-of-war"] .tug-pstatus.thinking{ color:#d97706; } [data-game="tug-of-war"] .tug-pstatus.answered{ color:#16a34a; }
    [data-game="tug-of-war"] .tug-live{ text-align:center; background:#0f2545; color:#fff; border-radius:12px; padding:8px 14px; font-size:12px; font-weight:800; }
    [data-game="tug-of-war"] .tug-live small{ display:block; font-size:10px; font-weight:600; opacity:.75; }

    /* end */
    [data-game="tug-of-war"] .tug-end{ max-width:520px; margin:0 auto; text-align:center; padding:34px 24px; }
    [data-game="tug-of-war"] .tug-end-emoji{ font-size:52px; }
    [data-game="tug-of-war"] .tug-end-title{ font-size:26px; font-weight:900; color:var(--tug-ink); margin:8px 0 4px; }
    [data-game="tug-of-war"] .tug-end-detail{ color:#586a82; font-size:14px; margin-bottom:16px; }
    [data-game="tug-of-war"] .tug-end-scores{ display:flex; justify-content:center; gap:16px; margin-bottom:20px; }
    [data-game="tug-of-war"] .tug-end-score{ border-radius:14px; padding:14px 22px; color:#fff; min-width:130px; }
    [data-game="tug-of-war"] .tug-end-score.a{ background:linear-gradient(160deg,var(--tug-blue),var(--tug-blue-deep)); }
    [data-game="tug-of-war"] .tug-end-score.b{ background:linear-gradient(160deg,var(--tug-orange),var(--tug-orange-deep)); }
    [data-game="tug-of-war"] .tug-end-score b{ display:block; font-size:30px; }

    @media (max-width:720px){
        [data-game="tug-of-war"] .tug-arena{ grid-template-columns:1fr; }
        [data-game="tug-of-war"] .tug-opts{ grid-template-columns:1fr; }
        [data-game="tug-of-war"] .tug-bench{ grid-template-columns:1fr; }
    }
</style>
@endverbatim

    {{-- ============ CONFIG SCREEN — shared component (Constitution §11B) ============ --}}
    @php $tugIsTeacher = strtolower((string) (auth()->user()->role ?? '')) === 'teacher'; @endphp
    <div id="tugConfig">
        <x-gamified-configuration
            title="Quiz Tug-of-War"
            subtitle="Two sides, one rope — every correct answer pulls it your way."
            icon="⚔️"
            :is-teacher="$tugIsTeacher"
            :items="[5, 10, 15]"
            :items-default="10"
            :types="['mcq', 'identification']"
            :difficulty="true"
            :modes="['solo', 'opponent', 'team']"
            mode-default="team"
            :section="true"
            :scoring="true"
            start-label="Start now">

            {{-- Per-game extras (dropped into the component's slot). --}}
            {{-- Opponent: pick one classmate --}}
            <div id="tugOpponentWrap" class="tug-teamwrap tug-hidden">
                <div class="tug-teamwrap-title">Choose your opponent</div>
                <select id="tugOpponent" class="tug-select"><option value="">Loading classmates…</option></select>
            </div>

            {{-- Team: build two teams from the class roster --}}
            <div id="tugTeamWrap" class="tug-teamwrap tug-hidden">
                <div class="tug-teamwrap-title">Build the teams — assign each student</div>
                <div class="tug-field" style="margin-bottom:10px;">
                    <select id="tugClass" class="tug-select"></select>
                </div>
                <div id="tugRoster" class="tug-roster"><div class="tug-empty">Loading classmates…</div></div>
            </div>
        </x-gamified-configuration>
    </div>

    {{-- ============ GAME SCREEN ============ --}}
    <div id="tugGame" class="tug-card tug-stage tug-hidden">
        <div class="tug-top">
            <div class="tug-top-title">QUIZ TUG-OF-WAR</div>
            <div class="tug-top-mid">
                <span id="tugRound">Round 1</span>
                <span class="tug-pill"><span>&#9201;</span><span id="tugTimer">0:20</span></span>
            </div>
            <div class="tug-top-mid">
                <span class="tug-pill"><span>&#128218;</span><span id="tugMeta">Practice</span></span>
                <span class="tug-pill tug-code">#<span id="tugCode">------</span></span>
            </div>
        </div>

        <div class="tug-arena">
            <div class="tug-team a">
                <div class="tug-mascot">E</div>
                <div id="tugALabel" class="tug-team-label">Team Einstein</div>
                <div class="tug-team-score"><span id="tugAScore">0</span> <small>pts</small></div>
                <div class="tug-team-correct"><span id="tugACorrect">0</span> correct</div>
            </div>

            <div>
                <div class="tug-rope">
                    {{-- Team mascots hauling the rope from each end — pure inline
                         SVG (project-drawn, no third-party art). The B puller is
                         the same figure mirrored via CSS. --}}
                    <svg class="tug-puller a" viewBox="0 0 64 64" aria-hidden="true">
                        <path d="M20 50 L10 60" stroke="#0f2545" stroke-width="5" stroke-linecap="round"/>
                        <path d="M28 53 L24 62" stroke="#0f2545" stroke-width="5" stroke-linecap="round"/>
                        <path d="M40 30 Q54 29 63 31" stroke="#0f2545" stroke-width="6" stroke-linecap="round" fill="none"/>
                        <path d="M40 40 Q54 37 63 35" stroke="#0f2545" stroke-width="6" stroke-linecap="round" fill="none"/>
                        <circle cx="26" cy="34" r="19" fill="var(--tug-blue)" stroke="#fff" stroke-width="2.5"/>
                        <path d="M12 20 L18 26 M40 20 L34 26" stroke="var(--tug-blue)" stroke-width="7" stroke-linecap="round"/>
                        <ellipse cx="30" cy="42" rx="9" ry="6" fill="rgba(255,255,255,.35)"/>
                        <circle cx="29" cy="29" r="4" fill="#fff"/><circle cx="30.5" cy="29" r="2" fill="#12203a"/>
                        <circle cx="39" cy="29" r="4" fill="#fff"/><circle cx="40.5" cy="29" r="2" fill="#12203a"/>
                        <path d="M31 37 Q35 40 39 37" stroke="#12203a" stroke-width="2" fill="none" stroke-linecap="round"/>
                        <circle cx="60" cy="31" r="4.5" fill="#0f2545" stroke="#ffd86b" stroke-width="1.5"/>
                        <circle cx="60" cy="35" r="4.5" fill="#0f2545" stroke="#ffd86b" stroke-width="1.5"/>
                    </svg>
                    <svg class="tug-puller b" viewBox="0 0 64 64" aria-hidden="true">
                        {{-- Mirrored in-viewBox (not via CSS) so the box stays put:
                             hands land at x≈4 (8%), body leans toward the orange side. --}}
                        <g transform="translate(64,0) scale(-1,1)">
                        <path d="M20 50 L10 60" stroke="#7a3d10" stroke-width="5" stroke-linecap="round"/>
                        <path d="M28 53 L24 62" stroke="#7a3d10" stroke-width="5" stroke-linecap="round"/>
                        <path d="M40 30 Q54 29 63 31" stroke="#7a3d10" stroke-width="6" stroke-linecap="round" fill="none"/>
                        <path d="M40 40 Q54 37 63 35" stroke="#7a3d10" stroke-width="6" stroke-linecap="round" fill="none"/>
                        <circle cx="26" cy="34" r="19" fill="var(--tug-orange)" stroke="#fff" stroke-width="2.5"/>
                        <path d="M12 20 L18 26 M40 20 L34 26" stroke="var(--tug-orange)" stroke-width="7" stroke-linecap="round"/>
                        <ellipse cx="30" cy="42" rx="9" ry="6" fill="rgba(255,255,255,.35)"/>
                        <circle cx="29" cy="29" r="4" fill="#fff"/><circle cx="30.5" cy="29" r="2" fill="#3a1c08"/>
                        <circle cx="39" cy="29" r="4" fill="#fff"/><circle cx="40.5" cy="29" r="2" fill="#3a1c08"/>
                        <path d="M31 37 Q35 40 39 37" stroke="#3a1c08" stroke-width="2" fill="none" stroke-linecap="round"/>
                        <circle cx="60" cy="31" r="4.5" fill="#7a3d10" stroke="#ffd86b" stroke-width="1.5"/>
                        <circle cx="60" cy="35" r="4.5" fill="#7a3d10" stroke="#ffd86b" stroke-width="1.5"/>
                        </g>
                    </svg>
                    <div class="tug-rope-line"></div>
                    <div id="tugNodes" class="tug-nodes"></div>
                    <div id="tugKnot" class="tug-knot" style="left:50%;"></div>
                </div>
                <div class="tug-finish"><span class="a">&#9209; Blue finish</span><span class="b">Orange finish &#9209;</span></div>
            </div>

            <div class="tug-team b">
                <div class="tug-mascot">N</div>
                <div id="tugBLabel" class="tug-team-label">Team Newton</div>
                <div class="tug-team-score"><span id="tugBScore">0</span> <small>pts</small></div>
                <div class="tug-team-correct"><span id="tugBCorrect">0</span> correct</div>
            </div>
        </div>

        <div class="tug-qcard">
            <div class="tug-qnum" id="tugQNum">Question 1</div>
            <div class="tug-qtext" id="tugQuestion">…</div>
            <div class="tug-arm" id="tugArm">
                <button type="button" class="tug-armbtn a" id="tugArmA">Team Einstein buzzed</button>
                <button type="button" class="tug-armbtn b" id="tugArmB">Team Newton buzzed</button>
            </div>
            <div id="tugOptions" class="tug-opts"></div>
            <div id="tugFeedback" class="tug-feedback tug-hidden"></div>
            <div class="tug-hint">Anyone who knows the answer buzzes in — the fastest correct answer pulls the rope. <a href="#" id="tugSkip">Skip question</a></div>
        </div>

        <div class="tug-bench">
            <div class="tug-benchside a">
                <div class="tug-shield a">E</div>
                <div id="tugBenchA" class="tug-players"></div>
            </div>
            <div class="tug-live">Live Classroom Game<small>Host Controlled</small></div>
            <div class="tug-benchside b">
                <div id="tugBenchB" class="tug-players"></div>
                <div class="tug-shield b">N</div>
            </div>
        </div>
    </div>

    {{-- ============ END SCREEN ============ --}}
    <div id="tugEnd" class="tug-card tug-stage tug-hidden">
        <div class="tug-end">
            <div class="tug-end-emoji" id="tugEndEmoji">&#127942;</div>
            <div class="tug-end-title" id="tugEndTitle">Team Einstein wins!</div>
            <div class="tug-end-detail" id="tugEndDetail"></div>
            <div class="tug-end-scores">
                <div class="tug-end-score a"><span id="tugEndALabel">Team Einstein</span><b id="tugEndAScore">0</b></div>
                <div class="tug-end-score b"><span id="tugEndBLabel">Team Newton</span><b id="tugEndBScore">0</b></div>
            </div>
            <button type="button" id="tugAgainBtn" class="tug-start" style="max-width:260px;">Play again</button>
        </div>
    </div>
</div>

<script>
(function () {
    const BASE = @json(route('tools.games.sessions.store')); // /tools/games/api/sessions
    const CSRF = @json(csrf_token());
    const el = (id) => document.getElementById(id);
    const LETTERS = ['A', 'B', 'C', 'D', 'E', 'F'];

    // Inside the catalog iframe → allow the stage to widen past the readable cap.
    if (window.self !== window.top) document.querySelector('[data-game="tug-of-war"]').classList.add('tug-embedded');

    let state = null;        // latest server snapshot
    let armedTeam = null;    // single-screen host relay: which team buzzed
    let lockedTeams = {};    // teams that already (wrongly) answered the current question
    let curSeq = 0;
    let rosterCache = null;
    let timer = null;
    let scoring = null;      // teacher scoring (winner/non-winner %) — shown on the end screen only

    // ---------- helpers ----------
    async function api(path, opts = {}) {
        const res = await fetch(BASE + path, {
            method: opts.method || 'GET',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: opts.body ? JSON.stringify(opts.body) : undefined,
        });
        let data = null;
        try { data = await res.json(); } catch (e) {}
        if (!res.ok) throw new Error((data && data.message) || ('Request failed (' + res.status + ')'));
        return data;
    }
    const esc = (s) => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const initials = (name) => (String(name).trim().split(/\s+/).map(w => w[0] || '').slice(0, 2).join('') || '?').toUpperCase();
    function show(screen) {
        el('tugConfig').classList.toggle('tug-hidden', screen !== 'config');
        el('tugGame').classList.toggle('tug-hidden', screen !== 'game');
        el('tugEnd').classList.toggle('tug-hidden', screen !== 'end');
    }

    // ---------- config screen (driven by the shared gamified-configuration component) ----------
    // The shared component owns the scope box + item/type/difficulty/mode
    // selects; this game only supplies the opponent picker / team builder and
    // reacts to the component's mode + start events.
    function applyMode(mode) {
        el('tugOpponentWrap').classList.toggle('tug-hidden', mode !== 'opponent');
        el('tugTeamWrap').classList.toggle('tug-hidden', mode !== 'team');
        if (mode === 'opponent' || mode === 'team') loadRoster();
    }
    document.addEventListener('gamified-config:mode', (e) => applyMode(e.detail.mode));

    async function loadRoster() {
        if (!rosterCache) {
            try { rosterCache = await api('/classmates'); }
            catch (e) { rosterCache = { classes: [] }; }
        }
        const classes = rosterCache.classes || [];
        // Opponent: flat list of every classmate.
        const opp = el('tugOpponent');
        const all = [];
        classes.forEach(c => (c.students || []).forEach(s => { if (!all.find(x => x.id === s.id)) all.push(s); }));
        opp.innerHTML = all.length
            ? '<option value="">Select a classmate…</option>' + all.map(s => '<option value="' + s.id + '">' + esc(s.name) + '</option>').join('')
            : '<option value="">No classmates found</option>';
        // Team: class picker + roster with A/B toggles.
        const cls = el('tugClass');
        cls.innerHTML = classes.length
            ? classes.map(c => '<option value="' + c.id + '">' + esc(c.label) + '</option>').join('')
            : '<option value="">No classes found</option>';
        cls.onchange = renderTeamRoster;
        renderTeamRoster();
    }

    const teamPick = {}; // student_id -> 'A' | 'B'
    function renderTeamRoster() {
        const classes = (rosterCache && rosterCache.classes) || [];
        const c = classes.find(x => String(x.id) === String(el('tugClass').value)) || classes[0];
        const students = (c && c.students) || [];
        const box = el('tugRoster');
        if (!students.length) { box.innerHTML = '<div class="tug-empty">No students in this class yet.</div>'; return; }
        box.innerHTML = students.map(s =>
            '<div class="tug-rrow"><span class="tug-rname">' + esc(s.name) + '</span>'
            + '<span class="tug-teambtns" data-sid="' + s.id + '">'
            + '<button type="button" class="tug-tb btn-a' + (teamPick[s.id] === 'A' ? ' is-a' : '') + '">Einstein</button>'
            + '<button type="button" class="tug-tb btn-b' + (teamPick[s.id] === 'B' ? ' is-b' : '') + '">Newton</button>'
            + '</span></div>'
        ).join('');
        box.querySelectorAll('.tug-teambtns').forEach(g => {
            const sid = g.dataset.sid;
            g.querySelector('.btn-a').addEventListener('click', () => { teamPick[sid] = teamPick[sid] === 'A' ? undefined : 'A'; renderTeamRoster(); });
            g.querySelector('.btn-b').addEventListener('click', () => { teamPick[sid] = teamPick[sid] === 'B' ? undefined : 'B'; renderTeamRoster(); });
        });
    }

    function gatherPlayers(mode) {
        if (mode === 'opponent') {
            const oid = el('tugOpponent').value;
            return oid ? [{ student_id: parseInt(oid, 10), team: 'B' }] : [];
        }
        if (mode === 'team') {
            return Object.keys(teamPick).filter(k => teamPick[k]).map(k => ({ student_id: parseInt(k, 10), team: teamPick[k] }));
        }
        return [];
    }

    document.addEventListener('gamified-config:start', async (e) => {
        const cfg = e.detail;                       // {items, type, difficulty, mode, section, scoring}
        scoring = cfg.scoring || null;
        const mode = cfg.mode;
        const players = gatherPlayers(mode);
        if (mode === 'opponent' && !players.length) return window.GamifiedConfig.error('Pick an opponent first.');
        if (mode === 'team' && (players.filter(p => p.team === 'A').length === 0 || players.filter(p => p.team === 'B').length === 0)) {
            return window.GamifiedConfig.error('Assign at least one student to each team.');
        }
        window.GamifiedConfig.loading(true, 'Setting up…');
        try {
            const scope = window.GameScope || {};
            const body = { mode, question_type: cfg.type, difficulty: cfg.difficulty, question_count: cfg.items, players };
            ['subject_id', 'topic_id', 'lesson_id', 'competency_id', 'academic_level_id'].forEach(k => { if (scope[k]) body[k] = scope[k]; });
            const created = await api('/', { method: 'POST', body });
            state = await api('/' + created.id + '/start', { method: 'POST' });
            armedTeam = null; lockedTeams = {}; curSeq = 0;
            renderGame();
            show('game');
        } catch (err) {
            window.GamifiedConfig.error(err.message);
        } finally {
            window.GamifiedConfig.loading(false);
        }
    });

    // Reveal the default mode's extras once the shared config has initialised.
    applyMode((window.GamifiedConfig && window.GamifiedConfig.read().mode) || 'team');

    // ---------- game screen ----------
    function renderGame() {
        if (!state) return;
        if (state.status === 'ended') return renderEnd();

        el('tugMeta').textContent = (window.GameScope && window.GameScope.summary && window.GameScope.summary()) || 'Practice';
        el('tugCode').textContent = state.join_code;
        el('tugRound').textContent = 'Round ' + state.current_sequence + ' of ' + state.question_count;
        el('tugALabel').textContent = state.teams.A.label;
        el('tugBLabel').textContent = state.teams.B.label;
        el('tugAScore').textContent = state.teams.A.score;
        el('tugBScore').textContent = state.teams.B.score;
        el('tugACorrect').textContent = state.teams.A.correct;
        el('tugBCorrect').textContent = state.teams.B.correct;

        renderRope();
        renderBench();

        // New question → reset arming + locks.
        if (state.current_sequence !== curSeq) { curSeq = state.current_sequence; armedTeam = (state.mode === 'solo') ? 'A' : null; lockedTeams = {}; }
        renderQuestion();
        startTimer();
    }

    function renderRope() {
        const finish = state.finish_line, pos = state.rope_position;
        const total = finish * 2 + 1;
        let nodes = '';
        for (let i = 0; i < total; i++) {
            const cls = i < finish ? 'a' : (i > finish ? 'b' : '');
            nodes += '<div class="tug-node ' + cls + '"></div>';
        }
        el('tugNodes').innerHTML = nodes;
        // pos>0 (Team A/blue) pulls the knot LEFT toward the blue finish.
        const clamped = Math.max(-finish, Math.min(finish, pos));
        const pct = ((finish - clamped) / (total - 1)) * 100;
        el('tugKnot').style.left = pct + '%';
    }

    function renderQuestion() {
        const q = state.question;
        const arm = el('tugArm');
        arm.style.display = (state.mode === 'solo') ? 'none' : 'flex';
        el('tugArmA').classList.toggle('is-armed', armedTeam === 'A');
        el('tugArmB').classList.toggle('is-armed', armedTeam === 'B');
        el('tugArmA').disabled = !!lockedTeams['A'];
        el('tugArmB').disabled = !!lockedTeams['B'];
        el('tugFeedback').classList.add('tug-hidden');

        if (!q) { el('tugQuestion').textContent = 'Waiting…'; el('tugOptions').innerHTML = ''; return; }
        el('tugQNum').textContent = 'Question ' + state.current_sequence;
        el('tugQuestion').textContent = q.question;

        if (q.choices && q.choices.length) {
            el('tugOptions').innerHTML = q.choices.map((c, i) =>
                '<button type="button" class="tug-opt" data-idx="' + i + '">'
                + '<span class="tug-optletter">' + (LETTERS[i] || '?') + '</span>'
                + '<span class="tug-opttext">' + esc(c) + '</span></button>'
            ).join('');
            el('tugOptions').querySelectorAll('.tug-opt').forEach(b =>
                b.addEventListener('click', () => submit({ choice_index: parseInt(b.dataset.idx, 10) })));
            // Fresh question: hold hover styling until the pointer moves, so the
            // button rendered under the resting cursor doesn't look pre-selected.
            el('tugOptions').classList.add('tug-nohover');
            document.addEventListener('mousemove', function unlock() {
                el('tugOptions').classList.remove('tug-nohover');
                document.removeEventListener('mousemove', unlock);
            });
        } else {
            // Identification: host types the buzzing side's spoken answer.
            el('tugOptions').innerHTML =
                '<div class="tug-idrow" style="grid-column:1/-1;">'
                + '<input type="text" id="tugIdInput" class="tug-idinput" placeholder="Type the answer…">'
                + '<button type="button" id="tugIdSubmit" class="tug-idsubmit">Submit</button></div>';
            el('tugIdSubmit').addEventListener('click', () => submit({ answer_text: el('tugIdInput').value }));
        }
    }

    el('tugArmA').addEventListener('click', () => { if (!lockedTeams['A']) { armedTeam = 'A'; renderQuestion(); } });
    el('tugArmB').addEventListener('click', () => { if (!lockedTeams['B']) { armedTeam = 'B'; renderQuestion(); } });

    async function submit(payload) {
        if (state.mode !== 'solo' && !armedTeam) return flash('Tap the team that buzzed first.', 'bad');
        const team = armedTeam || 'A';
        try {
            const r = await api('/' + state.id + '/answer', { method: 'POST', body: Object.assign({ team }, payload) });
            if (!r.ok) return flash(r.reason || 'That answer could not be counted.', 'bad');
            if (r.correct) {
                flash(state.teams[team].label + ' scores — rope pulled!', 'ok');
                state = r.state;
                if (state.status === 'ended') return setTimeout(renderEnd, 900);
                setTimeout(renderGame, 900);
            } else {
                flash('Wrong! ' + (state.mode === 'solo' ? 'The CPU takes that round.' : 'The other team can steal it.'), 'bad');
                if (state.mode === 'solo') { // consume the question, no pull
                    state = (await api('/' + state.id + '/advance', { method: 'POST' }));
                    if (state.status === 'ended') return setTimeout(renderEnd, 900);
                    setTimeout(renderGame, 900);
                } else {
                    lockedTeams[team] = true; armedTeam = null; state = r.state; renderQuestion();
                }
            }
        } catch (e) { flash(e.message, 'bad'); }
    }

    el('tugSkip').addEventListener('click', async (e) => {
        e.preventDefault();
        try { state = await api('/' + state.id + '/advance', { method: 'POST' }); (state.status === 'ended') ? renderEnd() : renderGame(); }
        catch (err) { flash(err.message, 'bad'); }
    });

    function flash(msg, kind) {
        const fb = el('tugFeedback');
        fb.textContent = msg; fb.className = 'tug-feedback ' + kind;
    }

    function renderBench() {
        const A = [], B = [];
        (state.players || []).forEach(p => { if (p.team === 'A') A.push(p); else if (p.team === 'B') B.push(p); });
        el('tugBenchA').innerHTML = A.map(p => playerChip(p, 'a')).join('') || '<span class="tug-empty">—</span>';
        el('tugBenchB').innerHTML = B.map(p => playerChip(p, 'b')).join('') || '<span class="tug-empty">—</span>';
    }
    function playerChip(p, side) {
        return '<div class="tug-player"><div class="tug-avatar ' + side + '">' + esc(initials(p.display_name))
            + '<span class="tug-dot ' + p.status + '"></span></div>'
            + '<div class="tug-pname">' + esc(p.display_name.split(' ')[0]) + '</div>'
            + '<div class="tug-pstatus ' + p.status + '">' + p.status + '</div></div>';
    }

    function startTimer() {
        if (timer) clearInterval(timer);
        const ends = state.question_ends_at ? new Date(state.question_ends_at).getTime() : null;
        function tick() {
            if (!ends) { el('tugTimer').textContent = '--'; return; }
            let left = Math.max(0, Math.round((ends - Date.now()) / 1000));
            el('tugTimer').textContent = Math.floor(left / 60) + ':' + String(left % 60).padStart(2, '0');
            if (left <= 0) { clearInterval(timer); }
        }
        tick(); timer = setInterval(tick, 1000);
    }

    // ---------- end screen ----------
    function renderEnd() {
        if (timer) clearInterval(timer);
        show('end');
        const w = state.winner;
        const a = state.teams.A, b = state.teams.B;
        el('tugEndEmoji').innerHTML = w === 'draw' ? '&#129309;' : '&#127942;';
        el('tugEndTitle').textContent = w === 'draw' ? "It's a draw!" : ((w === 'A' ? a.label : b.label) + ' wins!');
        let detail = 'Final pull: ' + a.correct + ' vs ' + b.correct + ' correct.';
        // Teacher scoring — recorded as a recitation (display only for now).
        if (scoring && w !== 'draw' && (state.mode === 'opponent' || state.mode === 'team')) {
            const winLabel = w === 'A' ? a.label : b.label;
            const loseLabel = w === 'A' ? b.label : a.label;
            detail += '  •  Recitation: ' + winLabel + ' ' + scoring.winner_pct + '%, ' + loseLabel + ' ' + scoring.loser_pct + '%.';
        }
        el('tugEndDetail').textContent = detail;
        el('tugEndALabel').textContent = a.label; el('tugEndBLabel').textContent = b.label;
        el('tugEndAScore').textContent = a.score; el('tugEndBScore').textContent = b.score;
    }
    el('tugAgainBtn').addEventListener('click', () => { state = null; armedTeam = null; show('config'); });
})();
</script>
