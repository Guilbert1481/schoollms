/*
 * Auto-shrink image uploads in the browser BEFORE they are sent.
 * -------------------------------------------------------------------------
 * Why this exists: an oversized upload is rejected with HTTP 413 (Content Too
 * Large) at the web-server / PHP layer — BEFORE Laravel runs — so no amount of
 * server-side compression can help; the bytes never arrive. This intercepts a
 * form submit, downscales + re-encodes any selected raster image to a size that
 * clears the upload limit, swaps it back into the file input, then lets the
 * submit proceed.
 *
 * Quality-first, by design:
 *   - Files already small enough are sent UNTOUCHED (zero loss).
 *   - We pick the HIGHEST JPEG quality that fits the byte budget, so quality
 *     only drops for genuinely huge files.
 *   - Downscaling is done in halving steps with high-quality smoothing.
 *   - Transparent PNGs get a white backdrop (not the black one a raw JPEG
 *     flatten produces), so logos come out clean.
 *
 * Progressive enhancement: anything that throws falls back to the original
 * file, and the form submits exactly as it does today. Opt a form or input out
 * with the `data-no-compress` attribute.
 */
(function () {
    'use strict';

    // --- Tunables ------------------------------------------------------------
    // Longest edge we keep. Matches the server's own re-encode ceiling, so the
    // browser never ships more pixels than the server would keep anyway.
    var MAX_EDGE = 2000;

    // Byte budget per image. We choose the highest quality that lands under it,
    // so quality is preserved unless the source is very large. Kept well under
    // the common 1–2 MB server limits so a logo + banner together still fit.
    var TARGET_BYTES = 1024 * 1024; // 1 MB

    var QUALITY_START = 0.92;
    var QUALITY_MIN = 0.75;
    var QUALITY_STEP = 0.05;

    // Only these source types are re-encoded. PDF / SVG / GIF (possibly
    // animated) are deliberately left untouched.
    var HANDLED = { 'image/jpeg': 1, 'image/png': 1, 'image/webp': 1 };

    // Guard so our own programmatic re-submit does not re-enter the handler.
    var SUBMITTING = new WeakSet();

    function isHandledImage(file) {
        return !!file && HANDLED[file.type] === 1;
    }

    // Load a File into an <img>. Modern browsers honour EXIF orientation when
    // rendering <img>, so drawing it to a canvas yields correctly-rotated pixels.
    function loadImage(file) {
        return new Promise(function (resolve, reject) {
            var url = URL.createObjectURL(file);
            var img = new Image();
            img.onload = function () { URL.revokeObjectURL(url); resolve(img); };
            img.onerror = function (err) { URL.revokeObjectURL(url); reject(err); };
            img.src = url;
        });
    }

    // Stepwise (halving) downscale — noticeably cleaner than one big jump —
    // then a final draw onto an opaque white canvas.
    function drawScaled(img, targetW, targetH) {
        var cur = document.createElement('canvas');
        var cw = img.naturalWidth;
        var ch = img.naturalHeight;
        cur.width = cw;
        cur.height = ch;
        cur.getContext('2d').drawImage(img, 0, 0);

        while (cw > targetW * 2) {
            var nw = Math.max(targetW, Math.round(cw / 2));
            var nh = Math.max(targetH, Math.round(ch / 2));
            var next = document.createElement('canvas');
            next.width = nw;
            next.height = nh;
            var nctx = next.getContext('2d');
            nctx.imageSmoothingEnabled = true;
            nctx.imageSmoothingQuality = 'high';
            nctx.drawImage(cur, 0, 0, cw, ch, 0, 0, nw, nh);
            cur = next; cw = nw; ch = nh;
        }

        var canvas = document.createElement('canvas');
        canvas.width = targetW;
        canvas.height = targetH;
        var ctx = canvas.getContext('2d');
        ctx.imageSmoothingEnabled = true;
        ctx.imageSmoothingQuality = 'high';
        // White backdrop so transparent PNG logos don't flatten to black.
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, targetW, targetH);
        ctx.drawImage(cur, 0, 0, cw, ch, 0, 0, targetW, targetH);
        return canvas;
    }

    function toBlob(canvas, quality) {
        return new Promise(function (resolve) {
            canvas.toBlob(function (b) { resolve(b); }, 'image/jpeg', quality);
        });
    }

    // Returns a compressed File, or the original when no work is needed / useful.
    function compress(file) {
        if (!isHandledImage(file)) return Promise.resolve(file);

        return loadImage(file).then(function (img) {
            var w = img.naturalWidth;
            var h = img.naturalHeight;
            if (!w || !h) return file;

            var scale = Math.min(1, MAX_EDGE / Math.max(w, h));
            var tw = Math.max(1, Math.round(w * scale));
            var th = Math.max(1, Math.round(h * scale));

            // Fast path: no downscale needed, already under budget, and no
            // transparency to flatten (JPEG) → ship the original, zero loss.
            if (scale === 1 && file.type === 'image/jpeg' && file.size <= TARGET_BYTES) {
                return file;
            }

            var canvas = drawScaled(img, tw, th);

            // Highest quality under the byte budget.
            var q = QUALITY_START;
            return toBlob(canvas, q).then(function step(blob) {
                if (blob && blob.size > TARGET_BYTES && q > QUALITY_MIN) {
                    q = Math.max(QUALITY_MIN, q - QUALITY_STEP);
                    return toBlob(canvas, q).then(step);
                }
                // Still too big at the quality floor: shrink dimensions once more.
                if (blob && blob.size > TARGET_BYTES) {
                    var c2 = drawScaled(img, Math.round(tw * 0.75), Math.round(th * 0.75));
                    return toBlob(c2, QUALITY_MIN);
                }
                return blob;
            }).then(function (blob) {
                if (!blob) return file;
                // Never make a same-size image bigger than the original.
                if (scale === 1 && blob.size >= file.size) return file;
                var base = (file.name || 'image').replace(/\.[^.]+$/, '');
                return new File([blob], base + '.jpg', {
                    type: 'image/jpeg',
                    lastModified: Date.now(),
                });
            });
        });
    }

    // File inputs in this form that (a) aren't opted out and (b) hold at least
    // one handled image.
    function imageInputs(form) {
        if (form.hasAttribute('data-no-compress')) return [];
        var out = [];
        form.querySelectorAll('input[type="file"]').forEach(function (inp) {
            if (inp.disabled || inp.hasAttribute('data-no-compress')) return;
            if (!inp.files || inp.files.length === 0) return;
            for (var i = 0; i < inp.files.length; i++) {
                if (isHandledImage(inp.files[i])) { out.push(inp); break; }
            }
        });
        return out;
    }

    function processForm(form) {
        var inputs = imageInputs(form);
        var chain = Promise.resolve();
        inputs.forEach(function (inp) {
            chain = chain.then(function () {
                var dt = new DataTransfer();
                var changed = false;
                var inner = Promise.resolve();
                var files = Array.prototype.slice.call(inp.files);
                files.forEach(function (f) {
                    inner = inner.then(function () {
                        return compress(f).then(function (out) {
                            if (out !== f) changed = true;
                            dt.items.add(out);
                        }).catch(function () {
                            dt.items.add(f); // fall back to the original
                        });
                    });
                });
                return inner.then(function () {
                    if (changed) inp.files = dt.files;
                });
            });
        });
        return chain;
    }

    // Capture phase so we run before any other submit handlers; we fully stop
    // the first event, compress, then re-dispatch preserving the submitter.
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (SUBMITTING.has(form)) return;              // our own re-submit
        if (imageInputs(form).length === 0) return;    // nothing to shrink

        e.preventDefault();
        e.stopImmediatePropagation();

        var submitter = e.submitter || form.querySelector('[type="submit"]');
        var prevText;
        if (submitter) {
            submitter.disabled = true;
            if (submitter.tagName === 'BUTTON') {
                prevText = submitter.textContent;
                submitter.textContent = 'Optimizing image…';
            }
        }

        processForm(form).catch(function () {}).then(function () {
            if (submitter) {
                submitter.disabled = false;
                if (prevText !== undefined) submitter.textContent = prevText;
            }
            SUBMITTING.add(form);
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit(submitter || undefined);
            } else {
                form.submit();
            }
        });
    }, true);
})();
