/* ============================================================================
 * Quiz Speed Dash — shared game engine (vanilla JS + CSS animation).
 *
 * One engine, three deliveries: the graded assessment screen, catalog
 * practice, and (later) live races — each supplies an ADAPTER, so the engine
 * itself never knows a correct answer in graded mode. It renders the track,
 * gates, HUD and overlays, collects the pick, and asks the adapter to grade.
 *
 * adapter contract:
 *   count()                → total questions, or null (endless practice)
 *   get(i)                 → question {question_id, text, choices:[{id,text}],
 *                            answered?, was_correct?, chosen_id?} or
 *                            Promise thereof; null = no more questions
 *   submit(q, choiceId, ms)→ Promise<{correct, points, game?, correct_choice_id?,
 *                            explanation?, duplicate?, expired?, redirect?}>
 *   finish(summary)        → run is complete (navigate / show summary)
 *   meters?                → 'server' (trust submit().game) | 'client' (engine keeps them)
 *
 * options: root, meta{subject, level, studentName, avatarUrl}, settings
 * {startingLives, instantSubmit, powerupsEnabled}, game (initial meters),
 * timerSeconds, adapter.
 * ========================================================================== */
(function () {
    'use strict';

    var LETTERS = ['A', 'B', 'C', 'D', 'E'];

    // ------------------------------------------------------------- helpers
    function h(tag, cls, html) {
        var el = document.createElement(tag);
        if (cls) el.className = cls;
        if (html !== undefined) el.innerHTML = html;
        return el;
    }
    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
    function pref(key, fallback) {
        try { var v = localStorage.getItem(key); return v === null ? fallback : v === '1'; }
        catch (e) { return fallback; }
    }
    function setPref(key, on) {
        try { localStorage.setItem(key, on ? '1' : '0'); } catch (e) { /* private mode */ }
    }

    // Tiny WebAudio blips — generated, no assets, no copyright.
    function makeAudio() {
        var ctx = null, muted = pref('sd_muted', false);
        function ensure() {
            if (!ctx) {
                var AC = window.AudioContext || window.webkitAudioContext;
                if (AC) ctx = new AC();
            }
            return ctx;
        }
        function tone(freq, dur, type, gain, when) {
            if (muted || !ensure()) return;
            try {
                var t = ctx.currentTime + (when || 0);
                var o = ctx.createOscillator(), g = ctx.createGain();
                o.type = type || 'sine'; o.frequency.value = freq;
                g.gain.setValueAtTime(gain || 0.08, t);
                g.gain.exponentialRampToValueAtTime(0.0001, t + dur);
                o.connect(g).connect(ctx.destination);
                o.start(t); o.stop(t + dur + 0.02);
            } catch (e) { /* audio is best-effort */ }
        }
        return {
            isMuted: function () { return muted; },
            setMuted: function (on) { muted = on; setPref('sd_muted', on); },
            correct: function () { tone(660, .12, 'triangle'); tone(880, .16, 'triangle', .08, .09); },
            wrong: function () { tone(180, .28, 'sawtooth', .06); },
            streak: function () { tone(660, .1, 'square', .05); tone(990, .12, 'square', .05, .08); tone(1320, .16, 'square', .05, .16); },
            boost: function () { tone(440, .1, 'sine'); tone(660, .1, 'sine', .08, .07); tone(990, .2, 'sine', .08, .14); },
            finish: function () { [523, 659, 784, 1047].forEach(function (f, i) { tone(f, .22, 'triangle', .08, i * .13); }); },
            tick: function () { tone(880, .06, 'sine', .04); }
        };
    }

    // Campus scenery matching the game's key art: window-grid buildings, campus
    // banners, a chalkboard, trees, stacked books, and floating academic props.
    // All project-drawn inline SVG — detailed enough to read as the mockup,
    // cheap enough for school hardware (static shapes, a few float loops).
    function buildScenery() {
        var win = function (x, y, cols, rows, w, hgt, fill) {
            var out = '';
            for (var r = 0; r < rows; r++) {
                for (var c = 0; c < cols; c++) {
                    out += '<rect x="' + (x + c * (w + 9)) + '" y="' + (y + r * (hgt + 11)) + '" width="' + w + '" height="' + hgt + '" rx="2" fill="' + (((r * 7 + c * 3) % 9 === 0) ? '#ffe9a8' : fill) + '"/>';
                }
            }
            return out;
        };
        var tree = function (x, y, s) {
            return '<rect x="' + (x - 3 * s) + '" y="' + y + '" width="' + 6 * s + '" height="' + 18 * s + '" rx="2" fill="#7a5230"/>' +
                '<circle cx="' + x + '" cy="' + (y - 8 * s) + '" r="' + 16 * s + '" fill="#3f9b53"/>' +
                '<circle cx="' + (x - 11 * s) + '" cy="' + (y + 1 * s) + '" r="' + 11 * s + '" fill="#55b269"/>' +
                '<circle cx="' + (x + 11 * s) + '" cy="' + (y + 1 * s) + '" r="' + 11 * s + '" fill="#4aa85e"/>';
        };

        var scen = h('div', 'sd-scenery');

        var left = h('div', 'sd-city left');
        left.innerHTML =
            '<svg viewBox="0 0 360 600" preserveAspectRatio="xMinYMax meet" aria-hidden="true">' +
            '<rect x="118" y="60" width="112" height="540" rx="6" fill="#4f86c0"/>' +
            '<rect x="118" y="60" width="112" height="14" fill="#3a6ba3"/>' +
            win(128, 90, 4, 12, 17, 24, 'rgba(220,240,255,.85)') +
            '<rect x="6" y="170" width="106" height="430" rx="6" fill="#6ea9d8"/>' +
            win(16, 190, 4, 9, 15, 26, 'rgba(235,248,255,.9)') +
            '<rect x="236" y="200" width="118" height="400" rx="6" fill="#7fb7e3"/>' +
            win(246, 222, 4, 8, 18, 26, 'rgba(240,250,255,.9)') +
            '<line x1="174" y1="60" x2="174" y2="28" stroke="#3a6ba3" stroke-width="4"/><circle cx="174" cy="26" r="4" fill="#ffd257"/>' +
            '<rect x="140" y="120" width="70" height="170" rx="9" fill="#16305e" stroke="#7fb2ff" stroke-width="2"/>' +
            '<text x="175" y="165" text-anchor="middle" font-family="inherit" font-size="17" font-weight="800" fill="#fff">LEARN</text>' +
            '<text x="175" y="195" text-anchor="middle" font-size="17" font-weight="800" fill="#fff">EXPLORE</text>' +
            '<text x="175" y="225" text-anchor="middle" font-size="17" font-weight="800" fill="#fff">GROW</text>' +
            '<rect x="12" y="96" width="96" height="66" rx="6" fill="#1c5a44" stroke="#8a5a2b" stroke-width="5"/>' +
            '<text x="60" y="136" text-anchor="middle" font-size="24" font-style="italic" font-weight="700" fill="#f2f7ef">E=mc&#178;</text>' +
            tree(300, 540, 1.4) + tree(60, 556, 1.1) +
            '</svg>';
        scen.appendChild(left);

        var right = h('div', 'sd-city right');
        right.innerHTML =
            '<svg viewBox="0 0 360 600" preserveAspectRatio="xMaxYMax meet" aria-hidden="true">' +
            '<rect x="130" y="70" width="112" height="530" rx="6" fill="#4f86c0"/>' +
            '<rect x="130" y="70" width="112" height="14" fill="#3a6ba3"/>' +
            win(140, 100, 4, 11, 17, 24, 'rgba(220,240,255,.85)') +
            '<rect x="248" y="150" width="106" height="450" rx="6" fill="#6ea9d8"/>' +
            win(258, 172, 4, 9, 15, 26, 'rgba(235,248,255,.9)') +
            '<rect x="10" y="210" width="112" height="390" rx="6" fill="#7fb7e3"/>' +
            win(20, 232, 4, 7, 18, 26, 'rgba(240,250,255,.9)') +
            '<rect x="150" y="130" width="74" height="200" rx="9" fill="#16305e" stroke="#7fb2ff" stroke-width="2"/>' +
            '<path d="M167 158 L187 150 L207 158 L187 166 Z" fill="#0d1e3e" stroke="#ffd257" stroke-width="2"/>' +
            '<line x1="205" y1="160" x2="205" y2="172" stroke="#ffd257" stroke-width="2"/><circle cx="205" cy="174" r="2.5" fill="#ffd257"/>' +
            '<text x="187" y="200" text-anchor="middle" font-size="14" font-weight="800" fill="#fff">KNOWLEDGE</text>' +
            '<text x="187" y="222" text-anchor="middle" font-size="14" font-weight="800" fill="#fff">IS YOUR</text>' +
            '<text x="187" y="244" text-anchor="middle" font-size="14" font-weight="800" fill="#fff">SUPERPOWER</text>' +
            '<g transform="rotate(-4 300 560)">' +
            '<rect x="248" y="548" width="104" height="22" rx="4" fill="#1c5a44"/><text x="300" y="564" text-anchor="middle" font-size="13" font-weight="800" fill="#e8f3ec">SCIENCE</text>' +
            '<rect x="254" y="524" width="96" height="22" rx="4" fill="#8a3b2a"/><text x="302" y="540" text-anchor="middle" font-size="12" font-weight="800" fill="#ffe4d8">ASTRONOMY</text>' +
            '<rect x="250" y="500" width="100" height="22" rx="4" fill="#274d80"/><text x="300" y="516" text-anchor="middle" font-size="11" font-weight="800" fill="#dcebff">EARTH &amp; SPACE</text>' +
            '</g>' +
            tree(60, 560, 1.3) +
            '</svg>';
        scen.appendChild(right);

        var props = [
            ['p1', 'left:23%;top:15%;width:56px',
                '<svg viewBox="0 0 60 60"><circle cx="30" cy="30" r="6" fill="#ffd257"/><ellipse cx="30" cy="30" rx="24" ry="10" fill="none" stroke="#e8f4ff" stroke-width="3"/><ellipse cx="30" cy="30" rx="24" ry="10" fill="none" stroke="#bfe3ff" stroke-width="3" transform="rotate(60 30 30)"/><ellipse cx="30" cy="30" rx="24" ry="10" fill="none" stroke="#9fd4ff" stroke-width="3" transform="rotate(120 30 30)"/></svg>'],
            ['p2', 'right:22%;top:11%;width:74px',
                '<svg viewBox="0 0 80 60"><circle cx="40" cy="30" r="17" fill="#d9a066"/><path d="M40 13 A17 17 0 0 1 40 47 Z" fill="#c08447"/><ellipse cx="40" cy="32" rx="34" ry="9" fill="none" stroke="#f0cfa0" stroke-width="4"/></svg>'],
            ['p3', 'right:31%;top:30%;width:48px',
                '<svg viewBox="0 0 50 50"><line x1="12" y1="14" x2="34" y2="24" stroke="#4f86c0" stroke-width="3"/><line x1="34" y1="24" x2="18" y2="40" stroke="#4f86c0" stroke-width="3"/><circle cx="12" cy="14" r="7" fill="#2fa9bd"/><circle cx="34" cy="24" r="9" fill="#1f5fd0"/><circle cx="18" cy="40" r="6" fill="#7ac142"/></svg>'],
            ['p4', 'left:31%;top:33%;width:34px',
                '<svg viewBox="0 0 40 40"><path d="M20 2l4.6 10.1L36 13.4l-8.2 7.6L30 32 20 26.4 10 32l2.2-11L4 13.4l11.4-1.3z" fill="#ffd257" stroke="#a87a00"/></svg>'],
        ];
        props.forEach(function (p) {
            var el = h('div', 'sd-prop ' + p[0]);
            el.style.cssText = p[1];
            el.innerHTML = p[2];
            scen.appendChild(el);
        });

        return scen;
    }

    function SpeedDash(opts) {
        var root = opts.root;
        var adapter = opts.adapter;
        var settings = Object.assign({ startingLives: 3, instantSubmit: true, powerupsEnabled: true }, opts.settings || {});
        var meta = opts.meta || {};
        var serverMeters = (adapter.meters || 'client') === 'server';
        var audio = makeAudio();

        var prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var reduced = pref('sd_reduced', prefersReduced);

        // Game meters. In graded mode the server state (opts.game / submit().game)
        // is authoritative and simply displayed; in practice the engine owns them.
        var g = Object.assign({
            score: 0, hearts: settings.startingLives, shield: false, streak: 0,
            best_streak: 0, correct: 0, wrong: 0, recovery: false, boosts: 0
        }, opts.game || {});

        var total = adapter.count();          // null = endless
        var index = 0;                        // current question index
        var q = null;                         // current question object
        var picked = null;                    // choice id picked (confirm mode)
        var busy = false;                     // submit in flight
        var startedAt = 0;                    // question shown timestamp
        var finished = false;
        var timerLeft = (typeof opts.timerSeconds === 'number') ? Math.floor(opts.timerSeconds) : null;
        var timerHandle = null;

        // ------------------------------------------------------------ markup
        root.classList.add('sd-root');
        if (!reduced) root.classList.add('sd-motion'); else root.classList.add('sd-reduced');

        root.innerHTML = '';
        var sky = h('div', 'sd-sky'); root.appendChild(sky);
        root.appendChild(buildScenery());
        root.appendChild(h('div', 'sd-greenery'));

        var world = h('div', 'sd-world');
        var track = h('div', 'sd-track');
        world.appendChild(track);
        root.appendChild(world);
        var arrL = h('div', 'sd-lane-arrows left'), arrR = h('div', 'sd-lane-arrows right');
        root.appendChild(arrL); root.appendChild(arrR);

        var gates = h('div', 'sd-gates'); root.appendChild(gates);

        // Back-view runner with an articulated run cycle: arm and leg groups
        // (.rn-limb) pivot at shoulder/hip via CSS keyframes in opposite phase.
        var runner = h('div', 'sd-runner');
        runner.innerHTML =
            '<div class="sd-shield-ring"></div>' +
            '<svg class="sd-runner-svg" viewBox="0 0 100 132" aria-hidden="true">' +
            '<g class="rn-limb rn-legL">' +
            '<path d="M42 92 Q39 106 42 116" fill="none" stroke="#233043" stroke-width="11" stroke-linecap="round"/>' +
            '<ellipse cx="42" cy="121" rx="10" ry="5.5" fill="#eef3fa"/><path d="M33 121 h18" stroke="#38bdf8" stroke-width="3" stroke-linecap="round"/>' +
            '</g>' +
            '<g class="rn-limb rn-legR">' +
            '<path d="M58 92 Q61 106 58 116" fill="none" stroke="#17202e" stroke-width="11" stroke-linecap="round"/>' +
            '<ellipse cx="58" cy="121" rx="10" ry="5.5" fill="#dfe7f2"/><path d="M49 121 h18" stroke="#38bdf8" stroke-width="3" stroke-linecap="round"/>' +
            '</g>' +
            '<g class="rn-limb rn-armL">' +
            '<path d="M33 60 Q24 72 27 84" fill="none" stroke="#2b57b0" stroke-width="9" stroke-linecap="round"/>' +
            '<circle cx="27" cy="87" r="5" fill="#f3c69f"/>' +
            '</g>' +
            '<g class="rn-limb rn-armR">' +
            '<path d="M67 60 Q76 72 73 84" fill="none" stroke="#204a9c" stroke-width="9" stroke-linecap="round"/>' +
            '<circle cx="73" cy="87" r="5" fill="#f3c69f"/>' +
            '</g>' +
            '<path d="M32 56 Q50 46 68 56 L66 94 Q50 102 34 94 Z" fill="#2b57b0"/>' +
            '<path d="M35 57 Q50 66 65 57 Q60 49 50 49 Q40 49 35 57Z" fill="#9fb6dd"/>' +
            '<rect x="33" y="58" width="34" height="32" rx="10" fill="#1a3d7c"/>' +
            '<rect x="38" y="63" width="24" height="18" rx="5" fill="#2f66c4"/>' +
            '<path d="M37 58 L36 90 M63 58 L64 90" stroke="#0f2545" stroke-width="4" stroke-linecap="round"/>' +
            '<circle cx="50" cy="72" r="6.5" fill="#0d2952"/>' +
            '<circle cx="50" cy="72" r="1.8" fill="#9fdcff"/>' +
            '<ellipse cx="50" cy="72" rx="6" ry="2.4" fill="none" stroke="#9fdcff" stroke-width="1.2"/>' +
            '<ellipse cx="50" cy="72" rx="6" ry="2.4" fill="none" stroke="#9fdcff" stroke-width="1.2" transform="rotate(64 50 72)"/>' +
            '<rect x="45" y="40" width="10" height="8" fill="#f3c69f"/>' +
            '<circle cx="50" cy="28" r="14" fill="#f3c69f"/>' +
            '<path d="M36 30 Q34 12 50 11 Q66 12 64 30 Q64 20 50 19 Q36 20 36 30Z" fill="#6b4423"/>' +
            '<path d="M36 29 Q35 17 44 14 Q37 22 38 31 Z M64 29 Q65 17 56 14 Q63 22 62 31 Z" fill="#7d5230"/>' +
            '<path d="M37 25 Q40 14 50 13 Q60 14 63 25 Q57 17 50 17 Q43 17 37 25Z" fill="#5d3a1e"/>' +
            '<circle cx="36" cy="30" r="3.4" fill="#f3c69f"/><circle cx="64" cy="30" r="3.4" fill="#f3c69f"/>' +
            '</svg>' +
            '<div class="sd-runner-shadow"></div>';
        root.appendChild(runner);

        var pop = h('div', 'sd-pop'); root.appendChild(pop);

        // Reduced-motion fallback: a simple progress rail.
        var progress = h('div', 'sd-progress-track',
            '<div class="sd-progress-rail"><div class="sd-progress-fill"></div></div>' +
            '<div class="sd-progress-label"></div>');
        root.appendChild(progress);

        // -------------------------------------------------------------- HUD
        var hud = h('div', 'sd-hud');
        hud.innerHTML =
            '<div class="sd-tl">' +
              '<div class="sd-logo">Quiz<br>Speed <span class="sd-logo-dash">Dash</span></div>' +
              '<span class="sd-subject sd-card">' + esc(meta.subject || 'Practice') + (meta.level ? ' &bull; ' + esc(meta.level) : '') + '</span>' +
            '</div>' +
            '<div class="sd-tc">' +
              '<span class="sd-qcount sd-card" data-sd="qcount">Get Ready</span>' +
              '<div class="sd-qpanel" data-sd="qtext" aria-live="polite"></div>' +
            '</div>' +
            '<div class="sd-tr">' +
              '<span class="sd-card" data-sd="timerwrap" style="display:none;padding:7px 12px;font-weight:900;font-variant-numeric:tabular-nums">&#9201; <span data-sd="timer"></span></span>' +
              '<span class="sd-score sd-card"><svg width="22" height="22" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2l2.9 6.3 6.9.8-5.1 4.7 1.4 6.8L12 17.3 5.9 20.6l1.4-6.8L2.2 9.1l6.9-.8z" fill="#f5b301" stroke="#8a6400" stroke-width="1"/></svg> <span data-sd="score">0</span></span>' +
              '<span class="sd-hearts sd-card" data-sd="hearts" role="img"></span>' +
              '<button type="button" class="sd-iconbtn" data-sd="mute" title="Sound on/off" aria-label="Toggle sound">&#128266;</button>' +
              '<button type="button" class="sd-iconbtn" data-sd="pause" title="Pause" aria-label="Pause game">&#10073;&#10073;</button>' +
            '</div>' +
            '<div class="sd-streak sd-card">' +
              '<div class="sd-streak-title">STREAK</div>' +
              '<div class="sd-streak-x" data-sd="streakx">x0</div>' +
              '<div class="sd-streak-bar" data-sd="streakbar" aria-hidden="true"></div>' +
            '</div>' +
            '<div class="sd-bl sd-card">' +
              '<span class="sd-avatar"><img src="' + esc(meta.avatarUrl || '') + '" alt=""></span>' +
              '<span><span class="sd-bl-name">' + esc(meta.studentName || 'Player') + '</span><br>' +
              '<span class="sd-bl-rank" data-sd="rank">#1</span></span>' +
            '</div>' +
            '<div class="sd-bc sd-card">' +
              '<span class="sd-bolt"><svg width="16" height="16" viewBox="0 0 24 24"><path d="M13 2 4 14h6l-1 8 9-12h-6z" fill="#fff"/></svg></span>' +
              '<span class="sd-bc-label">SPEED BOOST</span>' +
              '<span class="sd-boost-bar" data-sd="boostbar" aria-hidden="true"></span>' +
            '</div>' +
            '<div class="sd-br" data-sd="positions"></div>';
        root.appendChild(hud);

        // The answer cards live inside the top-center column, directly below the
        // question panel and above the gates, so question → choices → gates read
        // as one stack.
        var choicesEl = h('div', 'sd-choices');
        hud.querySelector('.sd-tc').appendChild(choicesEl);
        var confirmBtn = h('button', 'sd-confirm', 'CONFIRM &#10148;');
        confirmBtn.type = 'button';
        root.appendChild(confirmBtn);

        var live = h('div', 'sd-sr'); live.setAttribute('aria-live', 'assertive'); root.appendChild(live);

        var overlay = h('div', 'sd-overlay'); root.appendChild(overlay);

        var $ = function (k) { return hud.querySelector('[data-sd="' + k + '"]'); };

        // streak bar segments (12) + boost segments (8)
        for (var i = 0; i < 12; i++) $('streakbar').appendChild(h('span', 'sd-streak-seg'));
        for (var j = 0; j < 8; j++) $('boostbar').appendChild(h('span', 'sd-boost-seg'));

        // ------------------------------------------------------- HUD update
        function heartsHtml() {
            var out = '', lives = settings.startingLives;
            for (var i = 0; i < lives; i++) {
                out += '<svg class="sd-heart' + (i < g.hearts ? '' : ' sd-lost') + '" viewBox="0 0 24 24" width="22" height="22">' +
                    '<path d="M12 21C7 16.6 2 12.8 2 8.6 2 5.5 4.4 3.5 7 3.5c1.9 0 3.8 1 5 2.9 1.2-1.9 3.1-2.9 5-2.9 2.6 0 5 2 5 5.1 0 4.2-5 8-10 12.4z" fill="#e34a4a" stroke="#8f1d1d" stroke-width="1.2"/></svg>';
            }
            return out;
        }
        function renderMeters() {
            $('score').textContent = String(g.score);
            $('hearts').innerHTML = heartsHtml();
            $('hearts').setAttribute('aria-label', g.hearts + ' of ' + settings.startingLives + ' hearts left');
            $('streakx').textContent = 'x' + g.streak;
            var segs = $('streakbar').children;
            for (var i = 0; i < segs.length; i++) segs[i].classList.toggle('on', i < Math.min(g.streak, segs.length));
            var bsegs = $('boostbar').children;
            var toBoost = settings.powerupsEnabled ? Math.min(g.streak % 3 === 0 && g.streak > 0 ? 8 : Math.round((g.streak % 3) / 3 * 8), 8) : 0;
            if (g.streak >= 3 && settings.powerupsEnabled) toBoost = 8;
            for (var k = 0; k < bsegs.length; k++) bsegs[k].classList.toggle('on', k < toBoost);
            runner.classList.toggle('sd-shielded', !!g.shield);
            track.classList.toggle('sd-boosted', g.streak >= 3 && !g.recovery);
            track.classList.toggle('sd-slowed', !!g.recovery);
            runner.classList.toggle('sd-boosted', g.streak >= 3 && !g.recovery);
            runner.classList.toggle('sd-slowed', !!g.recovery);
        }
        function renderProgress() {
            var done = g.correct + g.wrong;
            var pct = total ? Math.round(done / total * 100) : 0;
            progress.querySelector('.sd-progress-fill').style.width = pct + '%';
            progress.querySelector('.sd-progress-label').textContent =
                total ? (done + ' of ' + total + ' answered') : (done + ' answered');
        }
        function renderPositions(list) {
            var box = $('positions');
            box.innerHTML = '';
            (list || []).slice(0, 3).forEach(function (p, i) {
                var cell = h('div', 'sd-pos sd-card' + (p.me ? ' me' : ''));
                cell.innerHTML =
                    '<span class="sd-pos-medal' + (i === 1 ? ' s2' : i === 2 ? ' s3' : '') + '">' + (i + 1) + '</span>' +
                    '<span class="sd-pos-avatar"><img src="' + esc(p.avatarUrl || meta.avatarUrl || '') + '" alt=""></span>' +
                    '<div class="sd-pos-score">' + esc(p.score) + '</div>' +
                    '<div class="sd-pos-name">' + esc(p.name) + '</div>';
                box.appendChild(cell);
            });
            var mine = (list || []).findIndex(function (p) { return p.me; });
            $('rank').textContent = '#' + (mine >= 0 ? mine + 1 : 1);
        }

        // ------------------------------------------------------------ stars
        function scatterStars() {
            root.querySelectorAll('.sd-star').forEach(function (s) { s.remove(); });
            if (reduced) return;
            for (var i = 0; i < 4; i++) {
                var s = h('div', 'sd-star');
                s.innerHTML = '<svg viewBox="0 0 24 24"><path d="M12 2l2.9 6.3 6.9.8-5.1 4.7 1.4 6.8L12 17.3 5.9 20.6l1.4-6.8L2.2 9.1l6.9-.8z" fill="#ffd257" stroke="#a87a00"/></svg>';
                s.style.left = (30 + Math.random() * 40) + '%';
                s.style.top = (48 + Math.random() * 22) + '%';
                s.style.animationDelay = (Math.random() * 1.4) + 's';
                root.appendChild(s);
            }
        }

        // ------------------------------------------------------------ timer
        function startTimer() {
            if (timerLeft === null) return;
            $('timerwrap').style.display = '';
            var render = function () {
                var m = Math.floor(timerLeft / 60), s = timerLeft % 60;
                $('timer').textContent = m + ':' + (s < 10 ? '0' : '') + s;
            };
            render();
            timerHandle = setInterval(function () {
                timerLeft = Math.max(0, timerLeft - 1);
                render();
                if (timerLeft === 30) audio.tick();
                if (timerLeft <= 0) {
                    clearInterval(timerHandle);
                    // Server enforces the real deadline; the client just wraps up.
                    endRun(true);
                }
            }, 1000);
        }

        // ------------------------------------------------- question render
        function showQuestion(question) {
            q = question;
            picked = null;
            busy = false;
            startedAt = Date.now();
            confirmBtn.classList.remove('sd-open');

            $('qcount').textContent = total
                ? 'Question ' + (index + 1) + ' of ' + total
                : 'Question ' + (index + 1);
            $('qtext').textContent = question.text;
            live.textContent = 'Question ' + (index + 1) + ': ' + question.text;

            gates.innerHTML = '';
            choicesEl.innerHTML = '';

            var longAnswers = question.choices.some(function (c) { return String(c.text).length > 26; });
            var mobile = window.innerWidth <= 640;

            question.choices.forEach(function (c, slot) {
                var letter = LETTERS[slot] || '?';

                var gate = h('button', 'sd-gate');
                gate.type = 'button';
                gate.dataset.slot = String(slot);
                gate.dataset.choice = String(c.id);
                gate.setAttribute('aria-label', 'Answer ' + letter + ': ' + c.text);
                gate.innerHTML =
                    '<div class="sd-gate-arch">' +
                      '<span class="sd-gate-flag"></span>' +
                      '<span class="sd-gate-letter">' + letter + '</span>' +
                      '<div class="sd-gate-label">' + ((longAnswers || mobile) ? letter : esc(c.text)) + '</div>' +
                      '<div class="sd-gate-hole"></div>' +
                    '</div>';
                gate.addEventListener('click', function () { pick(c.id); });
                gates.appendChild(gate);

                if (longAnswers || mobile) {
                    var card = h('button', 'sd-choice');
                    card.type = 'button';
                    card.dataset.choice = String(c.id);
                    card.style.setProperty('--g-hi', getComputedStyle(gate).getPropertyValue('--g-hi'));
                    card.innerHTML = '<span class="k">' + letter + '</span><span>' + esc(c.text) + '</span>';
                    card.addEventListener('click', function () { pick(c.id); });
                    choicesEl.appendChild(card);
                }
            });
            choicesEl.classList.toggle('sd-open', longAnswers || mobile);
            alignGates();

            runner.style.left = '50%';
            runner.classList.remove('sd-stumble');
            scatterStars();
            renderProgress();
        }

        // With the answer cards stacked under the question, keep the gates row
        // clear of the stack: anchor it just below the top-center column when
        // the cards are open (desktop/tablet; mobile keeps its bottom anchor).
        function alignGates() {
            if (window.innerWidth <= 640 || !choicesEl.classList.contains('sd-open')) {
                gates.style.top = '';
                return;
            }
            var stackBottom = hud.querySelector('.sd-tc').getBoundingClientRect().bottom - root.getBoundingClientRect().top;
            var min = root.clientHeight * 0.30, max = root.clientHeight * 0.58;
            gates.style.top = Math.round(Math.min(Math.max(stackBottom + 10, min), max)) + 'px';
        }
        window.addEventListener('resize', alignGates);

        function gateFor(choiceId) {
            return gates.querySelector('[data-choice="' + choiceId + '"]');
        }

        function pick(choiceId) {
            if (busy || finished || !q) return;
            if (settings.instantSubmit) { submitPick(choiceId); return; }

            picked = choiceId;
            gates.querySelectorAll('.sd-gate').forEach(function (el) {
                el.classList.toggle('sd-picked', el.dataset.choice === String(choiceId));
            });
            choicesEl.querySelectorAll('.sd-choice').forEach(function (el) {
                el.classList.toggle('sd-picked', el.dataset.choice === String(choiceId));
            });
            confirmBtn.classList.add('sd-open');
            var gate = gateFor(choiceId);
            if (gate) steerRunnerTo(gate);
        }

        function steerRunnerTo(gate) {
            var gr = gate.getBoundingClientRect(), rr = root.getBoundingClientRect();
            runner.style.left = ((gr.left + gr.width / 2 - rr.left) / rr.width * 100) + '%';
        }

        function submitPick(choiceId) {
            if (busy || !q) return;
            busy = true;
            confirmBtn.classList.remove('sd-open');
            var ms = Date.now() - startedAt;
            var gate = gateFor(choiceId);
            if (gate) { steerRunnerTo(gate); gate.classList.add('sd-picked'); }
            gates.querySelectorAll('.sd-gate').forEach(function (el) { el.disabled = true; });
            choicesEl.querySelectorAll('.sd-choice').forEach(function (el) { el.disabled = true; });

            var hadShield = !!g.shield;
            var wasRecovery = !!g.recovery;
            Promise.resolve(adapter.submit(q, choiceId, ms)).then(function (res) {
                if (!res) { busy = false; return; }
                if (res.expired && res.redirect) { window.location.href = res.redirect; return; }

                if (serverMeters && res.game) g = Object.assign(g, res.game);
                else if (!serverMeters) applyClientRules(res.correct);

                reveal(choiceId, res, hadShield, wasRecovery);
            }).catch(function () {
                // Flaky network: unlock so the student can retry — the server-side
                // lock makes a double-send land as an idempotent duplicate.
                busy = false;
                gates.querySelectorAll('.sd-gate').forEach(function (el) { el.disabled = false; });
                choicesEl.querySelectorAll('.sd-choice').forEach(function (el) { el.disabled = false; });
                pop.className = 'sd-pop sd-bad sd-show';
                pop.textContent = 'Connection hiccup — try again';
                setTimeout(function () { pop.className = 'sd-pop'; }, 1400);
            });
        }

        // Practice-mode meters (mirrors SpeedDashAttemptService rules, visual only).
        function applyClientRules(correct) {
            if (correct) {
                g.correct++; g.streak++;
                g.best_streak = Math.max(g.best_streak, g.streak);
                var pts = 100;
                if (!g.recovery) {
                    pts += Math.min((g.streak - 1) * 10, 50);
                    if (settings.powerupsEnabled && g.streak === 5 && !g.shield) g.shield = true;
                    if (settings.powerupsEnabled && g.streak === 3) g.boosts++;
                }
                g.score += pts;
                g.last_points = pts;
            } else {
                g.wrong++; g.streak = 0; g.last_points = 0;
                if (g.shield) g.shield = false;
                else if (g.hearts > 0) g.hearts--;
                if (g.hearts <= 0) g.recovery = true;
            }
        }

        function reveal(choiceId, res, hadShield, wasRecovery) {
            var gate = gateFor(choiceId);
            var shieldUsed = !res.correct && hadShield && !g.shield;

            if (res.correct) {
                if (gate) gate.classList.add('sd-correct');
                if (gate) gate.querySelector('.sd-gate-flag').textContent = '✓ CORRECT';
                var cc = choicesEl.querySelector('[data-choice="' + choiceId + '"]');
                if (cc) cc.classList.add('sd-correct');
                pop.className = 'sd-pop sd-show';
                pop.textContent = '+' + (res.points || (serverMeters ? 0 : g.last_points) || 100) + ' CORRECT!';
                live.textContent = 'Correct! ' + (res.points ? '+' + res.points + ' points.' : '');
                audio.correct();
                if (g.streak === 3) audio.boost();
                if (g.streak === 5) audio.streak();
            } else {
                if (gate) { gate.classList.add('sd-wrong'); gate.querySelector('.sd-gate-flag').textContent = '✗'; }
                var wc = choicesEl.querySelector('[data-choice="' + choiceId + '"]');
                if (wc) wc.classList.add('sd-wrong');
                // Show the right gate ONLY when the server chose to reveal it.
                if (res.correct_choice_id) {
                    var rg = gateFor(res.correct_choice_id);
                    if (rg) { rg.classList.add('sd-correct'); rg.querySelector('.sd-gate-flag').textContent = '✓'; }
                    var rc = choicesEl.querySelector('[data-choice="' + res.correct_choice_id + '"]');
                    if (rc) rc.classList.add('sd-correct');
                }
                runner.classList.add('sd-stumble');
                pop.className = 'sd-pop sd-bad sd-show';
                pop.textContent = shieldUsed ? 'SHIELD USED!' : 'OOPS!';
                live.textContent = 'Not quite.' + (res.correct_choice_id ? ' The correct answer is highlighted.' : '');
                audio.wrong();
            }

            renderMeters();
            renderProgress();
            renderSelfPosition();

            var wait = res.explanation && !res.correct ? 2600 : 1500;
            if (res.explanation && !res.correct) {
                $('qtext').textContent = 'Why: ' + res.explanation;
            }

            setTimeout(function () {
                pop.className = 'sd-pop';
                if (!wasRecovery && g.recovery) return showRecovery();
                nextQuestion();
            }, wait);
        }

        function showRecovery() {
            overlay.style.display = 'flex';
            overlay.innerHTML = '';
            var panel = h('div', 'sd-panel',
                '<h2>Recovery Mode</h2>' +
                '<p>You are out of hearts — but your test keeps going! The track slows down and bonuses pause. ' +
                'Take a breath, read carefully, and finish every question. Your official score only counts correct answers.</p>');
            var btn = h('button', 'sd-btn', 'Keep Running');
            btn.type = 'button';
            btn.addEventListener('click', function () { overlay.style.display = 'none'; nextQuestion(); });
            panel.appendChild(btn);
            overlay.appendChild(panel);
            btn.focus();
        }

        function nextQuestion() {
            index++;
            Promise.resolve(adapter.get(index)).then(function (question) {
                if (!question) return endRun(false);
                if (question.answered) { return nextQuestion(); } // resumed past a locked one
                showQuestion(question);
            });
        }

        function renderSelfPosition() {
            if (adapter.positions) {
                Promise.resolve(adapter.positions()).then(renderPositions);
            } else {
                renderPositions([{ name: meta.studentName || 'You', score: g.score, me: true, avatarUrl: meta.avatarUrl }]);
            }
        }

        function endRun(expired) {
            if (finished) return;
            finished = true;
            if (timerHandle) clearInterval(timerHandle);
            audio.finish();
            adapter.finish({
                score: g.score, correct: g.correct, wrong: g.wrong,
                best_streak: g.best_streak, expired: !!expired,
                show: function (html, actions) { showFinishPanel(html, actions); }
            });
        }

        function showFinishPanel(html, actions) {
            overlay.style.display = 'flex';
            overlay.innerHTML = '';
            var panel = h('div', 'sd-panel', html);
            (actions || []).forEach(function (a) {
                var btn = h('button', 'sd-btn' + (a.alt ? ' alt' : ''), esc(a.label));
                btn.type = 'button';
                btn.addEventListener('click', a.onClick);
                panel.appendChild(btn);
            });
            overlay.appendChild(panel);
            var first = panel.querySelector('button'); if (first) first.focus();
        }

        // ------------------------------------------------------------ pause
        var pausedAt = null;
        function togglePause() {
            if (finished) return;
            if (pausedAt !== null) { overlay.style.display = 'none'; pausedAt = null; return; }
            pausedAt = Date.now();
            overlay.style.display = 'flex';
            overlay.innerHTML = '';
            var panel = h('div', 'sd-panel',
                '<h2>Paused</h2><p>The server clock keeps running on timed tests — the pause is for your eyes, not the timer.</p>');
            var rm = h('div', 'sd-toggle-row',
                '<span>Reduced motion (static track)</span><input type="checkbox" ' + (reduced ? 'checked' : '') + ' aria-label="Reduced motion">');
            rm.querySelector('input').addEventListener('change', function (e) {
                reduced = e.target.checked; setPref('sd_reduced', reduced);
                root.classList.toggle('sd-reduced', reduced);
                root.classList.toggle('sd-motion', !reduced);
                scatterStars(); renderProgress();
            });
            panel.appendChild(rm);
            var snd = h('div', 'sd-toggle-row',
                '<span>Sound</span><input type="checkbox" ' + (audio.isMuted() ? '' : 'checked') + ' aria-label="Sound">');
            snd.querySelector('input').addEventListener('change', function (e) { audio.setMuted(!e.target.checked); });
            panel.appendChild(snd);
            var resume = h('button', 'sd-btn', 'Resume');
            resume.type = 'button';
            resume.addEventListener('click', togglePause);
            panel.appendChild(resume);
            overlay.appendChild(panel);
            resume.focus();
        }

        $('pause').addEventListener('click', togglePause);
        $('mute').addEventListener('click', function () {
            audio.setMuted(!audio.isMuted());
            $('mute').innerHTML = audio.isMuted() ? '&#128263;' : '&#128266;';
        });
        $('mute').innerHTML = audio.isMuted() ? '&#128263;' : '&#128266;';
        confirmBtn.addEventListener('click', function () { if (picked !== null) submitPick(picked); });

        // --------------------------------------------------------- keyboard
        document.addEventListener('keydown', function (e) {
            if (finished || !q || busy) {
                if (e.key === 'Escape' || e.key.toLowerCase() === 'p') togglePause();
                return;
            }
            var k = e.key.toUpperCase();
            var slot = LETTERS.indexOf(k);
            if (slot < 0 && /^[1-5]$/.test(k)) slot = parseInt(k, 10) - 1;
            if (slot >= 0 && slot < q.choices.length) { e.preventDefault(); pick(q.choices[slot].id); return; }
            if (e.key === 'Enter' && picked !== null) { e.preventDefault(); submitPick(picked); return; }
            if (e.key === 'Escape' || k === 'P') togglePause();
        });

        // ------------------------------------------------------------ intro
        function showIntro() {
            overlay.style.display = 'flex';
            overlay.innerHTML = '';
            var answered = g.correct + g.wrong;
            var panel = h('div', 'sd-panel',
                '<h2>Quiz Speed Dash</h2>' +
                '<p>' + esc(meta.subject || '') + (meta.level ? ' &bull; ' + esc(meta.level) : '') + '</p>' +
                '<p>Run through the answer gates! Pick <b>A–E</b> by tap, click, or keyboard (A–E or 1–5' +
                (settings.instantSubmit ? '' : ', then Enter to confirm') + '). ' +
                'Correct answers speed you up and build your streak; wrong answers cost a heart — ' +
                'but your run always continues to the finish line.</p>' +
                (answered > 0 ? '<p><b>Welcome back!</b> Resuming at question ' + (answered + 1) + '.</p>' : ''));
            var rm = h('div', 'sd-toggle-row',
                '<span>Reduced motion (static track)</span><input type="checkbox" ' + (reduced ? 'checked' : '') + ' aria-label="Reduced motion">');
            rm.querySelector('input').addEventListener('change', function (e) {
                reduced = e.target.checked; setPref('sd_reduced', reduced);
                root.classList.toggle('sd-reduced', reduced);
                root.classList.toggle('sd-motion', !reduced);
            });
            panel.appendChild(rm);
            var go = h('button', 'sd-btn', answered > 0 ? 'Resume Run' : 'Start Running');
            go.type = 'button';
            go.addEventListener('click', function () { countdown(); });
            panel.appendChild(go);
            overlay.appendChild(panel);
            go.focus();
        }

        function countdown() {
            overlay.innerHTML = '';
            var n = 3;
            var num = h('div', 'sd-count', '3');
            overlay.appendChild(num);
            audio.tick();
            var iv = setInterval(function () {
                n--;
                if (n > 0) { num.textContent = String(n); audio.tick(); return; }
                clearInterval(iv);
                overlay.style.display = 'none';
                startTimer();
                startFirst();
            }, 900);
        }

        function startFirst() {
            // Resume: jump past questions already locked on the server.
            var seek = function (i) {
                Promise.resolve(adapter.get(i)).then(function (question) {
                    if (!question) return endRun(false);
                    if (question.answered) return seek(i + 1);
                    index = i;
                    showQuestion(question);
                });
            };
            seek(0);
        }

        renderMeters();
        renderSelfPosition();
        renderProgress();
        showIntro();

        return { pause: togglePause };
    }

    window.SpeedDash = { mount: function (opts) { return SpeedDash(opts); } };
})();
