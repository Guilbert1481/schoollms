/**
 * Quiz Snakes & Ladders — the shared board-game engine.
 *
 * One engine, two deliveries: the GRADED assessment screen (server-authoritative
 * answers + dice) and catalog practice (client-graded) — each supplies an
 * ADAPTER, so in graded mode the engine never knows a correct answer or rolls
 * its own dice.
 *
 * Adapter contract:
 *   meters   'server' (trust submit()/roll() payloads) | 'client' (engine rules)
 *   count()            → total questions
 *   get(i)             → {question_id, text, choices:[{id,text}], answered?, was_correct?, chosen_id?} | null
 *   submit(q, choiceId)→ Promise<{correct, duplicate?, points?, correct_choice_id?, explanation?, game?, expired?, redirect?}>
 *   roll(q)            → Promise<{dice, from, landed, to, event, shielded, moved, bounced, board_completed, game?, duplicate?, expired?, redirect?}>
 *                        (client meters: engine rolls locally and ignores this)
 *   finish(summary)    → run complete; summary has correct/wrong/score/best_streak/position + summary.show(html, buttons)
 *
 * Board contract mirrors App\Services\Games\SnakesBoard: 100 tiles, 10×10
 * serpentine, tile 1 bottom-left START, tile 100 top-left FINISH.
 * Accessibility: keyboard (A–E/1–5, Enter, Space), aria-live announcements,
 * prefers-reduced-motion honored (token jumps instead of walking).
 */
