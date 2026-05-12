@extends('layouts.app')

@section('content')
<div class="mx-auto w-full max-w-7xl space-y-6 p-4 md:p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Camera</h1>
            <p class="text-sm text-slate-600">Capture high-quality photos directly in your browser. No photo is uploaded to the server.</p>
        </div>
        <a href="{{ route('tools.index') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            Back to Tools Hub
        </a>
    </div>

    <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">
        Privacy notice: Photos stay in this browser tab memory only. They are temporary until you click <strong>Save to Laptop</strong> or leave this page.
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_320px] 2xl:grid-cols-[minmax(0,1fr)_360px]">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="relative aspect-video w-full bg-slate-900">
                <video id="camera-feed" class="h-full w-full object-cover" playsinline autoplay muted></video>
                <canvas id="camera-canvas" class="hidden"></canvas>
                <div id="camera-countdown" class="absolute left-3 top-3 hidden rounded-lg bg-black/70 px-3 py-1.5 text-lg font-semibold text-white">3</div>
                <div id="camera-empty" class="absolute inset-0 flex items-center justify-center p-6 text-center text-sm text-slate-300">
                    Click <strong class="mx-1 text-white">Start Camera</strong> to preview your webcam.
                </div>
            </div>

            <div class="border-t border-slate-200 p-4">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-5">
                    <div>
                        <label for="camera-device" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Camera Device</label>
                        <select id="camera-device" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700">
                            <option value="">Default Camera</option>
                        </select>
                    </div>
                    <div>
                        <label for="camera-format" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Image Format</label>
                        <select id="camera-format" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700">
                            <option value="image/jpeg">JPG (smaller)</option>
                            <option value="image/png">PNG (lossless)</option>
                        </select>
                    </div>
                    <div>
                        <label for="camera-quality" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">JPG Quality</label>
                        <input id="camera-quality" type="range" min="80" max="100" value="98" class="mt-2 w-full accent-blue-600">
                        <p class="mt-1 text-xs text-slate-500"><span id="camera-quality-label">98</span>%</p>
                    </div>
                    <div>
                        <label for="camera-timer" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Capture Timer</label>
                        <select id="camera-timer" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700">
                            <option value="0">Off</option>
                            <option value="3">3 seconds</option>
                            <option value="5">5 seconds</option>
                            <option value="10">10 seconds</option>
                            <option value="20">20 seconds</option>
                        </select>
                    </div>
                    <div>
                        <label for="print-size" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Print Size</label>
                        <select id="print-size" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700">
                            <option value="original">Original (camera resolution)</option>
                            <option value="1x1">1x1 inch</option>
                            <option value="2x2">2x2 inch</option>
                            <option value="passport">Passport (35x45 mm)</option>
                            <option value="visa">Visa (2x2 inch)</option>
                            <option value="wallet">Wallet (2.5x3.5 inch)</option>
                            <option value="2r">2R (2.5x3.5 inch)</option>
                            <option value="3r">3R (3.5x5 inch)</option>
                            <option value="4r">4R (4x6 inch)</option>
                            <option value="5r">5R (5x7 inch)</option>
                            <option value="6r">6R (6x8 inch)</option>
                            <option value="8r">8R (8x10 inch)</option>
                        </select>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-2 md:flex-nowrap">
                    <button id="start-camera" type="button" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Start Camera</button>
                    <button id="capture-photo" type="button" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500" disabled>Capture Photo</button>
                    <button id="resume-camera" type="button" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" disabled>Retake</button>
                    <div class="relative">
                        <button id="background-menu-btn" type="button" class="rounded-lg border border-violet-300 bg-violet-50 px-4 py-2 text-sm font-medium text-violet-700 hover:bg-violet-100">Background</button>
                        <div id="background-menu" class="absolute bottom-full right-0 z-20 mb-2 hidden min-w-52 rounded-xl border border-slate-200 bg-white p-1 shadow-lg">
                            <button id="background-original" type="button" class="block w-full rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-100">Use Original Background</button>
                            <button id="background-color-open" type="button" class="block w-full rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-100">Choose Background Color</button>
                            <button id="background-image-open" type="button" class="block w-full rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-100">Upload Background Image</button>
                        </div>
                    </div>
                    <div class="relative">
                        <button id="attire-menu-btn" type="button" class="rounded-lg border border-rose-300 bg-rose-50 px-4 py-2 text-sm font-medium text-rose-700 hover:bg-rose-100">Change Attire</button>
                        <div id="attire-menu" class="absolute bottom-full right-0 z-20 mb-2 hidden min-w-56 rounded-xl border border-slate-200 bg-white p-1 shadow-lg">
                            <button type="button" data-attire="none" class="attire-option block w-full rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-100">Original Attire</button>
                            <button type="button" data-attire="blazer" class="attire-option block w-full rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-100">Formal Blazer + Tie</button>
                            <button type="button" data-attire="polo" class="attire-option block w-full rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-100">Collared Polo</button>
                            <button type="button" data-attire="gown" class="attire-option block w-full rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-100">Graduation Gown</button>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-2 py-1.5">
                        <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Zoom</span>
                        <button id="zoom-out" type="button" class="rounded-md border border-slate-300 bg-white px-2 py-1 text-sm font-medium text-slate-700 hover:bg-slate-50" disabled>-</button>
                        <input id="camera-zoom" type="range" min="1" max="3" step="0.1" value="1" class="w-28 accent-blue-600" disabled>
                        <button id="zoom-in" type="button" class="rounded-md border border-slate-300 bg-white px-2 py-1 text-sm font-medium text-slate-700 hover:bg-slate-50" disabled>+</button>
                        <span id="zoom-value" class="min-w-[42px] text-xs font-semibold text-slate-700">1.0x</span>
                        <span id="zoom-mode" class="text-[11px] text-slate-500">(auto)</span>
                    </div>
                    <input id="background-image-input" type="file" accept="image/*" class="hidden">
                </div>

                <p class="mt-2 text-xs text-slate-500">Background mode: <span id="background-selection" class="font-semibold text-slate-700">Original</span></p>
                <p class="mt-1 text-xs text-slate-500">Output size: <span id="print-size-selection" class="font-semibold text-slate-700">Original (camera resolution)</span></p>
                <p class="mt-1 text-xs text-slate-500">Attire mode: <span id="attire-selection" class="font-semibold text-slate-700">Original</span></p>
            </div>
        </section>

        <aside class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Captured Photo</h2>
            <div class="mt-3 overflow-hidden rounded-xl border border-slate-200 bg-slate-100">
                <img id="photo-preview" alt="Captured preview" class="hidden h-auto w-full">
                <div id="preview-empty" class="aspect-[4/3] p-4 text-center text-sm text-slate-500">No photo captured yet.</div>
            </div>

            <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                After capture, your image is only stored temporarily in this tab. Save it locally to keep it.
            </div>

            <div id="camera-status" class="mt-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                Ready.
            </div>

            <button id="save-photo" type="button" class="mt-3 w-full rounded-lg border border-blue-300 bg-blue-50 px-4 py-2 text-sm font-medium text-blue-700 hover:bg-blue-100" disabled>Save to Laptop</button>
        </aside>
    </div>
