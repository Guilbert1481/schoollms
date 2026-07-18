/**
 * Phase 2b — client-side OMR camera detection.
 *
 * The camera frame never leaves the device: we read the QR (jsQR), find the four
 * corner fiducials and perspective-correct the answer region (OpenCV.js), sample
 * each bubble, and score confidence. High-confidence sheets auto-record; anything
 * low-confidence or ambiguous drops into the review grid for the teacher. Only the
 * sheet token, detected marks, per-item confidence, and scan metadata are POSTed.
 *
 * Thresholds below are a sensible first cut — expect to tune FILL_ON / MARGIN /
 * CONF_MIN against real printed sheets and lighting.
 */
(function () {
    const CFG = window.OMR_SCAN || {};
    if (!CFG.scanUrl) return;

    // ---- tunables -------------------------------------------------------------
    const WARP_W = 1000, WARP_H = 1250;   // canonical region size (px)
    const SAMPLE_R = 9;                   // bubble sample radius (px, in warped space)
    const FILL_ON = 0.45;                 // fill ratio to count a bubble as marked
    const BLANK_MAX = 0.25;               // below this the darkest bubble is "clearly blank"
    const MARGIN = 0.18;                  // required gap between 1st and 2nd darkest
    const CONF_MIN = 0.62;                // per-item confidence below this → review
    const LETTERS = ['A', 'B', 'C', 'D', 'E'];

    const GRID = CFG.grid || [];          // [{n, options:[{label,x,y}]}] normalised coords
    const WRITES = CFG.written || [];     // [{n, type, box:{x,y,w,h}}] normalised coords
    const WRITE_CONF_MIN = 70;            // Tesseract confidence (0..100) below → flag
    const ROSTER = {};                    // sheet_token → roster row
    (CFG.roster || []).forEach((r) => { ROSTER[r.sheet_token] = r; });

    // ---- elements -------------------------------------------------------------
    const video = document.getElementById('omrVideo');
    const canvas = document.getElementById('omrCanvas');
    const statusEl = document.getElementById('omrStatus');
    const whoEl = document.getElementById('omrWho');
    const captureBtn = document.getElementById('omrCapture');
    const reviewEl = document.getElementById('omrReview');
    const gridEl = document.getElementById('omrGrid');
    const writtenEl = document.getElementById('omrWritten');
    const recordBtn = document.getElementById('omrRecordBtn');
    const resultEl = document.getElementById('omrResult');
    if (!video || !canvas) return;

    let ocrWorker = null; // lazily-created Tesseract worker (reused across boxes)

    const esc = (s) => String(s).replace(/[&<>"']/g, (c) =>
        ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const setStatus = (m) => { if (statusEl) statusEl.textContent = m; };

    let stream = null;
    let liveToken = null;      // token seen in the live preview
    let pending = null;        // last detection awaiting confirm/record

    // ---- OpenCV readiness (robust across builds) ------------------------------
    function whenCvReady(cb) {
        if (window.cv && window.cv.Mat) return cb();
        const t = setInterval(() => {
            if (window.cv && window.cv.Mat) { clearInterval(t); cb(); }
        }, 120);
    }

    // ---- camera ---------------------------------------------------------------
    async function startCamera() {
        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 720 } },
                audio: false,
            });
            video.srcObject = stream;
            await video.play();
            setStatus('Point the camera at a sheet. Fill the frame; keep the corner squares visible.');
            requestAnimationFrame(liveLoop);
        } catch (e) {
            setStatus('Camera unavailable: ' + e.message + '. You can still record answers manually.');
        }
    }

    function frameImageData() {
        const w = video.videoWidth, h = video.videoHeight;
        if (!w || !h) return null;
        canvas.width = w; canvas.height = h;
        const ctx = canvas.getContext('2d', { willReadFrequently: true });
        ctx.drawImage(video, 0, 0, w, h);
        return ctx.getImageData(0, 0, w, h);
    }

    // Live loop: just read the QR so the teacher gets "recognised: <name>" feedback.
    function liveLoop() {
        if (!stream) return;
        const img = frameImageData();
        if (img && window.jsQR) {
            const code = jsQR(img.data, img.width, img.height);
            const decoded = code ? decodeToken(code.data) : null;
            liveToken = decoded ? code.data : null;
            if (whoEl) {
                whoEl.textContent = decoded
                    ? 'Recognised: ' + decoded.name + (decoded.graded ? ' (already recorded)' : '')
                    : '';
            }
            if (captureBtn) captureBtn.disabled = !decoded;
        }
        requestAnimationFrame(liveLoop);
    }

    function decodeToken(qr) {
        const row = ROSTER[qr];
        return row ? row : null;   // roster row also confirms the sheet belongs to this section
    }

    // ---- capture + detect -----------------------------------------------------
    async function capture() {
        const img = frameImageData();
        if (!img) { setStatus('No camera frame yet.'); return; }

        const code = window.jsQR ? jsQR(img.data, img.width, img.height) : null;
        const row = code ? ROSTER[code.data] : null;
        if (!row) { setStatus('Align the sheet so the QR is readable and belongs to this section.'); return; }

        if (captureBtn) captureBtn.disabled = true;
        let warped = null;
        try {
            warped = warpToRegion(img);
        } catch (e) {
            setStatus('Detection error: ' + e.message);
            if (captureBtn) captureBtn.disabled = false;
            return;
        }
        if (!warped) {
            setStatus('Could not locate the four corner squares. Flatten the sheet, add light, and try again.');
            if (captureBtn) captureBtn.disabled = false;
            return;
        }

        const analysis = analyze(warped.gray);

        // OCR the write-in boxes (identification / matching), if any.
        let written = [];
        if (WRITES.length) {
            setStatus('Reading written answers…');
            try {
                written = await ocrWrites(warped.gray);
            } catch (e) {
                written = WRITES.map((w) => ({ n: w.n, text: '', conf: 0, low: true }));
            }
        }
        warped.gray.delete();
        if (captureBtn) captureBtn.disabled = false;

        // Write-in answers always get a human confirm (handwriting OCR is only a hint).
        const needsReview = analysis.needsReview || written.length > 0;

        pending = {
            token: code.data, row: row,
            answers: analysis.answers, confidence: analysis.confidence,
            written: written, needsReview: needsReview,
        };
        renderReview(row, analysis, written);

        if (!needsReview && !row.graded) {
            submit(false); // pure-bubble sheet, high confidence → auto-record
        } else if (row.graded) {
            setStatus('This student already has a recorded result — review below, then Record to replace it.');
        } else if (written.length) {
            setStatus('Check the written answers (and any amber bubbles), then Record.');
        } else {
            setStatus('Some marks are unclear — check the highlighted items, then Record.');
        }
    }

    // OCR each write-in box from the warped region → { n, text, conf, low }.
    async function ocrWrites(warped) {
        if (!window.Tesseract) return WRITES.map((w) => ({ n: w.n, text: '', conf: 0, low: true }));
        if (!ocrWorker) {
            ocrWorker = await Tesseract.createWorker('eng');
            await ocrWorker.setParameters({ tessedit_char_whitelist: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789 -' });
        }

        const out = [];
        for (const w of WRITES) {
            const crop = cropBox(warped, w.box);            // canvas of the box
            const { data } = await ocrWorker.recognize(crop);
            const text = (data.text || '').replace(/\s+/g, ' ').trim();
            const conf = Math.round(data.confidence || 0);
            out.push({ n: w.n, text: text, conf: conf, low: text === '' || conf < WRITE_CONF_MIN });
        }
        return out;
    }

    // Crop a normalised box from the warped gray Mat into an upscaled canvas.
    function cropBox(warped, box) {
        const x = Math.max(0, Math.round(box.x * WARP_W));
        const y = Math.max(0, Math.round(box.y * WARP_H));
        const w = Math.min(WARP_W - x, Math.round(box.w * WARP_W));
        const h = Math.min(WARP_H - y, Math.round(box.h * WARP_H));
        const roi = warped.roi(new cv.Rect(x, y, w, h));
        const up = new cv.Mat();
        cv.resize(roi, up, new cv.Size(w * 2, h * 2), 0, 0, cv.INTER_CUBIC);
        cv.threshold(up, up, 0, 255, cv.THRESH_BINARY + cv.THRESH_OTSU);
        const c = document.createElement('canvas');
        cv.imshow(c, up);
        roi.delete(); up.delete();
        return c;
    }

    // Detect the 4 fiducials, order them, and perspective-warp the gray region.
    function warpToRegion(imageData) {
        const src = cv.matFromImageData(imageData);
        const gray = new cv.Mat();
        cv.cvtColor(src, gray, cv.COLOR_RGBA2GRAY);
        const bin = new cv.Mat();
        cv.threshold(gray, bin, 0, 255, cv.THRESH_BINARY_INV + cv.THRESH_OTSU);

        const contours = new cv.MatVector();
        const hierarchy = new cv.Mat();
        cv.findContours(bin, contours, hierarchy, cv.RETR_LIST, cv.CHAIN_APPROX_SIMPLE);

        const frameArea = imageData.width * imageData.height;
        const candidates = [];
        for (let i = 0; i < contours.size(); i++) {
            const c = contours.get(i);
            const area = cv.contourArea(c);
            if (area < frameArea * 0.0004 || area > frameArea * 0.05) { c.delete(); continue; }
            const peri = cv.arcLength(c, true);
            const approx = new cv.Mat();
            cv.approxPolyDP(c, approx, 0.05 * peri, true);
            const rect = cv.boundingRect(c);
            const aspect = rect.width / rect.height;
            const solidity = area / (rect.width * rect.height);
            if (approx.rows === 4 && cv.isContourConvex(approx) && aspect > 0.6 && aspect < 1.6 && solidity > 0.7) {
                candidates.push({ x: rect.x + rect.width / 2, y: rect.y + rect.height / 2 });
            }
            approx.delete(); c.delete();
        }

        src.delete(); bin.delete(); contours.delete(); hierarchy.delete();

        const corners = pickCorners(candidates);
        if (!corners) { gray.delete(); return null; }

        const srcTri = cv.matFromArray(4, 1, cv.CV_32FC2, [
            corners.tl.x, corners.tl.y, corners.tr.x, corners.tr.y,
            corners.bl.x, corners.bl.y, corners.br.x, corners.br.y,
        ]);
        const dstTri = cv.matFromArray(4, 1, cv.CV_32FC2, [0, 0, WARP_W, 0, 0, WARP_H, WARP_W, WARP_H]);
        const M = cv.getPerspectiveTransform(srcTri, dstTri);
        const warped = new cv.Mat();
        cv.warpPerspective(gray, warped, M, new cv.Size(WARP_W, WARP_H), cv.INTER_LINEAR, cv.BORDER_CONSTANT, new cv.Scalar(255));

        gray.delete(); srcTri.delete(); dstTri.delete(); M.delete();
        return { gray: warped };
    }

    // From square candidates, choose the extreme 4 (region corners).
    function pickCorners(cands) {
        if (cands.length < 4) return null;
        let tl = cands[0], br = cands[0], tr = cands[0], bl = cands[0];
        cands.forEach((p) => {
            if (p.x + p.y < tl.x + tl.y) tl = p;
            if (p.x + p.y > br.x + br.y) br = p;
            if (p.x - p.y > tr.x - tr.y) tr = p;
            if (p.x - p.y < bl.x - bl.y) bl = p;
        });
        // Reject degenerate picks (need a real quad).
        const w = Math.hypot(tr.x - tl.x, tr.y - tl.y);
        const h = Math.hypot(bl.x - tl.x, bl.y - tl.y);
        if (w < WARP_W * 0.15 || h < WARP_H * 0.15) return null;
        return { tl, tr, bl, br };
    }

    // Sample every bubble and score each item.
    function analyze(warped) {
        const answers = [];
        const confidence = [];
        let needsReview = false;

        GRID.forEach((item) => {
            const fills = item.options.map((o) => bubbleFill(warped, o.x, o.y));
            const order = fills.map((f, i) => ({ f, l: LETTERS[i] })).sort((a, b) => b.f - a.f);
            const top = order[0], second = order[1] || { f: 0 };

            const marks = fills.map((f, i) => (f >= FILL_ON ? LETTERS[i] : null)).filter(Boolean);

            let conf;
            if (top.f < BLANK_MAX) {
                conf = Math.min(1, (BLANK_MAX - top.f) / BLANK_MAX + 0.5);     // clearly blank
            } else if (marks.length === 1 && (top.f - second.f) >= MARGIN) {
                conf = Math.min(1, (top.f - second.f) / (MARGIN * 2) + 0.4);   // clear single
            } else {
                conf = 0.35;                                                   // erased / two close / multiple
            }
            conf = Math.round(conf * 100) / 100;

            const lowConf = conf < CONF_MIN || marks.length > 1;
            if (lowConf) needsReview = true;

            answers.push({ n: item.n, marks });
            confidence.push({ n: item.n, c: conf, low: lowConf });
        });

        return { answers, confidence, needsReview };
    }

    function bubbleFill(warped, nx, ny) {
        const cx = Math.round(nx * WARP_W), cy = Math.round(ny * WARP_H);
        let sum = 0, count = 0;
        for (let dy = -SAMPLE_R; dy <= SAMPLE_R; dy++) {
            for (let dx = -SAMPLE_R; dx <= SAMPLE_R; dx++) {
                if (dx * dx + dy * dy > SAMPLE_R * SAMPLE_R) continue;
                const x = cx + dx, y = cy + dy;
                if (x < 0 || y < 0 || x >= WARP_W || y >= WARP_H) continue;
                sum += warped.ucharPtr(y, x)[0];
                count++;
            }
        }
        if (!count) return 0;
        return (255 - sum / count) / 255; // 0 (white) .. 1 (black)
    }

    // ---- review grid ----------------------------------------------------------
    function renderReview(row, analysis, written) {
        if (!reviewEl || !gridEl) return;
        reviewEl.style.display = '';
        if (whoEl) whoEl.textContent = 'Sheet: ' + row.name;
        const confByN = {};
        analysis.confidence.forEach((c) => { confByN[c.n] = c; });

        gridEl.innerHTML = analysis.answers.map((a) => {
            const c = confByN[a.n] || {};
            const cls = c.low ? ' low' : '';
            const opts = LETTERS.map((l) =>
                '<span class="opt' + (a.marks.includes(l) ? ' on' : '') + cls + '" data-n="' + a.n + '" data-l="' + l + '">' + l + '</span>'
            ).join('');
            return '<div class="rec-row"><span class="n">' + a.n + '.</span>' + opts +
                (c.low ? '<span class="flag">review</span>' : '') + '</div>';
        }).join('');

        // Write-in inputs pre-filled with the OCR guess (amber = low confidence).
        if (writtenEl) {
            writtenEl.innerHTML = (written || []).map((w) => {
                const border = w.low ? '#f59e0b' : '#cbd5e1';
                return '<div class="rec-row"><span class="n">' + w.n + '.</span>' +
                    '<input type="text" class="wtext" data-n="' + w.n + '" value="' + esc(w.text) + '" ' +
                    'style="flex:1; padding:6px 8px; border:1.4px solid ' + border + '; border-radius:6px; font-size:13px;">' +
                    (w.low ? '<span class="flag">review</span>' : '') + '</div>';
            }).join('');
        }
    }

    if (gridEl) {
        gridEl.addEventListener('click', (e) => {
            const opt = e.target.closest('.opt');
            if (opt) opt.classList.toggle('on');
        });
    }

    function collectFromGrid() {
        return GRID.map((item) => ({
            n: item.n,
            marks: Array.from(gridEl.querySelectorAll('.opt.on[data-n="' + item.n + '"]')).map((el) => el.dataset.l),
        }));
    }

    function collectWritten() {
        if (!writtenEl || !writtenEl.children.length) {
            return (pending && pending.written) ? pending.written.map((w) => ({ n: w.n, text: w.text })) : [];
        }
        return WRITES.map((w) => ({
            n: w.n,
            text: (writtenEl.querySelector('.wtext[data-n="' + w.n + '"]')?.value || '').trim(),
        }));
    }

    // ---- submit ---------------------------------------------------------------
    async function submit(allowRescan) {
        if (!pending) return;
        const answers = gridEl && gridEl.children.length ? collectFromGrid() : pending.answers;
        if (recordBtn) recordBtn.disabled = true;
        setStatus('Recording…');
        try {
            const res = await fetch(CFG.scanUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CFG.csrf, 'Accept': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({
                    sheet_token: pending.token,
                    marked_answers: answers,
                    written_answers: collectWritten(),
                    source: 'camera',
                    confidence: pending.confidence,
                    meta: { detector: 'omr-scan.js', ts: Date.now() },
                    allow_rescan: !!allowRescan,
                }),
            });
            const data = await res.json();

            if (res.status === 409 && data.error === 'already_scanned') {
                if (recordBtn) recordBtn.disabled = false;
                if (confirm('This student already has a recorded result. Replace it?')) return submit(true);
                setStatus('Kept the existing result.');
                return;
            }
            if (!res.ok || !data.ok) {
                setStatus('⚠ ' + (data.error || data.message || 'Failed to record.'));
                if (recordBtn) recordBtn.disabled = false;
                return;
            }

            const s = data.result;
            if (resultEl) {
                resultEl.innerHTML = '✔ Recorded <b>' + pending.row.name + '</b>: ' + s.raw_score + '/' + s.max_score +
                    ' (' + s.percentage + '%) — ' + s.correct_count + ' correct, ' + s.incorrect_count + ' wrong, ' +
                    s.blank_count + ' blank, ' + s.multiple_count + ' multiple.';
            }
            if (ROSTER[pending.token]) ROSTER[pending.token].graded = true;
            setStatus('Recorded. Scan the next sheet.');
            if (reviewEl) reviewEl.style.display = 'none';
            pending = null;
        } catch (e) {
            setStatus('⚠ Network error while recording.');
        } finally {
            if (recordBtn) recordBtn.disabled = false;
        }
    }

    // ---- wire up --------------------------------------------------------------
    if (captureBtn) captureBtn.addEventListener('click', () => { capture(); });
    if (recordBtn) recordBtn.addEventListener('click', () => submit(false));
    window.addEventListener('beforeunload', () => {
        if (stream) stream.getTracks().forEach((t) => t.stop());
        if (ocrWorker) { try { ocrWorker.terminate(); } catch (e) { /* ignore */ } }
    });

    setStatus('Loading detector…');
    whenCvReady(() => { setStatus('Detector ready.'); startCamera(); });
})();