(function () {
    'use strict';

    var SIZE = 100, COLS = 10;

    // ------------------------------------------------------------------
    // Board math (mirrors SnakesBoard::coord — row 0 = bottom, col 0 = left)
    // ------------------------------------------------------------------
    function coord(tile) {
        var row = Math.floor((tile - 1) / COLS);
        var offset = (tile - 1) % COLS;
        var col = row % 2 === 0 ? offset : (COLS - 1 - offset);
        return { row: row, col: col };
    }

    /** Tile center in SVG units (viewBox 0 0 1000 1000; y grows downward). */
    function center(tile) {
        var c = coord(tile);
        return { x: c.col * 100 + 50, y: (9 - c.row) * 100 + 50 };
    }

    // ------------------------------------------------------------------
    // Tiny WebAudio blips — generated, no assets, no copyright.
    // ------------------------------------------------------------------
    function makeAudio() {
        var ctx = null, muted = false;
        try { muted = localStorage.getItem('snl_muted') === '1'; } catch (e) {}
        function tone(freq, dur, type, gain, when) {
            if (muted) return;
            try {
                ctx = ctx || new (window.AudioContext || window.webkitAudioContext)();
                var o = ctx.createOscillator(), g = ctx.createGain();
                o.type = type || 'sine'; o.frequency.value = freq;
                g.gain.value = gain || 0.06;
                o.connect(g); g.connect(ctx.destination);
                var t = ctx.currentTime + (when || 0);
                o.start(t); o.stop(t + (dur || 0.12));
                g.gain.setValueAtTime(g.gain.value, t);
                g.gain.exponentialRampToValueAtTime(0.0001, t + (dur || 0.12));
            } catch (e) { /* audio is best-effort */ }
        }
        return {
            correct: function () { tone(660, 0.1); tone(880, 0.14, 'sine', 0.06, 0.09); },
            wrong: function () { tone(220, 0.2, 'square', 0.04); },
            dice: function () { tone(500, 0.05, 'triangle'); tone(620, 0.05, 'triangle', 0.05, 0.06); },
            step: function () { tone(440, 0.04, 'triangle', 0.03); },
            ladder: function () { tone(523, 0.09); tone(659, 0.09, 'sine', 0.06, 0.08); tone(784, 0.12, 'sine', 0.06, 0.16); },
            snake: function () { tone(392, 0.12, 'sawtooth', 0.03); tone(262, 0.18, 'sawtooth', 0.03, 0.1); },
            shield: function () { tone(700, 0.16, 'sine', 0.07); },
            finish: function () { [523, 659, 784, 1046].forEach(function (f, i) { tone(f, 0.16, 'sine', 0.06, i * 0.12); }); },
            toggle: function () {
                muted = !muted;
                try { localStorage.setItem('snl_muted', muted ? '1' : '0'); } catch (e) {}
                return muted;
            },
            isMuted: function () { return muted; }
        };
    }

    // ------------------------------------------------------------------
    // SVG snakes & ladders overlay
    // ------------------------------------------------------------------
    var SNAKE_COLORS = [
        ['#7c3aed', '#a78bfa'], ['#16a34a', '#4ade80'], ['#ea580c', '#fb923c'],
        ['#db2777', '#f472b6'], ['#2563eb', '#60a5fa'], ['#0d9488', '#2dd4bf'], ['#b45309', '#f59e0b']
    ];

    function svgEl(name, attrs) {
        var el = document.createElementNS('http://www.w3.org/2000/svg', name);
        for (var k in attrs) el.setAttribute(k, attrs[k]);
        return el;
    }

    function drawLadder(svg, from, to) {
        var a = center(from), b = center(to);
        var dx = b.x - a.x, dy = b.y - a.y, len = Math.sqrt(dx * dx + dy * dy);
        var nx = -dy / len, ny = dx / len, half = 14;
        var rail = function (sign) {
            return svgEl('line', {
                x1: a.x + nx * half * sign, y1: a.y + ny * half * sign,
                x2: b.x + nx * half * sign, y2: b.y + ny * half * sign,
                stroke: '#a16207', 'stroke-width': 9, 'stroke-linecap': 'round'
            });
        };
        var glow = svgEl('line', { x1: a.x, y1: a.y, x2: b.x, y2: b.y, stroke: 'rgba(250,204,21,.35)', 'stroke-width': 40, 'stroke-linecap': 'round' });
        svg.appendChild(glow);
        svg.appendChild(rail(1)); svg.appendChild(rail(-1));
        var rungs = Math.max(3, Math.floor(len / 46));
        for (var i = 1; i < rungs; i++) {
            var t = i / rungs, px = a.x + dx * t, py = a.y + dy * t;
            svg.appendChild(svgEl('line', {
                x1: px + nx * half, y1: py + ny * half, x2: px - nx * half, y2: py - ny * half,
                stroke: '#ca8a04', 'stroke-width': 7, 'stroke-linecap': 'round'
            }));
        }
    }

    function drawSnake(svg, from, to, colorIdx) {
        var head = center(from), tail = center(to);
        var colors = SNAKE_COLORS[colorIdx % SNAKE_COLORS.length];
        var dx = tail.x - head.x, dy = tail.y - head.y;
        var mx = head.x + dx * 0.5, my = head.y + dy * 0.5;
        var wig = 70 * (colorIdx % 2 === 0 ? 1 : -1);
        var nx = -dy, ny = dx, nl = Math.sqrt(nx * nx + ny * ny) || 1;
        nx = nx / nl * wig; ny = ny / nl * wig;
        var d = 'M ' + head.x + ' ' + head.y +
            ' C ' + (head.x + dx * 0.25 + nx) + ' ' + (head.y + dy * 0.25 + ny) + ', ' +
            (mx - nx) + ' ' + (my - ny) + ', ' + mx + ' ' + my +
            ' S ' + (tail.x - dx * 0.2 + nx) + ' ' + (tail.y - dy * 0.2 + ny) + ', ' + tail.x + ' ' + tail.y;
        var gid = 'snlSnakeG' + colorIdx + '_' + from;
        var defs = svg.querySelector('defs') || svg.insertBefore(svgEl('defs', {}), svg.firstChild);
        var grad = svgEl('linearGradient', { id: gid, x1: head.x, y1: head.y, x2: tail.x, y2: tail.y, gradientUnits: 'userSpaceOnUse' });
        var s0 = svgEl('stop', { offset: '0%' }); s0.setAttribute('stop-color', colors[0]);
        var s1 = svgEl('stop', { offset: '100%' }); s1.setAttribute('stop-color', colors[1]);
        grad.appendChild(s0); grad.appendChild(s1); defs.appendChild(grad);

        svg.appendChild(svgEl('path', { d: d, fill: 'none', stroke: 'url(#' + gid + ')', 'stroke-width': 17, 'stroke-linecap': 'round', opacity: 0.95 }));
        // Friendly head: round face + eyes, arrowed tongue toward its tail tile.
        svg.appendChild(svgEl('circle', { cx: head.x, cy: head.y, r: 19, fill: colors[0] }));
        svg.appendChild(svgEl('circle', { cx: head.x - 6, cy: head.y - 5, r: 4.2, fill: '#fff' }));
        svg.appendChild(svgEl('circle', { cx: head.x + 6, cy: head.y - 5, r: 4.2, fill: '#fff' }));
        svg.appendChild(svgEl('circle', { cx: head.x - 6, cy: head.y - 5, r: 2, fill: '#1e293b' }));
        svg.appendChild(svgEl('circle', { cx: head.x + 6, cy: head.y - 5, r: 2, fill: '#1e293b' }));
        svg.appendChild(svgEl('path', { d: 'M ' + (head.x - 5) + ' ' + (head.y + 7) + ' Q ' + head.x + ' ' + (head.y + 11) + ' ' + (head.x + 5) + ' ' + (head.y + 7), fill: 'none', stroke: '#1e293b', 'stroke-width': 1.8, 'stroke-linecap': 'round' }));
        // Tail arrow marker so students can see where the slide ends.
        svg.appendChild(svgEl('circle', { cx: tail.x, cy: tail.y, r: 8, fill: colors[1] }));
    }

    // ------------------------------------------------------------------
    // Engine
    // ------------------------------------------------------------------
    function mount(opts) {
        var root = opts.root, adapter = opts.adapter;
        var settings = opts.settings || {};
        var meta = opts.meta || {};
        var board = opts.board || { snakes: {}, ladders: {} };
        var audio = makeAudio();
        var reducedMotion = false;
        try { reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches; } catch (e) {}

        // Game meters (server mode: overwritten by every server payload).
        var g = Object.assign({
            position: 1, score: 0, streak: 0, best_streak: 0, correct: 0, wrong: 0,
            shields: 0, shields_used: 0, snakes_hit: 0, ladders_climbed: 0,
            rolls: [], pending_roll: null, board_completed: false
        }, opts.game || {});
        if (opts.game && opts.game.board) board = opts.game.board;

        // Live count — practice adapters requeue missed questions, so the
        // queue can grow between turns.
        function totalQ() { return adapter.count(); }
        var idx = firstOpenIndex();
        var selected = null;      // chosen choice id (pre-submit)
        var phase = 'question';   // question | submitting | rolling | moving | done
        var timerLeft = typeof opts.timerSeconds === 'number' ? opts.timerSeconds : null;

        function firstOpenIndex() {
            var n = totalQ();
            for (var i = 0; i < n; i++) {
                var q = adapter.get(i);
                if (q && !q.answered) return i;
            }
            return n; // everything answered
        }

        // --------------------------------------------------------------
        // Skeleton
        // --------------------------------------------------------------
        root.classList.add('snl');
        root.innerHTML =
            '<div class="snl-scene">' +
              '<div class="snl-hud">' +
                '<div class="snl-logo"><span class="snl-logo-top">QUIZ <span class="snl-star">★</span></span><span class="snl-logo-main">SNAKES &amp; LADDERS</span></div>' +
                '<div class="snl-hud-mid">' + (timerLeft !== null ? '<div class="snl-pill snl-timer"><span class="snl-clock">◔</span><span data-snl="timer">--:--</span></div>' : '') + '</div>' +
                '<div class="snl-hud-right">' +
                  '<div class="snl-pill snl-scorepill"><span class="snl-coin">★</span><span data-snl="score">0</span></div>' +
                  '<button type="button" class="snl-round-btn" data-snl="mute" title="Mute sounds">♪</button>' +
                '</div>' +
              '</div>' +
              '<div class="snl-main">' +
                '<div class="snl-board-wrap">' +
                  '<div class="snl-board" data-snl="board">' +
                    '<div class="snl-tiles" data-snl="tiles"></div>' +
                    '<svg class="snl-overlay" viewBox="0 0 1000 1000" preserveAspectRatio="none" data-snl="overlay" aria-hidden="true"></svg>' +
                    '<div class="snl-token" data-snl="token"><img alt="" data-snl="avatar"><span class="snl-token-ring"></span></div>' +
                  '</div>' +
                  '<div class="snl-banner" data-snl="banner" role="status"></div>' +
                '</div>' +
                '<div class="snl-panel">' +
                  '<div class="snl-panel-head"><span class="snl-subject-ico">📘</span><span class="snl-subject" data-snl="subject"></span></div>' +
                  '<div class="snl-qno-wrap"><span class="snl-qno" data-snl="qno"></span></div>' +
                  '<div class="snl-question" data-snl="question"></div>' +
                  '<div class="snl-choices" data-snl="choices" role="radiogroup" aria-label="Answer choices"></div>' +
                  '<button type="button" class="snl-submit" data-snl="submit">Submit Answer</button>' +
                  '<div class="snl-roll-wrap">' +
                    '<button type="button" class="snl-roll" data-snl="roll" aria-label="Roll the dice">' +
                      '<span class="snl-die" data-snl="die">⚄</span><span class="snl-roll-label">ROLL</span><span class="snl-lock" data-snl="lock">🔒</span>' +
                    '</button>' +
                  '</div>' +
                '</div>' +
              '</div>' +
              '<div class="snl-bottom">' +
                '<div class="snl-players" data-snl="players"></div>' +
                '<div class="snl-shield-chip"><span class="snl-shield-title">Snake<br>Shield</span><span class="snl-shield-ico">🛡</span><span class="snl-shield-n" data-snl="shields">0</span></div>' +
              '</div>' +
              '<div class="snl-live" aria-live="polite" data-snl="live"></div>' +
              '<div class="snl-modal" data-snl="modal" hidden><div class="snl-modal-card" data-snl="modalcard"></div></div>' +
            '</div>';

        var el = {};
        root.querySelectorAll('[data-snl]').forEach(function (n) { el[n.getAttribute('data-snl')] = n; });

        el.subject.textContent = (meta.subject || 'Quiz') + (meta.level ? ' • ' + meta.level : '');
        if (meta.avatarUrl) el.avatar.src = meta.avatarUrl;

        // Player strip (single-player deliveries show the one student).
        el.players.innerHTML =
            '<div class="snl-player snl-player-active">' +
              '<span class="snl-player-avatar">' + (meta.avatarUrl ? '<img src="' + meta.avatarUrl + '" alt="">' : '') + '</span>' +
              '<span class="snl-player-info"><span class="snl-player-name">' + escapeHtml(meta.studentName || 'Player') + '</span>' +
              '<span class="snl-turn-chip" data-snl="turnchip">YOUR TURN</span></span>' +
            '</div>';

        buildTiles();
        buildOverlay();
        placeToken(g.position, true);
        syncMeters();
        updateMuteButton();

        // --------------------------------------------------------------
        // Board rendering
        // --------------------------------------------------------------
        function buildTiles() {
            var html = '';
            for (var t = 1; t <= SIZE; t++) {
                var c = coord(t);
                var cls = 'snl-tile snl-shade-' + ((c.row + c.col) % 4);
                var label = String(t);
                if (t === 1) { cls += ' snl-start'; label = '<span class="snl-tile-tag">START</span>1'; }
                if (t === SIZE) { cls += ' snl-finish'; label = '<span class="snl-tile-tag">FINISH</span>100'; }
                html += '<div class="' + cls + '" data-tile="' + t + '" style="grid-row:' + (10 - c.row) + ';grid-column:' + (c.col + 1) + '">' + label + '</div>';
            }
            el.tiles.innerHTML = html;
        }

        function buildOverlay() {
            el.overlay.innerHTML = '';
            var i = 0, from;
            for (from in board.ladders) drawLadder(el.overlay, parseInt(from, 10), board.ladders[from]);
            for (from in board.snakes) drawSnake(el.overlay, parseInt(from, 10), board.snakes[from], i++);
        }

        function placeToken(tile, instant) {
            var c = coord(tile);
            el.token.style.left = 'calc(' + (c.col * 10 + 5) + '% )';
            el.token.style.top = 'calc(' + ((9 - c.row) * 10 + 5) + '% )';
            if (instant) el.token.classList.add('snl-token-still');
            else el.token.classList.remove('snl-token-still');
        }

        // --------------------------------------------------------------
        // HUD + announcements
        // --------------------------------------------------------------
        function syncMeters() {
            el.score.textContent = (g.score || 0).toLocaleString();
            el.shields.textContent = g.shields || 0;
        }

        function say(text) { el.live.textContent = text; }

        function banner(kind, html, sticky) {
            el.banner.className = 'snl-banner snl-banner-' + kind + ' snl-banner-show';
            el.banner.innerHTML = html;
            if (!sticky) {
                clearTimeout(banner._t);
                banner._t = setTimeout(function () { el.banner.classList.remove('snl-banner-show'); }, 2600);
            }
        }

        function updateMuteButton() {
            el.mute.textContent = audio.isMuted() ? '🔇' : '♪';
            el.mute.title = audio.isMuted() ? 'Unmute sounds' : 'Mute sounds';
        }
        el.mute.addEventListener('click', function () { audio.toggle(); updateMuteButton(); });

        if (timerLeft !== null) {
            var tick = setInterval(function () {
                timerLeft = Math.max(0, timerLeft - 1);
                var m = Math.floor(timerLeft / 60), s = timerLeft % 60;
                el.timer.textContent = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
                if (timerLeft <= 0) clearInterval(tick);
            }, 1000);
            var m0 = Math.floor(timerLeft / 60), s0 = timerLeft % 60;
            el.timer.textContent = (m0 < 10 ? '0' : '') + m0 + ':' + (s0 < 10 ? '0' : '') + s0;
        }

        // --------------------------------------------------------------
        // Question flow
        // --------------------------------------------------------------
        var LETTERS = ['A', 'B', 'C', 'D', 'E'];

        function showQuestion() {
            if (idx >= totalQ() && !g.pending_roll) { return finishRun(); }

            var q = g.pending_roll ? questionById(g.pending_roll) : adapter.get(idx);
            if (!q) { return finishRun(); }

            selected = null;
            phase = 'question';
            el.qno.textContent = 'Question ' + (indexOf(q) + 1) + ' of ' + totalQ();
            el.question.textContent = q.text;

            var html = '';
            q.choices.forEach(function (ch, i) {
                if (i >= 5) return;
                html += '<button type="button" class="snl-choice" role="radio" aria-checked="false" data-choice="' + ch.id + '">' +
                    '<span class="snl-choice-letter">' + LETTERS[i] + '</span>' +
                    '<span class="snl-choice-text">' + escapeHtml(ch.text) + '</span></button>';
            });
            el.choices.innerHTML = html;
            el.choices.querySelectorAll('.snl-choice').forEach(function (btn) {
                btn.addEventListener('click', function () { select(parseInt(btn.getAttribute('data-choice'), 10)); });
            });

            if (g.pending_roll) {
                // Resumed mid-turn: the answer is locked, only the roll is owed.
                paintLocked(q);
                armRoll();
                banner('good', '✔ Correct! Roll the dice');
            } else {
                el.submit.disabled = true;
                el.submit.classList.remove('snl-hide');
                disarmRoll();
            }
            say('Question ' + (indexOf(q) + 1) + ' of ' + totalQ() + '. ' + q.text);
        }

        function questionById(id) {
            for (var i = 0; i < totalQ(); i++) {
                var q = adapter.get(i);
                if (q && q.question_id === id) return q;
            }
            return null;
        }

        function indexOf(q) {
            for (var i = 0; i < totalQ(); i++) { var x = adapter.get(i); if (x && x.question_id === q.question_id) return i; }
            return idx;
        }

        function select(choiceId) {
            if (phase !== 'question' || g.pending_roll) return;
            selected = choiceId;
            el.choices.querySelectorAll('.snl-choice').forEach(function (btn) {
                var on = parseInt(btn.getAttribute('data-choice'), 10) === choiceId;
                btn.classList.toggle('snl-choice-selected', on);
                btn.setAttribute('aria-checked', on ? 'true' : 'false');
            });
            el.submit.disabled = false;
        }

        function paintLocked(q, correctChoiceId) {
            el.submit.classList.add('snl-hide');
            el.choices.querySelectorAll('.snl-choice').forEach(function (btn) {
                var id = parseInt(btn.getAttribute('data-choice'), 10);
                btn.disabled = true;
                if (q.chosen_id && id === q.chosen_id) {
                    btn.classList.add(q.was_correct === false ? 'snl-choice-wrong' : 'snl-choice-selected');
                }
                if (correctChoiceId && id === correctChoiceId) btn.classList.add('snl-choice-correct');
            });
        }

        function submitAnswer() {
            if (phase !== 'question' || selected === null) return;
            var q = adapter.get(idx);
            phase = 'submitting';
            el.submit.disabled = true;

            Promise.resolve(adapter.submit(q, selected)).then(function (res) {
                if (res && res.expired && res.redirect) { window.location = res.redirect; return; }
                q.answered = true;
                q.was_correct = !!res.correct;
                q.chosen_id = selected;
                if (res.game) { applyServerGame(res.game); }
                else if (adapter.meters === 'client') { applyClientAnswer(!!res.correct, q.question_id); }

                el.choices.querySelectorAll('.snl-choice').forEach(function (btn) {
                    var id = parseInt(btn.getAttribute('data-choice'), 10);
                    btn.disabled = true;
                    if (id === selected) btn.classList.add(res.correct ? 'snl-choice-correct' : 'snl-choice-wrong');
                    if (res.correct_choice_id && id === res.correct_choice_id) btn.classList.add('snl-choice-correct');
                });
                el.submit.classList.add('snl-hide');
                syncMeters();

                if (res.correct) {
                    audio.correct();
                    banner('good', '✔ Correct! Roll the dice');
                    say('Your answer is correct. Dice roll is now available.');
                    armRoll();
                    phase = 'question';
                } else {
                    audio.wrong();
                    banner('bad', '✖ Not this time — keep going, you\'ve got this!');
                    say('Your answer is not correct. The token stays on tile ' + g.position + '.');
                    setTimeout(function () { idx = nextOpenIndex(); showQuestion(); }, reducedMotion ? 600 : 1400);
                }
            }).catch(function () {
                phase = 'question';
                el.submit.disabled = false;
                banner('info', 'Connection hiccup — please try again.');
            });
        }

        function nextOpenIndex() {
            var n = totalQ();
            for (var i = 0; i < n; i++) { var q = adapter.get(i); if (q && !q.answered) return i; }
            return n;
        }

        // --------------------------------------------------------------
        // Dice + movement
        // --------------------------------------------------------------
        function armRoll() {
            el.roll.classList.add('snl-roll-armed');
            el.roll.disabled = false;
            el.lock.hidden = true;
        }

        function disarmRoll() {
            el.roll.classList.remove('snl-roll-armed');
            el.roll.disabled = true;
            el.lock.hidden = false;
        }

        function rollDice() {
            if (!el.roll.classList.contains('snl-roll-armed') || phase === 'rolling' || phase === 'moving') return;
            var q = g.pending_roll ? questionById(g.pending_roll) : adapter.get(idx);
            if (!q) return;
            phase = 'rolling';
            disarmRoll();
            audio.dice();

            var faces = ['⚀', '⚁', '⚂', '⚃', '⚄', '⚅'];
            var spin = null;
            if (!reducedMotion) {
                el.roll.classList.add('snl-roll-spin');
                spin = setInterval(function () { el.die.textContent = faces[Math.floor(Math.random() * 6)]; }, 90);
            }

            var request = adapter.meters === 'client'
                ? Promise.resolve(clientRoll(q))
                : Promise.resolve(adapter.roll(q));

            request.then(function (res) {
                var wait = reducedMotion ? 0 : 650;
                setTimeout(function () {
                    if (spin) clearInterval(spin);
                    el.roll.classList.remove('snl-roll-spin');
                    if (res && res.expired && res.redirect) { window.location = res.redirect; return; }

                    el.die.textContent = faces[(res.dice || 1) - 1] || '⚅';
                    say('You rolled ' + res.dice + '.');
                    if (res.game) applyServerGame(res.game);
                    animateMove(res);
                }, wait);
            }).catch(function () {
                if (spin) clearInterval(spin);
                el.roll.classList.remove('snl-roll-spin');
                phase = 'question';
                armRoll();
                banner('info', 'Connection hiccup — tap ROLL again.');
            });
        }

        function animateMove(res) {
            phase = 'moving';
            var from = res.from, landed = res.landed, to = res.to;

            if (!res.moved) {
                banner('info', 'Rolled ' + res.dice + ' — you need the exact number to finish!');
                say('Rolled ' + res.dice + '. You need an exact roll to reach tile 100. Staying on tile ' + from + '.');
                return afterMove(res);
            }

            var steps = [];
            if (res.bounced) {
                for (var t1 = from + 1; t1 <= SIZE; t1++) steps.push(t1);
                for (var t2 = SIZE - 1; t2 >= landed; t2--) steps.push(t2);
            } else {
                for (var t = from + 1; t <= landed; t++) steps.push(t);
            }

            if (reducedMotion) {
                placeToken(to, true);
                return announceLanding(res, true);
            }

            var i = 0;
            (function walk() {
                if (i < steps.length) {
                    placeToken(steps[i]); audio.step(); i++;
                    setTimeout(walk, 210);
                    return;
                }
                announceLanding(res, false);
            })();
        }

        function announceLanding(res, instant) {
            var doneMove = function () { afterMove(res); };

            if (res.event && res.event.type === 'snake' && res.shielded) {
                audio.shield();
                banner('good', '🛡 Snake Shield used — you stayed on tile ' + res.to + '!');
                say('A snake was blocked by your Snake Shield. You stay on tile ' + res.to + '.');
                syncMeters();
                return setTimeout(doneMove, instant ? 300 : 1200);
            }
            if (res.event && res.event.type === 'snake') {
                audio.snake();
                banner('bad', '🐍 Snake! Down from ' + res.event.from + ' to ' + res.event.to);
                say('Landed on a snake and moved down to tile ' + res.event.to + '.');
                setTimeout(function () { placeToken(res.to, instant); }, instant ? 0 : 500);
                return setTimeout(doneMove, instant ? 300 : 1400);
            }
            if (res.event && res.event.type === 'ladder') {
                audio.ladder();
                banner('good', '🪜 Ladder! Up from ' + res.event.from + ' to ' + res.event.to);
                say('Landed on a ladder and climbed to tile ' + res.event.to + '.');
                setTimeout(function () { placeToken(res.to, instant); }, instant ? 0 : 500);
                return setTimeout(doneMove, instant ? 300 : 1400);
            }
            say('Moved from tile ' + res.from + ' to tile ' + res.to + '.');
            return setTimeout(doneMove, instant ? 200 : 500);
        }

        function afterMove(res) {
            g.position = res.to;
            g.pending_roll = null;
            syncMeters();

            if (res.board_completed && !afterMove._cheered) {
                afterMove._cheered = true;
                audio.finish();
                banner('gold', '🏆 You reached tile 100!', false);
                say('You reached tile 100.');
            }

            // Practice runs END at tile 100; graded sittings keep asking
            // until every required question is answered.
            if (res.board_completed && adapter.meters === 'client') {
                return setTimeout(function () { finishRun(); }, reducedMotion ? 300 : 1200);
            }

            idx = nextOpenIndex();
            setTimeout(function () { showQuestion(); }, reducedMotion ? 300 : 900);
        }

        // --------------------------------------------------------------
        // Client-mode rules (practice only — server mode never runs these)
        // --------------------------------------------------------------
        function applyClientAnswer(correct, questionId) {
            if (correct) {
                g.correct++; g.streak++; g.best_streak = Math.max(g.best_streak, g.streak);
                g.score += 100 + Math.min((g.streak - 1) * 10, 50);
                if (g.streak % 3 === 0 && g.shields < 3) g.shields++;
                g.pending_roll = questionId;
            } else {
                g.wrong++; g.streak = 0;
            }
        }

        function clientRoll(q) {
            var dice = Math.floor(Math.random() * 6) + 1;
            var from = g.position, target = from + dice;
            var moved = true, bounced = false, landed;
            if (target > SIZE) { target = SIZE - (target - SIZE); bounced = true; } // practice = bounce-back
            landed = target;
            var to = landed, event = null, shielded = false;
            if (board.snakes[landed]) {
                event = { type: 'snake', from: landed, to: board.snakes[landed] };
                if (g.shields > 0) { g.shields--; g.shields_used++; shielded = true; to = landed; }
                else { g.snakes_hit++; to = board.snakes[landed]; }
            } else if (board.ladders[landed]) {
                event = { type: 'ladder', from: landed, to: board.ladders[landed] };
                g.ladders_climbed++; to = board.ladders[landed];
            }
            if (to === SIZE) { g.board_completed = true; g.score += 250; }
            g.position = to; g.pending_roll = null;
            return { dice: dice, from: from, landed: landed, to: to, event: event, shielded: shielded, moved: moved, bounced: bounced, board_completed: g.board_completed };
        }

        function applyServerGame(game) {
            var keep = g.board;
            g = Object.assign(g, game);
            if (!g.board) g.board = keep;
        }

        // --------------------------------------------------------------
        // Finish
        // --------------------------------------------------------------
        function finishRun() {
            phase = 'done';
            disarmRoll();
            el.submit.classList.add('snl-hide');
            el.qno.textContent = 'All questions answered';
            el.question.textContent = 'Great work — the game is complete!';
            el.choices.innerHTML = '';

            var summary = {
                correct: g.correct, wrong: g.wrong, score: g.score,
                best_streak: g.best_streak, position: g.position,
                board_completed: g.board_completed,
                show: function (html, buttons) {
                    var card = el.modalcard;
                    card.innerHTML = html;
                    (buttons || []).forEach(function (b) {
                        var btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'snl-modal-btn';
                        btn.textContent = b.label;
                        btn.addEventListener('click', b.onClick);
                        card.appendChild(btn);
                    });
                    el.modal.hidden = false;
                }
            };
            adapter.finish(summary);
        }

        // --------------------------------------------------------------
        // Controls
        // --------------------------------------------------------------
        el.submit.addEventListener('click', submitAnswer);
        el.roll.addEventListener('click', rollDice);

        document.addEventListener('keydown', function (e) {
            if (e.target && /INPUT|TEXTAREA|SELECT/.test(e.target.tagName)) return;
            var k = e.key.toUpperCase();
            var li = LETTERS.indexOf(k) !== -1 ? LETTERS.indexOf(k) : (/^[1-5]$/.test(k) ? parseInt(k, 10) - 1 : -1);
            if (li >= 0) {
                var btns = el.choices.querySelectorAll('.snl-choice');
                if (btns[li] && !btns[li].disabled) { select(parseInt(btns[li].getAttribute('data-choice'), 10)); e.preventDefault(); }
            } else if (e.key === 'Enter') {
                if (!el.submit.disabled && !el.submit.classList.contains('snl-hide')) { submitAnswer(); e.preventDefault(); }
            } else if (e.key === ' ') {
                if (el.roll.classList.contains('snl-roll-armed')) { rollDice(); e.preventDefault(); }
            }
        });

        function escapeHtml(s) {
            return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        }

        // Kick off.
        showQuestion();
    }

    window.SnakeLadder = { mount: mount, coord: coord };
})();