</div>

<div id="color-picker-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 p-4">
    <div class="w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-5 shadow-xl">
        <h3 class="text-base font-semibold text-slate-800">Pick Background Color</h3>
        <p class="mt-1 text-sm text-slate-600">Select a color to use as your photo background.</p>

        <div class="mt-4">
            <input id="background-color-input" type="color" value="#14b8a6" class="h-12 w-full cursor-pointer rounded-lg border border-slate-300 bg-white p-1">
        </div>

        <div class="mt-5 flex justify-end gap-2">
            <button id="background-color-cancel" type="button" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</button>
            <button id="background-color-apply" type="button" class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-medium text-white hover:bg-violet-500">OK</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@mediapipe/selfie_segmentation/selfie_segmentation.js"></script>
<script>
(function () {
    const video = document.getElementById('camera-feed');
    const canvas = document.getElementById('camera-canvas');
    const emptyOverlay = document.getElementById('camera-empty');
    const deviceSelect = document.getElementById('camera-device');
    const formatSelect = document.getElementById('camera-format');
    const qualityInput = document.getElementById('camera-quality');
    const qualityLabel = document.getElementById('camera-quality-label');
    const timerSelect = document.getElementById('camera-timer');
    const printSizeSelect = document.getElementById('print-size');
    const printSizeSelectionEl = document.getElementById('print-size-selection');
    const zoomOutBtn = document.getElementById('zoom-out');
    const zoomInBtn = document.getElementById('zoom-in');
    const zoomRange = document.getElementById('camera-zoom');
    const zoomValueEl = document.getElementById('zoom-value');
    const zoomModeEl = document.getElementById('zoom-mode');
    const countdownEl = document.getElementById('camera-countdown');
    const backgroundMenuBtn = document.getElementById('background-menu-btn');
    const backgroundMenu = document.getElementById('background-menu');
    const backgroundOriginalBtn = document.getElementById('background-original');
    const backgroundColorOpenBtn = document.getElementById('background-color-open');
    const backgroundImageOpenBtn = document.getElementById('background-image-open');
    const backgroundImageInput = document.getElementById('background-image-input');
    const backgroundSelectionEl = document.getElementById('background-selection');
    const attireMenuBtn = document.getElementById('attire-menu-btn');
    const attireMenu = document.getElementById('attire-menu');
    const attireSelectionEl = document.getElementById('attire-selection');
    const attireOptionButtons = Array.from(document.querySelectorAll('.attire-option'));
    const colorPickerModal = document.getElementById('color-picker-modal');
    const backgroundColorInput = document.getElementById('background-color-input');
    const backgroundColorCancelBtn = document.getElementById('background-color-cancel');
    const backgroundColorApplyBtn = document.getElementById('background-color-apply');
    const startBtn = document.getElementById('start-camera');
    const captureBtn = document.getElementById('capture-photo');
    const resumeBtn = document.getElementById('resume-camera');
    const saveBtn = document.getElementById('save-photo');
    const preview = document.getElementById('photo-preview');
    const previewEmpty = document.getElementById('preview-empty');
    const statusEl = document.getElementById('camera-status');

    let stream = null;
    let lastBlob = null;
    let lastObjectUrl = null;
    let countdownIntervalId = null;
    let backgroundMode = 'original';
    let backgroundColor = '#14b8a6';
    let backgroundImage = null;
    let backgroundImageObjectUrl = null;
    let attireMode = 'none';
    let selfieSegmentation = null;
    let zoomLevel = 1;
    let usingHardwareZoom = false;
    let hardwareZoomMin = 1;
    let hardwareZoomMax = 3;
    let hardwareZoomStep = 0.1;

    const PRINT_DPI = 300;
    const printSizePresets = {
        original: { label: 'Original (camera resolution)' },
        '1x1': { label: '1x1 inch', widthIn: 1, heightIn: 1 },
        '2x2': { label: '2x2 inch', widthIn: 2, heightIn: 2 },
        passport: { label: 'Passport (35x45 mm)', widthMm: 35, heightMm: 45 },
        visa: { label: 'Visa (2x2 inch)', widthIn: 2, heightIn: 2 },
        wallet: { label: 'Wallet (2.5x3.5 inch)', widthIn: 2.5, heightIn: 3.5 },
        '2r': { label: '2R (2.5x3.5 inch)', widthIn: 2.5, heightIn: 3.5 },
        '3r': { label: '3R (3.5x5 inch)', widthIn: 3.5, heightIn: 5 },
        '4r': { label: '4R (4x6 inch)', widthIn: 4, heightIn: 6 },
        '5r': { label: '5R (5x7 inch)', widthIn: 5, heightIn: 7 },
        '6r': { label: '6R (6x8 inch)', widthIn: 6, heightIn: 8 },
        '8r': { label: '8R (8x10 inch)', widthIn: 8, heightIn: 10 }
    };

    function setStatus(text, type) {
        statusEl.textContent = text;
        statusEl.className = 'mt-3 rounded-lg border px-3 py-2 text-sm';

        if (type === 'success') {
            statusEl.classList.add('border-emerald-200', 'bg-emerald-50', 'text-emerald-700');
            return;
        }

        if (type === 'error') {
            statusEl.classList.add('border-rose-200', 'bg-rose-50', 'text-rose-700');
            return;
        }

        statusEl.classList.add('border-slate-200', 'bg-slate-50', 'text-slate-700');
    }

    function releaseObjectUrl() {
        if (lastObjectUrl) {
            URL.revokeObjectURL(lastObjectUrl);
            lastObjectUrl = null;
        }
    }

    function releaseBackgroundImageObjectUrl() {
        if (backgroundImageObjectUrl) {
            URL.revokeObjectURL(backgroundImageObjectUrl);
            backgroundImageObjectUrl = null;
        }
    }

    function updateBackgroundSelectionLabel() {
        if (backgroundMode === 'color') {
            backgroundSelectionEl.textContent = 'Color ' + backgroundColor.toUpperCase();
            return;
        }

        if (backgroundMode === 'image') {
            backgroundSelectionEl.textContent = 'Uploaded image';
            return;
        }

        backgroundSelectionEl.textContent = 'Original';
    }

    function updateAttireSelectionLabel() {
        const labelMap = {
            none: 'Original',
            blazer: 'Formal Blazer + Tie',
            polo: 'Collared Polo',
            gown: 'Graduation Gown'
        };

        attireSelectionEl.textContent = labelMap[attireMode] || 'Original';
    }

    function toPixelsFromInches(valueInches) {
        return Math.max(1, Math.round(valueInches * PRINT_DPI));
    }

    function toPixelsFromMm(valueMm) {
        return toPixelsFromInches(valueMm / 25.4);
    }

    function getSelectedPrintPreset() {
        const presetKey = printSizeSelect.value || 'original';
        return printSizePresets[presetKey] || printSizePresets.original;
    }

    function getTargetPrintDimensions() {
        const preset = getSelectedPrintPreset();

        if (!preset.widthIn && !preset.widthMm) {
            return null;
        }

        const widthPx = preset.widthIn
            ? toPixelsFromInches(preset.widthIn)
            : toPixelsFromMm(preset.widthMm);
        const heightPx = preset.heightIn
            ? toPixelsFromInches(preset.heightIn)
            : toPixelsFromMm(preset.heightMm);

        return {
            label: preset.label,
            widthPx: widthPx,
            heightPx: heightPx
        };
    }

    function updatePrintSizeLabel() {
        const preset = getSelectedPrintPreset();
        const dimensions = getTargetPrintDimensions();

        if (!dimensions) {
            printSizeSelectionEl.textContent = preset.label;
            return;
        }

        printSizeSelectionEl.textContent = preset.label + ' (' + dimensions.widthPx + 'x' + dimensions.heightPx + ' px @ 300 DPI)';
    }

    function setZoomControlsEnabled(enabled) {
        zoomOutBtn.disabled = !enabled;
        zoomInBtn.disabled = !enabled;
        zoomRange.disabled = !enabled;
    }

    function updateZoomButtonsState() {
        const min = Number(zoomRange.min);
        const max = Number(zoomRange.max);
        zoomOutBtn.disabled = zoomRange.disabled || zoomLevel <= min;
        zoomInBtn.disabled = zoomRange.disabled || zoomLevel >= max;
    }

    function applyDigitalZoomPreview() {
        if (usingHardwareZoom) {
            video.style.transform = 'none';
            return;
        }

        video.style.transformOrigin = 'center center';
        video.style.transform = zoomLevel > 1 ? 'scale(' + zoomLevel.toFixed(2) + ')' : 'none';
    }

    function updateZoomUI() {
        zoomRange.value = String(zoomLevel);
        zoomValueEl.textContent = zoomLevel.toFixed(1) + 'x';
        updateZoomButtonsState();
    }

    async function applyZoomLevel(level) {
        const clamped = Math.max(Number(zoomRange.min), Math.min(Number(zoomRange.max), Number(level)));
        zoomLevel = Math.round(clamped * 10) / 10;

        if (usingHardwareZoom && stream) {
            const track = stream.getVideoTracks()[0];
            if (track) {
                try {
                    await track.applyConstraints({ advanced: [{ zoom: zoomLevel }] });
                } catch (error) {
                    usingHardwareZoom = false;
                    zoomModeEl.textContent = '(digital)';
                }
            }
        }

        applyDigitalZoomPreview();
        updateZoomUI();
    }

    async function setupZoomForCurrentStream() {
        if (!stream) {
            setZoomControlsEnabled(false);
            zoomModeEl.textContent = '(auto)';
            zoomLevel = 1;
            updateZoomUI();
            return;
        }

        const track = stream.getVideoTracks()[0];
        usingHardwareZoom = false;

        if (track && typeof track.getCapabilities === 'function') {
            const capabilities = track.getCapabilities();
            if (capabilities && typeof capabilities.zoom === 'object') {
                usingHardwareZoom = true;
                hardwareZoomMin = Number(capabilities.zoom.min || 1);
                hardwareZoomMax = Number(capabilities.zoom.max || 3);
                hardwareZoomStep = Number(capabilities.zoom.step || 0.1);

                zoomRange.min = String(hardwareZoomMin);
                zoomRange.max = String(hardwareZoomMax);
                zoomRange.step = String(hardwareZoomStep);

                const settings = track.getSettings ? track.getSettings() : null;
                zoomLevel = Number((settings && settings.zoom) || hardwareZoomMin || 1);
                zoomModeEl.textContent = '(hardware)';
                setZoomControlsEnabled(true);
                await applyZoomLevel(zoomLevel);
                return;
            }
        }

        zoomRange.min = '1';
        zoomRange.max = '3';
        zoomRange.step = '0.1';
        zoomLevel = Math.max(1, zoomLevel || 1);
        zoomModeEl.textContent = '(digital)';
        setZoomControlsEnabled(true);
        applyDigitalZoomPreview();
        updateZoomUI();
    }

    function drawVideoFrameToCanvas(context, width, height) {
        if (!usingHardwareZoom && zoomLevel > 1) {
            const srcWidth = video.videoWidth / zoomLevel;
            const srcHeight = video.videoHeight / zoomLevel;
            const srcX = (video.videoWidth - srcWidth) / 2;
            const srcY = (video.videoHeight - srcHeight) / 2;
            context.drawImage(video, srcX, srcY, srcWidth, srcHeight, 0, 0, width, height);
            return;
        }

        context.drawImage(video, 0, 0, width, height);
    }

    function applyPrintSizePreset() {
        const target = getTargetPrintDimensions();
        if (!target) {
            return {
                label: getSelectedPrintPreset().label,
                width: canvas.width,
                height: canvas.height,
                isOriginal: true
            };
        }

        const sourceCanvas = document.createElement('canvas');
        sourceCanvas.width = canvas.width;
        sourceCanvas.height = canvas.height;
        const sourceCtx = sourceCanvas.getContext('2d', { alpha: false });
        sourceCtx.drawImage(canvas, 0, 0);

        const sourceRatio = sourceCanvas.width / sourceCanvas.height;
        const targetRatio = target.widthPx / target.heightPx;

        let cropWidth = sourceCanvas.width;
        let cropHeight = sourceCanvas.height;
        let offsetX = 0;
        let offsetY = 0;

        if (sourceRatio > targetRatio) {
            cropWidth = Math.round(sourceCanvas.height * targetRatio);
            offsetX = Math.round((sourceCanvas.width - cropWidth) / 2);
        } else if (sourceRatio < targetRatio) {
            cropHeight = Math.round(sourceCanvas.width / targetRatio);
            offsetY = Math.round((sourceCanvas.height - cropHeight) / 2);
        }

        canvas.width = target.widthPx;
        canvas.height = target.heightPx;

        const outputCtx = canvas.getContext('2d', { alpha: false, willReadFrequently: false });
        outputCtx.drawImage(
            sourceCanvas,
            offsetX,
            offsetY,
            cropWidth,
            cropHeight,
            0,
            0,
            target.widthPx,
            target.heightPx
        );

        return {
            label: target.label,
            width: target.widthPx,
            height: target.heightPx,
            isOriginal: false
        };
    }

    function openBackgroundMenu() {
        backgroundMenu.classList.remove('hidden');
    }

    function closeBackgroundMenu() {
        backgroundMenu.classList.add('hidden');
    }

    function openAttireMenu() {
        attireMenu.classList.remove('hidden');
    }

    function closeAttireMenu() {
        attireMenu.classList.add('hidden');
    }

    function openColorModal() {
        colorPickerModal.classList.remove('hidden');
        colorPickerModal.classList.add('flex');
    }

    function closeColorModal() {
        colorPickerModal.classList.add('hidden');
        colorPickerModal.classList.remove('flex');
    }

    function drawCoverImage(ctx, image, width, height) {
        const imgW = image.width;
        const imgH = image.height;

        if (!imgW || !imgH) {
            return;
        }

        const scale = Math.max(width / imgW, height / imgH);
        const drawW = imgW * scale;
        const drawH = imgH * scale;
        const offsetX = (width - drawW) / 2;
        const offsetY = (height - drawH) / 2;
        ctx.drawImage(image, offsetX, offsetY, drawW, drawH);
    }

    function buildMaskCanvas(maskSource, width, height) {
        const maskCanvas = document.createElement('canvas');
        maskCanvas.width = width;
        maskCanvas.height = height;
        const maskCtx = maskCanvas.getContext('2d', { alpha: true });
        maskCtx.drawImage(maskSource, 0, 0, width, height);
        return maskCanvas;
    }

    function getPersonBoundingBox(maskCanvas, width, height) {
        const maskCtx = maskCanvas.getContext('2d', { alpha: true });
        const pixels = maskCtx.getImageData(0, 0, width, height).data;

        let minX = width;
        let minY = height;
        let maxX = 0;
        let maxY = 0;
        let found = false;

        for (let y = 0; y < height; y += 2) {
            for (let x = 0; x < width; x += 2) {
                const index = (y * width + x) * 4;
                const confidence = pixels[index];
                if (confidence < 120) {
                    continue;
                }

                found = true;
                if (x < minX) minX = x;
                if (y < minY) minY = y;
                if (x > maxX) maxX = x;
                if (y > maxY) maxY = y;
            }
        }

        if (!found) {
            return null;
        }

        const boxWidth = Math.max(1, maxX - minX);
        const boxHeight = Math.max(1, maxY - minY);

        return { x: minX, y: minY, width: boxWidth, height: boxHeight };
    }

    function drawAttireTemplate(ctx, box, mode) {
        const x = box.x;
        const y = box.y;
        const w = box.width;
        const h = box.height;

        const shoulderY = y + h * 0.36;
        const chestY = y + h * 0.46;
        const lowerY = y + h * 0.98;
        const centerX = x + w * 0.5;

        if (mode === 'blazer') {
            ctx.fillStyle = '#1f2937';
            ctx.beginPath();
            ctx.moveTo(x + w * 0.06, shoulderY);
            ctx.lineTo(x + w * 0.4, chestY);
            ctx.lineTo(x + w * 0.32, lowerY);
            ctx.lineTo(x + w * 0.02, lowerY);
            ctx.closePath();
            ctx.fill();

            ctx.beginPath();
            ctx.moveTo(x + w * 0.94, shoulderY);
            ctx.lineTo(x + w * 0.6, chestY);
            ctx.lineTo(x + w * 0.68, lowerY);
            ctx.lineTo(x + w * 0.98, lowerY);
            ctx.closePath();
            ctx.fill();

            ctx.fillStyle = '#f8fafc';
            ctx.beginPath();
            ctx.moveTo(centerX - w * 0.12, chestY);
            ctx.lineTo(centerX + w * 0.12, chestY);
            ctx.lineTo(centerX + w * 0.09, lowerY);
            ctx.lineTo(centerX - w * 0.09, lowerY);
            ctx.closePath();
            ctx.fill();

            ctx.fillStyle = '#0f172a';
            ctx.beginPath();
            ctx.moveTo(centerX, chestY + h * 0.03);
            ctx.lineTo(centerX + w * 0.05, y + h * 0.76);
            ctx.lineTo(centerX, lowerY);
            ctx.lineTo(centerX - w * 0.05, y + h * 0.76);
            ctx.closePath();
            ctx.fill();
            return;
        }

        if (mode === 'polo') {
            ctx.fillStyle = '#1d4ed8';
            ctx.beginPath();
            ctx.moveTo(x + w * 0.08, shoulderY);
            ctx.lineTo(x + w * 0.92, shoulderY);
            ctx.lineTo(x + w * 0.98, lowerY);
            ctx.lineTo(x + w * 0.02, lowerY);
            ctx.closePath();
            ctx.fill();

            ctx.fillStyle = '#eff6ff';
            ctx.beginPath();
            ctx.moveTo(centerX - w * 0.11, shoulderY + h * 0.02);
            ctx.lineTo(centerX, y + h * 0.58);
            ctx.lineTo(centerX + w * 0.11, shoulderY + h * 0.02);
            ctx.closePath();
            ctx.fill();
            return;
        }

        if (mode === 'gown') {
            ctx.fillStyle = '#111827';
            ctx.beginPath();
            ctx.moveTo(x + w * 0.02, shoulderY);
            ctx.lineTo(x + w * 0.98, shoulderY);
            ctx.lineTo(x + w * 0.86, lowerY);
            ctx.lineTo(x + w * 0.14, lowerY);
            ctx.closePath();
            ctx.fill();

            ctx.fillStyle = '#f59e0b';
            ctx.beginPath();
            ctx.moveTo(centerX - w * 0.12, shoulderY + h * 0.01);
            ctx.lineTo(centerX, y + h * 0.62);
            ctx.lineTo(centerX + w * 0.12, shoulderY + h * 0.01);
            ctx.closePath();
            ctx.fill();
        }
    }

    async function initializeSegmentation() {
        if (selfieSegmentation) {
            return;
        }

        if (!window.SelfieSegmentation) {
            throw new Error('Background segmentation module not loaded.');
        }

        selfieSegmentation = new window.SelfieSegmentation({
            locateFile: function (file) {
                return 'https://cdn.jsdelivr.net/npm/@mediapipe/selfie_segmentation/' + file;
            }
        });

        selfieSegmentation.setOptions({ modelSelection: 1 });
    }

    function runSegmentation(inputCanvas) {
        return new Promise(function (resolve, reject) {
            if (!selfieSegmentation) {
                reject(new Error('Segmentation not initialized.'));
                return;
            }

            let settled = false;
            selfieSegmentation.onResults(function (results) {
                if (settled) {
                    return;
                }

                settled = true;
                resolve(results);
            });

            selfieSegmentation.send({ image: inputCanvas }).catch(function (error) {
                settled = true;
                reject(error);
            });
        });
    }

    async function applySelectedBackground(width, height) {
        if (backgroundMode === 'original') {
            return true;
        }

        await initializeSegmentation();
        const results = await runSegmentation(canvas);

        if (!results || !results.segmentationMask) {
            throw new Error('No segmentation mask returned.');
        }

        const sourceCanvas = document.createElement('canvas');
        sourceCanvas.width = width;
        sourceCanvas.height = height;
        const sourceCtx = sourceCanvas.getContext('2d', { alpha: true });
        sourceCtx.drawImage(canvas, 0, 0, width, height);

        const personCanvas = document.createElement('canvas');
        personCanvas.width = width;
        personCanvas.height = height;
        const personCtx = personCanvas.getContext('2d', { alpha: true });
        personCtx.drawImage(sourceCanvas, 0, 0, width, height);
        personCtx.globalCompositeOperation = 'destination-in';
        personCtx.drawImage(results.segmentationMask, 0, 0, width, height);
        personCtx.globalCompositeOperation = 'source-over';

        const bgCanvas = document.createElement('canvas');
        bgCanvas.width = width;
        bgCanvas.height = height;
        const bgCtx = bgCanvas.getContext('2d', { alpha: false });

        if (backgroundMode === 'color') {
            bgCtx.fillStyle = backgroundColor;
            bgCtx.fillRect(0, 0, width, height);
        } else if (backgroundMode === 'image' && backgroundImage) {
            drawCoverImage(bgCtx, backgroundImage, width, height);
        } else {
            return true;
        }

        const ctx = canvas.getContext('2d', { alpha: false, willReadFrequently: false });
        ctx.clearRect(0, 0, width, height);
        ctx.drawImage(bgCanvas, 0, 0, width, height);
        ctx.drawImage(personCanvas, 0, 0, width, height);
        return true;
    }

    async function applyAttireOverlay(width, height) {
        if (attireMode === 'none') {
            return;
        }

        await initializeSegmentation();
        const results = await runSegmentation(canvas);
        if (!results || !results.segmentationMask) {
            throw new Error('No segmentation mask returned.');
        }

        const maskCanvas = buildMaskCanvas(results.segmentationMask, width, height);
        const bbox = getPersonBoundingBox(maskCanvas, width, height);
        if (!bbox) {
            return;
        }

        const attireCanvas = document.createElement('canvas');
        attireCanvas.width = width;
        attireCanvas.height = height;
        const attireCtx = attireCanvas.getContext('2d', { alpha: true });

        drawAttireTemplate(attireCtx, bbox, attireMode);
        attireCtx.globalCompositeOperation = 'destination-in';
        attireCtx.drawImage(maskCanvas, 0, 0, width, height);
        attireCtx.globalCompositeOperation = 'source-over';

        const ctx = canvas.getContext('2d', { alpha: false, willReadFrequently: false });
        ctx.drawImage(attireCanvas, 0, 0, width, height);
    }

    function clearCountdown() {
        if (countdownIntervalId) {
            clearInterval(countdownIntervalId);
            countdownIntervalId = null;
        }

        countdownEl.classList.add('hidden');
        startBtn.disabled = false;
    }

    async function loadDevices() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) {
            return;
        }

        const devices = await navigator.mediaDevices.enumerateDevices();
        const cameras = devices.filter((device) => device.kind === 'videoinput');

        const previous = deviceSelect.value;
        deviceSelect.innerHTML = '<option value="">Default Camera</option>';

        cameras.forEach((camera, index) => {
            const option = document.createElement('option');
            option.value = camera.deviceId;
            option.textContent = camera.label || 'Camera ' + (index + 1);
            deviceSelect.appendChild(option);
        });

        if (previous && cameras.some((camera) => camera.deviceId === previous)) {
            deviceSelect.value = previous;
        }
    }

    function stopStream() {
        if (!stream) {
            return;
        }

        stream.getTracks().forEach((track) => track.stop());
        stream = null;
        video.srcObject = null;
        video.style.transform = 'none';
        setZoomControlsEnabled(false);
        zoomModeEl.textContent = '(auto)';
        zoomLevel = 1;
        updateZoomUI();
    }

    async function tryGetStream(constraintsList) {
        let lastError = null;

        for (const constraints of constraintsList) {
            try {
                const mediaStream = await navigator.mediaDevices.getUserMedia(constraints);
                return mediaStream;
            } catch (error) {
                lastError = error;
            }
        }

        throw lastError || new Error('Unable to access camera stream.');
    }

    function getCameraErrorMessage(error) {
        if (!window.isSecureContext) {
            return 'Camera needs a secure page (HTTPS or localhost). Open this tool from localhost/HTTPS and try again.';
        }

        if (!error || !error.name) {
            return 'Unable to start camera. Please try again.';
        }

        if (error.name === 'NotAllowedError' || error.name === 'SecurityError') {
            return 'Camera permission was blocked. Allow camera access in your browser and retry.';
        }

        if (error.name === 'NotFoundError' || error.name === 'DevicesNotFoundError') {
            return 'No camera device was found. Connect a camera and try again.';
        }

        if (error.name === 'NotReadableError' || error.name === 'TrackStartError') {
            return 'Camera is currently busy in another app/tab. Close other camera apps and retry.';
        }

        if (error.name === 'OverconstrainedError' || error.name === 'ConstraintNotSatisfiedError') {
            return 'Selected camera settings are not supported. Try default camera and start again.';
        }

        return 'Unable to start camera (' + error.name + '). Please try again.';
    }

    async function startCamera() {
        try {
            stopStream();
            clearCountdown();

            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                setStatus('Camera access is not supported in this browser.', 'error');
                return;
            }

            const selectedDevice = deviceSelect.value;
            const constraintsList = [];

            if (selectedDevice) {
                constraintsList.push({
                    audio: false,
                    video: {
                        deviceId: { exact: selectedDevice },
                        width: { ideal: 3840 },
                        height: { ideal: 2160 }
                    }
                });

                constraintsList.push({
                    audio: false,
                    video: {
                        deviceId: { ideal: selectedDevice },
                        width: { ideal: 1920 },
                        height: { ideal: 1080 }
                    }
                });
            }

            constraintsList.push({
                audio: false,
                video: {
                    width: { ideal: 3840 },
                    height: { ideal: 2160 },
                    facingMode: 'user'
                }
            });

            constraintsList.push({
                audio: false,
                video: {
                    width: { ideal: 1920 },
                    height: { ideal: 1080 }
                }
            });

            constraintsList.push({
                audio: false,
                video: true
            });

            stream = await tryGetStream(constraintsList);
            video.srcObject = stream;
            await video.play();

            emptyOverlay.classList.add('hidden');
            captureBtn.disabled = false;
            resumeBtn.disabled = true;

            await loadDevices();
            await setupZoomForCurrentStream();
            setStatus('Camera started. Capture a photo when ready.', 'success');
        } catch (error) {
            clearCountdown();
            setZoomControlsEnabled(false);
            setStatus(getCameraErrorMessage(error), 'error');
        }
    }

    async function captureSnapshot() {
        if (!stream || video.videoWidth === 0 || video.videoHeight === 0) {
            setStatus('Camera is not ready yet. Start camera first.', 'error');
            return;
        }

        captureBtn.disabled = true;

        const width = video.videoWidth;
        const height = video.videoHeight;

        canvas.width = width;
        canvas.height = height;

        const context = canvas.getContext('2d', { alpha: false, willReadFrequently: false });
        drawVideoFrameToCanvas(context, width, height);

        if (backgroundMode !== 'original') {
            try {
                await applySelectedBackground(width, height);
            } catch (error) {
                setStatus('Background effect could not be applied. Original background kept.', 'default');
            }
        }

        if (attireMode !== 'none') {
            try {
                await applyAttireOverlay(width, height);
            } catch (error) {
                setStatus('Attire effect could not be applied. Original attire kept.', 'default');
            }
        }

        const outputInfo = applyPrintSizePreset();

        const mimeType = formatSelect.value;
        const quality = Math.max(0.8, Math.min(1, Number(qualityInput.value) / 100));

        canvas.toBlob(function (blob) {
            if (!blob) {
                setStatus('Capture failed. Please try again.', 'error');
                captureBtn.disabled = false;
                return;
            }

            lastBlob = blob;
            releaseObjectUrl();
            lastObjectUrl = URL.createObjectURL(blob);

            preview.src = lastObjectUrl;
            preview.classList.remove('hidden');
            previewEmpty.classList.add('hidden');

            saveBtn.disabled = false;
            resumeBtn.disabled = false;
            captureBtn.disabled = false;

            const megaPixels = ((outputInfo.width * outputInfo.height) / 1000000).toFixed(1);
            const sizeText = outputInfo.isOriginal
                ? outputInfo.width + 'x' + outputInfo.height + ' (Original)'
                : outputInfo.width + 'x' + outputInfo.height + ' (' + outputInfo.label + ' @ 300 DPI)';
            setStatus('Photo captured at ' + sizeText + ' - ' + megaPixels + ' MP. Save locally to keep it.', 'success');
        }, mimeType, quality);
    }

    function capturePhoto() {
        if (!stream || video.videoWidth === 0 || video.videoHeight === 0) {
            setStatus('Camera is not ready yet. Start camera first.', 'error');
            return;
        }

        clearCountdown();

        const timerSeconds = Number(timerSelect.value || 0);
        if (timerSeconds <= 0) {
            captureSnapshot();
            return;
        }

        let remaining = timerSeconds;
        captureBtn.disabled = true;
        resumeBtn.disabled = true;
        startBtn.disabled = true;
        countdownEl.textContent = String(remaining);
        countdownEl.classList.remove('hidden');
        setStatus('Capturing in ' + remaining + ' second(s)... Hold steady.', 'default');

        countdownIntervalId = setInterval(function () {
            remaining -= 1;

            if (remaining > 0) {
                countdownEl.textContent = String(remaining);
                setStatus('Capturing in ' + remaining + ' second(s)... Hold steady.', 'default');
                return;
            }

            clearCountdown();
            captureBtn.disabled = false;
            captureSnapshot();
        }, 1000);
    }

    async function saveToLaptop() {
        if (!lastBlob) {
            setStatus('Capture a photo first before saving.', 'error');
            return;
        }

        const extension = formatSelect.value === 'image/png' ? 'png' : 'jpg';
        const fileName = 'camera-' + new Date().toISOString().replace(/[:.]/g, '-').slice(0, 19) + '.' + extension;

        try {
            if ('showSaveFilePicker' in window) {
                const handle = await window.showSaveFilePicker({
                    suggestedName: fileName,
                    types: [
                        {
                            description: extension === 'png' ? 'PNG Image' : 'JPEG Image',
                            accept: {
                                [formatSelect.value]: ['.' + extension]
                            }
                        }
                    ]
                });

                const writable = await handle.createWritable();
                await writable.write(lastBlob);
                await writable.close();

                setStatus('Saved successfully to your laptop. Photo was never uploaded to the server.', 'success');
                return;
            }

            const link = document.createElement('a');
            link.href = lastObjectUrl;
            link.download = fileName;
            document.body.appendChild(link);
            link.click();
            link.remove();

            setStatus('Download started. Save the file in your Downloads folder to keep it.', 'success');
        } catch (error) {
            if (error && error.name === 'AbortError') {
                setStatus('Save canceled. Photo is still temporary in this tab memory.', 'default');
                return;
            }

            setStatus('Unable to save automatically. Try again and choose a local folder.', 'error');
        }
    }

    startBtn.addEventListener('click', startCamera);
    captureBtn.addEventListener('click', capturePhoto);
    resumeBtn.addEventListener('click', function () {
        clearCountdown();
        lastBlob = null;
        releaseObjectUrl();
        preview.removeAttribute('src');
        preview.classList.add('hidden');
        previewEmpty.classList.remove('hidden');
        saveBtn.disabled = true;
        setStatus('Ready for another shot. Capture again when prepared.', 'default');
    });
    saveBtn.addEventListener('click', saveToLaptop);

    deviceSelect.addEventListener('change', function () {
        if (stream) {
            startCamera();
        }
    });

    qualityInput.addEventListener('input', function () {
        qualityLabel.textContent = qualityInput.value;
    });

    printSizeSelect.addEventListener('change', function () {
        updatePrintSizeLabel();
    });

    zoomRange.addEventListener('input', function () {
        applyZoomLevel(Number(zoomRange.value));
    });

    zoomInBtn.addEventListener('click', function () {
        const step = Number(zoomRange.step || 0.1);
        applyZoomLevel(zoomLevel + step);
    });

    zoomOutBtn.addEventListener('click', function () {
        const step = Number(zoomRange.step || 0.1);
        applyZoomLevel(zoomLevel - step);
    });

    backgroundMenuBtn.addEventListener('click', function (event) {
        event.stopPropagation();
        closeAttireMenu();
        if (backgroundMenu.classList.contains('hidden')) {
            openBackgroundMenu();
            return;
        }

        closeBackgroundMenu();
    });

    backgroundOriginalBtn.addEventListener('click', function () {
        backgroundMode = 'original';
        updateBackgroundSelectionLabel();
        closeBackgroundMenu();
        setStatus('Background reset to original camera view.', 'default');
    });

    backgroundColorOpenBtn.addEventListener('click', function () {
        closeBackgroundMenu();
        backgroundColorInput.value = backgroundColor;
        openColorModal();
    });

    backgroundImageOpenBtn.addEventListener('click', function () {
        closeBackgroundMenu();
        backgroundImageInput.click();
    });

    backgroundColorCancelBtn.addEventListener('click', function () {
        closeColorModal();
    });

    backgroundColorApplyBtn.addEventListener('click', function () {
        backgroundColor = backgroundColorInput.value || '#14b8a6';
        backgroundMode = 'color';
        updateBackgroundSelectionLabel();
        closeColorModal();
        setStatus('Color background selected. It will be applied on next capture.', 'success');
    });

    colorPickerModal.addEventListener('click', function (event) {
        if (event.target === colorPickerModal) {
            closeColorModal();
        }
    });

    backgroundImageInput.addEventListener('change', function () {
        const file = backgroundImageInput.files && backgroundImageInput.files[0];
        if (!file) {
            return;
        }

        if (!file.type.startsWith('image/')) {
            setStatus('Please choose a valid image file for background.', 'error');
            backgroundImageInput.value = '';
            return;
        }

        releaseBackgroundImageObjectUrl();
        backgroundImageObjectUrl = URL.createObjectURL(file);

        const uploadedImage = new Image();
        uploadedImage.onload = function () {
            backgroundImage = uploadedImage;
            backgroundMode = 'image';
            updateBackgroundSelectionLabel();
            setStatus('Background image selected. It will be applied on next capture.', 'success');
        };
        uploadedImage.onerror = function () {
            setStatus('Unable to read background image. Please choose another file.', 'error');
            releaseBackgroundImageObjectUrl();
        };
        uploadedImage.src = backgroundImageObjectUrl;

        backgroundImageInput.value = '';
    });

    attireMenuBtn.addEventListener('click', function (event) {
        event.stopPropagation();
        closeBackgroundMenu();
        if (attireMenu.classList.contains('hidden')) {
            openAttireMenu();
            return;
        }

        closeAttireMenu();
    });

    attireOptionButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            attireMode = button.getAttribute('data-attire') || 'none';
            updateAttireSelectionLabel();
            closeAttireMenu();
            if (attireMode === 'none') {
                setStatus('Attire reset to original.', 'default');
            } else {
                setStatus('Attire set to ' + attireSelectionEl.textContent + '. It will be applied on next capture.', 'success');
            }
        });
    });

    document.addEventListener('click', function (event) {
        if (!backgroundMenu.contains(event.target) && event.target !== backgroundMenuBtn) {
            closeBackgroundMenu();
        }

        if (!attireMenu.contains(event.target) && event.target !== attireMenuBtn) {
            closeAttireMenu();
        }
    });

    window.addEventListener('beforeunload', function () {
        clearCountdown();
        stopStream();
        releaseObjectUrl();
        releaseBackgroundImageObjectUrl();
    });

    loadDevices().catch(function () {
        // No-op: labels may stay hidden until camera permission is granted.
    });
    updateBackgroundSelectionLabel();
    updateAttireSelectionLabel();
    updatePrintSizeLabel();
    setZoomControlsEnabled(false);
    updateZoomUI();
})();
</script>
@endsection
