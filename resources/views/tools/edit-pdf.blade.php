@extends('layouts.app')

@section('content')
<div class="p-4 md:p-6">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-slate-800">Edit PDF</h1>
            <p class="text-sm text-slate-600 mt-1">Edit styled text, add or delete images, add transparent signature, and highlight text.</p>
        </div>

        <a href="{{ route('tools.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            Back to Tools Hub
        </a>
    </div>

    <div class="space-y-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
            <div class="overflow-x-auto overflow-y-hidden pb-1">
                <div class="inline-flex min-w-max items-center gap-2">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-2 py-2">
                        <input id="pdfInput" type="file" accept="application/pdf" class="block w-[220px] rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-700">
                    </div>

                    <div class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-2 py-2">
                        <button id="prevPageBtn" type="button" class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50">Prev</button>
                        <div id="pageIndicator" class="text-sm text-slate-700 font-medium whitespace-nowrap">Page 0 / 0</div>
                        <button id="nextPageBtn" type="button" class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50">Next</button>
                        <input id="jumpPageInput" type="number" min="1" step="1" placeholder="#" class="w-[74px] rounded-md border border-slate-300 bg-white px-2 py-1.5 text-sm text-slate-700" disabled>
                        <button id="jumpPageBtn" type="button" class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50 disabled:opacity-60 disabled:cursor-not-allowed" disabled>Go</button>
                    </div>

                    <select id="actionSelect" class="w-[88px] rounded-md border border-slate-300 bg-white px-2 py-2 text-sm text-slate-700">
                        <option value="">Actions</option>
                        <option value="select">Select</option>
                        <option value="highlight">Highlight</option>
                        <option value="delete">Delete Selected</option>
                    </select>

                    <div class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-2 py-2">
                        <button id="addTextBtn" type="button" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 whitespace-nowrap">Add Text</button>
                        <select id="textFont" class="w-[130px] rounded-md border border-slate-300 bg-white px-2 py-2 text-sm text-slate-700">
                            <option value="Helvetica">Helvetica</option>
                            <option value="Times-Roman">Times</option>
                            <option value="Courier">Courier</option>
                        </select>
                        <input id="textSize" type="number" min="8" max="120" value="24" class="w-[84px] rounded-md border border-slate-300 bg-white px-2.5 py-2 text-sm text-slate-700" placeholder="Size">
                        <select id="styleSelect" class="w-[90px] rounded-md border border-slate-300 bg-white px-2 py-2 text-sm text-slate-700">
                            <option value="">Style</option>
                            <option value="bold">Bold</option>
                            <option value="italic">Italic</option>
                            <option value="underline">Underline</option>
                            <option value="align-left">Left</option>
                            <option value="align-center">Center</option>
                            <option value="align-right">Right</option>
                        </select>
                        <input id="textColor" type="color" value="#111827" class="h-10 w-[52px] rounded-md border border-slate-300 bg-white p-1">
                    </div>
                    <div class="inline-flex h-10 items-center overflow-hidden rounded-md border border-slate-300 bg-white" title="Eraser radius">
                        <select id="eraserSize" class="h-full w-[108px] border-0 bg-transparent px-2 text-sm text-slate-700 focus:outline-none focus:ring-0">
                            <option value="8">8 px</option>
                            <option value="12">12 px</option>
                            <option value="16">16 px</option>
                            <option value="24" selected>24 px</option>
                            <option value="32">32 px</option>
                        </select>
                        <button id="modeErase" type="button" class="inline-flex h-full w-10 items-center justify-center border-l border-slate-300 text-slate-700 hover:bg-slate-50" title="Erase" aria-label="Erase">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M20.77 7.23a2 2 0 0 0 0-2.83l-1.17-1.17a2 2 0 0 0-2.83 0L4 16v4h4z"></path>
                                <path d="M7 21h13"></path>
                            </svg>
                        </button>
                    </div>

                    <select id="insertSelect" class="w-[90px] rounded-md border border-slate-300 bg-white px-2 py-2 text-sm text-slate-700">
                        <option value="">Insert</option>
                        <option value="image">Add Image</option>
                        <option value="signature">Add Signature</option>
                    </select>

                    <button id="downloadPdfBtn" type="button" class="rounded-md bg-slate-800 px-3 py-2 text-sm font-medium text-white hover:bg-slate-700 disabled:opacity-60 disabled:cursor-not-allowed whitespace-nowrap" disabled>
                        Download
                    </button>
                    <button id="rotatePageBtn" type="button" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-60 disabled:cursor-not-allowed whitespace-nowrap" disabled>
                        Rotate
                    </button>
                    <button id="fitPageBtn" type="button" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-60 disabled:cursor-not-allowed whitespace-nowrap" disabled>
                        Fit to Page
                    </button>
                    <button id="printBtn" type="button" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-60 disabled:cursor-not-allowed whitespace-nowrap" disabled>
                        Print
                    </button>
                    <button id="clearDraftBtn" type="button" class="rounded-md border border-rose-300 bg-white px-3 py-2 text-sm font-medium text-rose-700 hover:bg-rose-50 whitespace-nowrap">
                        Clear Draft
                    </button>
                    <button id="undoBtn" type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-md border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 disabled:opacity-60 disabled:cursor-not-allowed" title="Undo" aria-label="Undo" disabled>
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M3 7v6h6"></path>
                            <path d="M3 13c2.5-5 7-8 12-8 3.5 0 6.5 1.5 9 4"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <input id="imageInput" type="file" accept="image/*" class="hidden">
            <input id="signatureInput" type="file" accept="image/*" class="hidden">
        </div>

        <section id="editorSection" class="rounded-2xl border border-slate-200 bg-gray-100 p-3 md:p-4 shadow-sm min-h-[680px]">
            <div id="editorEmpty" class="h-full min-h-[640px] flex items-center justify-center text-slate-500 text-sm">
                Upload a PDF file to start editing.
            </div>

            <div id="editorCanvasWrap" class="hidden overflow-auto">
                <div class="min-w-full flex justify-center">
                    <canvas id="pdfEditorCanvas" class="block"></canvas>
                </div>
            </div>
        </section>

        <div id="fitOverlay" class="hidden fixed inset-0 z-[999] bg-slate-900/45 backdrop-blur-md">
            <div class="relative h-full w-full bg-gray-100">
                <button id="closeFitBtn" type="button" class="absolute right-4 top-4 z-10 inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-300 bg-white text-slate-700 shadow-sm hover:bg-slate-50" title="Close fit mode" aria-label="Close fit mode">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M18 6 6 18"></path>
                        <path d="m6 6 12 12"></path>
                    </svg>
                </button>
                <div id="fitCanvasHost" class="h-full overflow-auto pt-14 px-3 pb-3">
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.0/fabric.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/pdf-lib@1.17.1/dist/pdf-lib.min.js"></script>

<script>
(() => {
    const pdfInput = document.getElementById('pdfInput');
    const imageInput = document.getElementById('imageInput');
    const signatureInput = document.getElementById('signatureInput');
    const pageIndicator = document.getElementById('pageIndicator');
    const prevPageBtn = document.getElementById('prevPageBtn');
    const nextPageBtn = document.getElementById('nextPageBtn');
    const jumpPageInput = document.getElementById('jumpPageInput');
    const jumpPageBtn = document.getElementById('jumpPageBtn');
    const addTextBtn = document.getElementById('addTextBtn');
    const actionSelect = document.getElementById('actionSelect');
    const styleSelect = document.getElementById('styleSelect');
    const insertSelect = document.getElementById('insertSelect');
    const modeEraseBtn = document.getElementById('modeErase');
    const downloadPdfBtn = document.getElementById('downloadPdfBtn');
    const rotatePageBtn = document.getElementById('rotatePageBtn');
    const fitPageBtn = document.getElementById('fitPageBtn');
    const printBtn = document.getElementById('printBtn');
    const clearDraftBtn = document.getElementById('clearDraftBtn');
    const undoBtn = document.getElementById('undoBtn');

    const textFont = document.getElementById('textFont');
    const textSize = document.getElementById('textSize');
    const textColor = document.getElementById('textColor');
    const eraserSize = document.getElementById('eraserSize');

    const editorEmpty = document.getElementById('editorEmpty');
    const editorCanvasWrap = document.getElementById('editorCanvasWrap');
    const editorSection = document.getElementById('editorSection');
    const fitOverlay = document.getElementById('fitOverlay');
    const fitCanvasHost = document.getElementById('fitCanvasHost');
    const closeFitBtn = document.getElementById('closeFitBtn');

    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';

    let fabricCanvas = new fabric.Canvas('pdfEditorCanvas', {
        selection: true,
        preserveObjectStacking: true,
    });

    let pdfDoc = null;
    let pdfBytes = null;
    let currentPage = 1;
    let pageCount = 0;
    let pageViewports = {};
    let pageRotations = {};
    let pageStates = {};
    let pageHistory = {};
    let pageHistoryIndex = {};
    let mode = 'select';
    let drawingHighlight = false;
    let highlightStart = null;
    let activeHighlightRect = null;
    const PREVIEW_BASE_SCALE = 1.8;
    const PREVIEW_OUTPUT_SCALE = Math.max(1, window.devicePixelRatio || 1);
    const DRAFT_DB_NAME = 'schoollms-edit-pdf';
    const DRAFT_STORE_NAME = 'drafts';
    const DRAFT_KEY = 'admin-tools-edit-pdf-v1';
    let draftPersistTimer = null;
    let fitModeActive = false;
    let editorCanvasOriginalParent = null;
    let textStyleState = {
        bold: false,
        italic: false,
        underline: false,
    };

    if (editorCanvasWrap) {
        editorCanvasOriginalParent = editorCanvasWrap.parentElement;
    }

    function cloneJson(value) {
        return JSON.parse(JSON.stringify(value));
    }

    function openDraftDb() {
        return new Promise((resolve, reject) => {
            if (!window.indexedDB) {
                reject(new Error('IndexedDB not available'));
                return;
            }

            const request = indexedDB.open(DRAFT_DB_NAME, 1);
            request.onupgradeneeded = () => {
                const db = request.result;
                if (!db.objectStoreNames.contains(DRAFT_STORE_NAME)) {
                    db.createObjectStore(DRAFT_STORE_NAME);
                }
            };
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error || new Error('Failed to open draft DB'));
        });
    }

    async function saveDraft(draftPayload) {
        const db = await openDraftDb();
        await new Promise((resolve, reject) => {
            const tx = db.transaction(DRAFT_STORE_NAME, 'readwrite');
            tx.objectStore(DRAFT_STORE_NAME).put(draftPayload, DRAFT_KEY);
            tx.oncomplete = () => resolve();
            tx.onerror = () => reject(tx.error || new Error('Failed to save draft'));
        });
        db.close();
    }

    async function readDraft() {
        const db = await openDraftDb();
        const result = await new Promise((resolve, reject) => {
            const tx = db.transaction(DRAFT_STORE_NAME, 'readonly');
            const req = tx.objectStore(DRAFT_STORE_NAME).get(DRAFT_KEY);
            req.onsuccess = () => resolve(req.result || null);
            req.onerror = () => reject(req.error || new Error('Failed to read draft'));
        });
        db.close();
        return result;
    }

    async function deleteDraft() {
        const db = await openDraftDb();
        await new Promise((resolve, reject) => {
            const tx = db.transaction(DRAFT_STORE_NAME, 'readwrite');
            tx.objectStore(DRAFT_STORE_NAME).delete(DRAFT_KEY);
            tx.oncomplete = () => resolve();
            tx.onerror = () => reject(tx.error || new Error('Failed to clear draft'));
        });
        db.close();
    }

    function scheduleDraftPersist() {
        if (!pdfDoc || !pdfBytes) return;

        if (draftPersistTimer) {
            clearTimeout(draftPersistTimer);
        }

        draftPersistTimer = setTimeout(() => {
            persistDraft().catch((error) => console.warn('Draft autosave failed:', error));
        }, 500);
    }

    async function persistDraft() {
        if (!pdfDoc || !pdfBytes) return;

        await saveDraft({
            savedAt: Date.now(),
            pdfBytes,
            pageRotations: cloneJson(pageRotations),
            pageStates: cloneJson(pageStates),
            currentPage,
            textFont: textFont.value,
            textSize: textSize.value,
            textColor: textColor.value,
            eraserSize: eraserSize.value,
            textStyleState: cloneJson(textStyleState),
        });
    }

    function updateUndoState() {
        const index = pageHistoryIndex[currentPage] ?? -1;
        undoBtn.disabled = index <= 0;
    }

    function recordHistory() {
        if (!pdfDoc) return;

        const snapshot = cloneJson(pageStates[currentPage] || []);
        const history = pageHistory[currentPage] || [];
        let index = pageHistoryIndex[currentPage] ?? -1;

        if (index >= 0) {
            const current = JSON.stringify(history[index]);
            const next = JSON.stringify(snapshot);
            if (current === next) {
                updateUndoState();
                return;
            }
        }

        const trimmed = index < history.length - 1
            ? history.slice(0, index + 1)
            : history.slice();

        trimmed.push(snapshot);
        if (trimmed.length > 60) {
            trimmed.shift();
        }

        pageHistory[currentPage] = trimmed;
        pageHistoryIndex[currentPage] = trimmed.length - 1;
        updateUndoState();
    }

    function setMode(nextMode) {
        mode = nextMode;
        const drawMode = mode === 'highlight' || mode === 'erase';
        fabricCanvas.defaultCursor = drawMode ? 'crosshair' : 'default';
        fabricCanvas.isDrawingMode = mode === 'erase';
        fabricCanvas.selection = !drawMode;

        if (mode === 'erase') {
            fabricCanvas.freeDrawingBrush = new fabric.PencilBrush(fabricCanvas);
            fabricCanvas.freeDrawingBrush.color = '#ffffff';
            fabricCanvas.freeDrawingBrush.width = Math.max(6, parseInt(eraserSize.value || '24', 10));
        }

        fabricCanvas.forEachObject((obj) => {
            obj.selectable = !drawMode;
            obj.evented = !drawMode;
        });
        fabricCanvas.renderAll();
    }

    function updatePageIndicator() {
        pageIndicator.textContent = `Page ${pageCount ? currentPage : 0} / ${pageCount}`;
        prevPageBtn.disabled = currentPage <= 1;
        nextPageBtn.disabled = currentPage >= pageCount;
        jumpPageInput.disabled = pageCount === 0;
        jumpPageBtn.disabled = pageCount === 0;
        jumpPageInput.max = String(Math.max(1, pageCount));
        jumpPageInput.value = pageCount ? String(currentPage) : '';
    }

    async function jumpToEnteredPage() {
        if (!pdfDoc || pageCount === 0) return;

        const raw = parseInt(jumpPageInput.value || '', 10);
        if (Number.isNaN(raw)) {
            jumpPageInput.value = String(currentPage);
            return;
        }

        const target = Math.min(pageCount, Math.max(1, raw));
        if (target === currentPage) {
            jumpPageInput.value = String(currentPage);
            return;
        }

        await goToPage(target);
    }

    function colorHexToRgb(hex) {
        const safe = (hex || '#000000').replace('#', '');
        const bigint = parseInt(safe.length === 3
            ? safe.split('').map(ch => ch + ch).join('')
            : safe, 16);

        return {
            r: ((bigint >> 16) & 255) / 255,
            g: ((bigint >> 8) & 255) / 255,
            b: (bigint & 255) / 255,
        };
    }

    function mapPdfLibFontName(baseName, isBold, isItalic) {
        if (baseName === 'Times-Roman') {
            if (isBold && isItalic) return 'Times-BoldItalic';
            if (isBold) return 'Times-Bold';
            if (isItalic) return 'Times-Italic';
            return 'Times-Roman';
        }

        if (baseName === 'Courier') {
            if (isBold && isItalic) return 'Courier-BoldOblique';
            if (isBold) return 'Courier-Bold';
            if (isItalic) return 'Courier-Oblique';
            return 'Courier';
        }

        if (isBold && isItalic) return 'Helvetica-BoldOblique';
        if (isBold) return 'Helvetica-Bold';
        if (isItalic) return 'Helvetica-Oblique';
        return 'Helvetica';
    }

    function readFileAsArrayBuffer(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => resolve(reader.result);
            reader.onerror = reject;
            reader.readAsArrayBuffer(file);
        });
    }

    function readFileAsDataURL(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => resolve(reader.result);
            reader.onerror = reject;
            reader.readAsDataURL(file);
        });
    }

    function getCurrentTextStyle() {
        return {
            text: 'Type here',
            fontFamily: textFont.value || 'Helvetica',
            fontSize: Math.max(8, parseInt(textSize.value || '24', 10)),
            fill: textColor.value || '#111827',
            fontWeight: textStyleState.bold ? 'bold' : 'normal',
            fontStyle: textStyleState.italic ? 'italic' : 'normal',
            underline: !!textStyleState.underline,
        };
    }

    function toggleTextStyle(styleKey) {
        if (!['bold', 'italic', 'underline'].includes(styleKey)) return;

        textStyleState[styleKey] = !textStyleState[styleKey];

        const active = fabricCanvas.getActiveObject();
        if (active && active.type === 'i-text') {
            if (styleKey === 'bold') {
                active.set('fontWeight', textStyleState.bold ? 'bold' : 'normal');
            }
            if (styleKey === 'italic') {
                active.set('fontStyle', textStyleState.italic ? 'italic' : 'normal');
            }
            if (styleKey === 'underline') {
                active.set('underline', !!textStyleState.underline);
            }
            fabricCanvas.renderAll();
            serializeCurrentPageObjects({ record: true });
        }

        scheduleDraftPersist();
    }

    function alignTextObject(textObj, alignment) {
        if (!textObj || textObj.type !== 'i-text') return;

        const canvasWidth = fabricCanvas.getWidth();
        const textWidth = textObj.getScaledWidth();
        const margin = 24;

        if (alignment === 'left') {
            textObj.set({ left: margin });
        }

        if (alignment === 'center') {
            textObj.set({ left: (canvasWidth - textWidth) / 2 });
        }

        if (alignment === 'right') {
            textObj.set({ left: Math.max(margin, canvasWidth - textWidth - margin) });
        }

        textObj.setCoords();
    }

    function applyTextAlignment(alignment) {
        const active = fabricCanvas.getActiveObject();
        if (!active) return;

        if (active.type === 'i-text') {
            alignTextObject(active, alignment);
        }

        if (active.type === 'activeSelection') {
            active.forEachObject((obj) => {
                if (obj.type === 'i-text') {
                    alignTextObject(obj, alignment);
                }
            });
            active.setCoords();
        }

        fabricCanvas.renderAll();
        serializeCurrentPageObjects({ record: true });
    }

    function handleDeleteSelected() {
        const active = fabricCanvas.getActiveObject();
        if (!active) return;

        if (active.type === 'activeSelection') {
            active.forEachObject((obj) => fabricCanvas.remove(obj));
        } else {
            fabricCanvas.remove(active);
        }

        fabricCanvas.discardActiveObject();
        fabricCanvas.renderAll();
        serializeCurrentPageObjects({ record: true });
    }

    function serializeCurrentPageObjects(options = {}) {
        const { record = true } = options;
        const objects = fabricCanvas.getObjects().map((obj) => {
            const base = obj.toObject([
                'annotationType',
                'sourceData',
                'fontFamilyPdf',
                'isSignature',
            ]);

            if (obj.type === 'i-text') {
                base.annotationType = 'text';
            }

            return base;
        });

        pageStates[currentPage] = objects;
        if (record) {
            recordHistory();
        }
        scheduleDraftPersist();
    }

    async function initializePdfSession(bytes, options = {}) {
        const {
            restoredPageRotations = null,
            restoredPageStates = null,
            restoredCurrentPage = 1,
        } = options;

        pdfBytes = bytes;
        const loadingTask = pdfjsLib.getDocument({ data: pdfBytes });
        pdfDoc = await loadingTask.promise;
        pageCount = pdfDoc.numPages;
        currentPage = 1;
        pageRotations = restoredPageRotations && typeof restoredPageRotations === 'object'
            ? restoredPageRotations
            : {};
        pageStates = restoredPageStates && typeof restoredPageStates === 'object'
            ? restoredPageStates
            : {};
        pageViewports = {};
        pageHistory = {};
        pageHistoryIndex = {};

        for (let p = 1; p <= pageCount; p++) {
            const page = await pdfDoc.getPage(p);
            const viewport = page.getViewport({
                scale: PREVIEW_BASE_SCALE,
                rotation: pageRotations[p] || 0,
            });
            pageViewports[p] = viewport;
        }

        if (Number.isInteger(restoredCurrentPage) && restoredCurrentPage >= 1 && restoredCurrentPage <= pageCount) {
            currentPage = restoredCurrentPage;
        }

        editorEmpty.classList.add('hidden');
        editorCanvasWrap.classList.remove('hidden');
        downloadPdfBtn.disabled = false;
        rotatePageBtn.disabled = false;
        fitPageBtn.disabled = false;
        printBtn.disabled = false;

        await restoreCurrentPageObjects();
        serializeCurrentPageObjects({ record: true });
        updatePageIndicator();
        updateUndoState();
        scheduleDraftPersist();
    }

    function resetEditorSession(options = {}) {
        const { clearFileInput = false } = options;

        closeFitMode();

        pdfDoc = null;
        pdfBytes = null;
        currentPage = 1;
        pageCount = 0;
        pageViewports = {};
        pageRotations = {};
        pageStates = {};
        pageHistory = {};
        pageHistoryIndex = {};
        mode = 'select';
        drawingHighlight = false;
        highlightStart = null;
        activeHighlightRect = null;

        if (draftPersistTimer) {
            clearTimeout(draftPersistTimer);
            draftPersistTimer = null;
        }

        fabricCanvas.clear();
        fabricCanvas.setBackgroundImage(null, fabricCanvas.renderAll.bind(fabricCanvas));

        editorCanvasWrap.classList.add('hidden');
        editorEmpty.classList.remove('hidden');

        downloadPdfBtn.disabled = true;
        rotatePageBtn.disabled = true;
        fitPageBtn.disabled = true;
        printBtn.disabled = true;
        undoBtn.disabled = true;

        updatePageIndicator();

        if (clearFileInput) {
            pdfInput.value = '';
        }
    }

    async function restoreCurrentPageObjects() {
        const objects = pageStates[currentPage] || [];
        fabricCanvas.__restoring = true;
        fabricCanvas.clear();

        const viewport = pageViewports[currentPage];
        if (!viewport) return;

        const page = await pdfDoc.getPage(currentPage);
        const renderCanvas = document.createElement('canvas');
        renderCanvas.width = Math.floor(viewport.width * PREVIEW_OUTPUT_SCALE);
        renderCanvas.height = Math.floor(viewport.height * PREVIEW_OUTPUT_SCALE);
        const ctx = renderCanvas.getContext('2d');
        await page.render({
            canvasContext: ctx,
            viewport,
            transform: PREVIEW_OUTPUT_SCALE !== 1
                ? [PREVIEW_OUTPUT_SCALE, 0, 0, PREVIEW_OUTPUT_SCALE, 0, 0]
                : null,
        }).promise;

        const bgData = renderCanvas.toDataURL('image/png');

        await new Promise((resolve) => {
            fabric.Image.fromURL(bgData, (img) => {
                fabricCanvas.setWidth(viewport.width);
                fabricCanvas.setHeight(viewport.height);
                fabricCanvas.setBackgroundImage(img, fabricCanvas.renderAll.bind(fabricCanvas), {
                    originX: 'left',
                    originY: 'top',
                    left: 0,
                    top: 0,
                    scaleX: 1 / PREVIEW_OUTPUT_SCALE,
                    scaleY: 1 / PREVIEW_OUTPUT_SCALE,
                    selectable: false,
                    evented: false,
                });
                resolve();
            });
        });

        if (objects.length > 0) {
            await new Promise((resolve) => {
                fabric.util.enlivenObjects(objects, (enlivened) => {
                    enlivened.forEach((obj) => {
                        obj.selectable = true;
                        obj.evented = true;
                        fabricCanvas.add(obj);
                    });
                    resolve();
                });
            });
        }

        fabricCanvas.__restoring = false;
        setMode(mode);
        fabricCanvas.renderAll();
    }

    async function loadPdf(file) {
        const bytes = await readFileAsArrayBuffer(file);
        await initializePdfSession(bytes, {
            restoredPageRotations: {},
            restoredPageStates: {},
            restoredCurrentPage: 1,
        });
    }

    function rotatePageObjectsClockwise(pageNo, oldViewport) {
        const objects = pageStates[pageNo] || [];
        if (!oldViewport || objects.length === 0) return;

        const oldWidth = oldViewport.width;
        const oldHeight = oldViewport.height;

        pageStates[pageNo] = objects.map((obj) => {
            const scaleX = obj.scaleX || 1;
            const scaleY = obj.scaleY || 1;
            const objWidth = (obj.width || 0) * scaleX;
            const objHeight = (obj.height || 0) * scaleY;
            const cx = (obj.left || 0) + (objWidth / 2);
            const cy = (obj.top || 0) + (objHeight / 2);

            const newCx = oldHeight - cy;
            const newCy = cx;
            const newWidth = objHeight;
            const newHeight = objWidth;

            return {
                ...obj,
                left: Math.max(0, newCx - (newWidth / 2)),
                top: Math.max(0, newCy - (newHeight / 2)),
                angle: ((obj.angle || 0) + 90) % 360,
            };
        });
    }

    async function rotateCurrentPageClockwise() {
        if (!pdfDoc) return;

        serializeCurrentPageObjects({ record: true });

        const oldViewport = pageViewports[currentPage];
        const nextRotation = ((pageRotations[currentPage] || 0) + 90) % 360;
        pageRotations[currentPage] = nextRotation;

        rotatePageObjectsClockwise(currentPage, oldViewport);

        const page = await pdfDoc.getPage(currentPage);
        pageViewports[currentPage] = page.getViewport({
            scale: PREVIEW_BASE_SCALE,
            rotation: nextRotation,
        });

        await restoreCurrentPageObjects();
        serializeCurrentPageObjects({ record: true });
        scheduleDraftPersist();
    }

    function openFitMode() {
        if (!pdfDoc || fitModeActive) return;

        fitModeActive = true;
        fitOverlay.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        fitCanvasHost.appendChild(editorCanvasWrap);
        editorCanvasWrap.classList.remove('hidden');
    }

    function closeFitMode() {
        if (!fitModeActive) return;

        fitModeActive = false;
        fitOverlay.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');

        if (editorCanvasOriginalParent) {
            editorCanvasOriginalParent.appendChild(editorCanvasWrap);
        } else if (editorSection) {
            editorSection.appendChild(editorCanvasWrap);
        }
    }

    function addTextAnnotation() {
        const style = getCurrentTextStyle();
        const text = new fabric.IText(style.text, {
            left: fabricCanvas.getWidth() * 0.2,
            top: fabricCanvas.getHeight() * 0.2,
            fontSize: style.fontSize,
            fill: style.fill,
            fontFamily: 'Helvetica',
            fontWeight: style.fontWeight,
            fontStyle: style.fontStyle,
            underline: style.underline,
            editable: true,
            annotationType: 'text',
            fontFamilyPdf: style.fontFamily,
        });

        fabricCanvas.add(text);
        fabricCanvas.setActiveObject(text);
        fabricCanvas.renderAll();
        text.enterEditing();
        text.selectAll();
    }

    function removeWhiteBackgroundFromDataUrl(dataUrl) {
        return new Promise((resolve) => {
            const img = new Image();
            img.onload = () => {
                const canvas = document.createElement('canvas');
                canvas.width = img.width;
                canvas.height = img.height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0);

                const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                const data = imageData.data;

                for (let i = 0; i < data.length; i += 4) {
                    const r = data[i];
                    const g = data[i + 1];
                    const b = data[i + 2];

                    if (r > 235 && g > 235 && b > 235) {
                        data[i + 3] = 0;
                    }
                }

                ctx.putImageData(imageData, 0, 0);
                resolve(canvas.toDataURL('image/png'));
            };
            img.src = dataUrl;
        });
    }

    async function addImageObject(dataUrl, annotationType = 'image') {
        await new Promise((resolve) => {
            fabric.Image.fromURL(dataUrl, (img) => {
                const maxW = fabricCanvas.getWidth() * 0.5;
                const maxH = fabricCanvas.getHeight() * 0.5;
                const scale = Math.min(maxW / img.width, maxH / img.height, 1);

                img.set({
                    left: fabricCanvas.getWidth() * 0.25,
                    top: fabricCanvas.getHeight() * 0.25,
                    scaleX: scale,
                    scaleY: scale,
                    annotationType,
                    isSignature: annotationType === 'signature',
                    sourceData: dataUrl,
                });

                fabricCanvas.add(img);
                fabricCanvas.setActiveObject(img);
                fabricCanvas.renderAll();
                resolve();
            });
        });
    }

    function bindCanvasEvents() {
        fabricCanvas.on('object:modified', () => serializeCurrentPageObjects({ record: true }));
        fabricCanvas.on('object:added', () => {
            if (!fabricCanvas.__restoring) {
                const canvasObjects = fabricCanvas.getObjects();
                const latest = canvasObjects[canvasObjects.length - 1];
                if (latest && latest.type === 'path' && mode === 'erase' && !latest.annotationType) {
                    latest.annotationType = 'erase';
                    latest.selectable = false;
                    latest.evented = false;
                }
                serializeCurrentPageObjects({ record: true });
            }
        });
        fabricCanvas.on('object:removed', () => serializeCurrentPageObjects({ record: true }));

        fabricCanvas.on('mouse:down', (opt) => {
            if (mode !== 'highlight') return;
            const pointer = fabricCanvas.getPointer(opt.e);
            drawingHighlight = true;
            highlightStart = pointer;
            activeHighlightRect = new fabric.Rect({
                left: pointer.x,
                top: pointer.y,
                width: 1,
                height: 1,
                fill: 'rgba(255, 234, 0, 0.35)',
                stroke: 'rgba(250, 204, 21, 0.9)',
                strokeWidth: 1,
                selectable: false,
                evented: false,
                annotationType: 'highlight',
            });
            fabricCanvas.add(activeHighlightRect);
        });

        fabricCanvas.on('mouse:move', (opt) => {
            if (!drawingHighlight || !activeHighlightRect || mode !== 'highlight') return;
            const pointer = fabricCanvas.getPointer(opt.e);
            const width = pointer.x - highlightStart.x;
            const height = pointer.y - highlightStart.y;

            activeHighlightRect.set({
                left: width < 0 ? pointer.x : highlightStart.x,
                top: height < 0 ? pointer.y : highlightStart.y,
                width: Math.abs(width),
                height: Math.abs(height),
            });
            fabricCanvas.renderAll();
        });

        fabricCanvas.on('mouse:up', () => {
            if (!drawingHighlight) return;
            drawingHighlight = false;
            if (activeHighlightRect && (activeHighlightRect.width < 8 || activeHighlightRect.height < 8)) {
                fabricCanvas.remove(activeHighlightRect);
            } else if (activeHighlightRect) {
                activeHighlightRect.set({ selectable: true, evented: true });
            }
            activeHighlightRect = null;
            serializeCurrentPageObjects({ record: true });
        });
    }

    async function goToPage(nextPage) {
        if (!pdfDoc || nextPage < 1 || nextPage > pageCount) return;
        serializeCurrentPageObjects({ record: true });
        currentPage = nextPage;
        await restoreCurrentPageObjects();
        updatePageIndicator();
        updateUndoState();
        scheduleDraftPersist();
    }

    function extractExportObjects(objects) {
        return objects
            .filter((obj) => obj.annotationType)
            .map((obj) => {
                const common = {
                    annotationType: obj.annotationType,
                    left: obj.left || 0,
                    top: obj.top || 0,
                    width: obj.width || 0,
                    height: obj.height || 0,
                    scaleX: obj.scaleX || 1,
                    scaleY: obj.scaleY || 1,
                    angle: obj.angle || 0,
                };

                if (obj.annotationType === 'text') {
                    return {
                        ...common,
                        text: obj.text || '',
                        fontSize: obj.fontSize || 20,
                        fill: obj.fill || '#000000',
                        fontWeight: obj.fontWeight || 'normal',
                        fontStyle: obj.fontStyle || 'normal',
                        underline: !!obj.underline,
                        fontFamilyPdf: obj.fontFamilyPdf || 'Helvetica',
                    };
                }

                if (obj.annotationType === 'highlight') {
                    return {
                        ...common,
                        fill: obj.fill || 'rgba(255, 234, 0, 0.35)',
                    };
                }

                if (obj.annotationType === 'erase') {
                    return {
                        ...common,
                        path: obj.path || [],
                        strokeWidth: obj.strokeWidth || 24,
                    };
                }

                return {
                    ...common,
                    sourceData: obj.sourceData || null,
                    isSignature: !!obj.isSignature,
                };
            });
    }

    async function buildEditedPdfBytes() {
        if (!pdfBytes || !pdfDoc) return;

        serializeCurrentPageObjects();

        const { PDFDocument, StandardFonts, rgb, degrees } = PDFLib;
        const editedPdf = await PDFDocument.load(pdfBytes);

        for (let pageNo = 1; pageNo <= pageCount; pageNo++) {
            const viewport = pageViewports[pageNo];
            const page = editedPdf.getPage(pageNo - 1);
            const pageRotation = pageRotations[pageNo] || 0;
            if (pageRotation) {
                page.setRotation(degrees(pageRotation));
            }
            const pageHeight = page.getHeight();
            const pageWidth = page.getWidth();
            const scaleX = pageWidth / viewport.width;
            const scaleY = pageHeight / viewport.height;

            const objects = extractExportObjects(pageStates[pageNo] || []);

            for (const obj of objects) {
                const x = obj.left * scaleX;
                const y = pageHeight - ((obj.top + (obj.height * obj.scaleY)) * scaleY);
                const width = (obj.width * obj.scaleX) * scaleX;
                const height = (obj.height * obj.scaleY) * scaleY;

                if (obj.annotationType === 'text') {
                    const fontName = mapPdfLibFontName(
                        obj.fontFamilyPdf,
                        obj.fontWeight === 'bold',
                        obj.fontStyle === 'italic'
                    );

                    const pdfFont = await editedPdf.embedFont(StandardFonts[fontName]);
                    const fillColor = colorHexToRgb(obj.fill);
                    const size = Math.max(6, obj.fontSize * ((scaleX + scaleY) / 2));

                    page.drawText(obj.text || '', {
                        x,
                        y,
                        size,
                        font: pdfFont,
                        color: rgb(fillColor.r, fillColor.g, fillColor.b),
                    });

                    if (obj.underline) {
                        const textWidth = pdfFont.widthOfTextAtSize(obj.text || '', size);
                        page.drawLine({
                            start: { x, y: y - 2 },
                            end: { x: x + textWidth, y: y - 2 },
                            thickness: Math.max(0.6, size * 0.05),
                            color: rgb(fillColor.r, fillColor.g, fillColor.b),
                        });
                    }
                }

                if (obj.annotationType === 'highlight') {
                    page.drawRectangle({
                        x,
                        y,
                        width,
                        height,
                        color: rgb(1, 0.92, 0.2),
                        opacity: 0.35,
                        borderWidth: 0,
                    });
                }

                if (obj.annotationType === 'erase' && Array.isArray(obj.path) && obj.path.length > 0) {
                    const thickness = Math.max(4, obj.strokeWidth * ((scaleX + scaleY) / 2));
                    let prevPoint = null;

                    for (const cmd of obj.path) {
                        if (!Array.isArray(cmd) || cmd.length < 3) continue;

                        let point = null;
                        const type = cmd[0];

                        if (type === 'M' || type === 'L') {
                            point = {
                                x: (obj.left + (cmd[1] * (obj.scaleX || 1))) * scaleX,
                                y: pageHeight - ((obj.top + (cmd[2] * (obj.scaleY || 1))) * scaleY),
                            };
                        } else if (type === 'Q' && cmd.length >= 5) {
                            point = {
                                x: (obj.left + (cmd[3] * (obj.scaleX || 1))) * scaleX,
                                y: pageHeight - ((obj.top + (cmd[4] * (obj.scaleY || 1))) * scaleY),
                            };
                        } else if (type === 'C' && cmd.length >= 7) {
                            point = {
                                x: (obj.left + (cmd[5] * (obj.scaleX || 1))) * scaleX,
                                y: pageHeight - ((obj.top + (cmd[6] * (obj.scaleY || 1))) * scaleY),
                            };
                        }

                        if (!point) continue;

                        if (prevPoint) {
                            page.drawLine({
                                start: prevPoint,
                                end: point,
                                thickness,
                                color: rgb(1, 1, 1),
                            });
                        }

                        prevPoint = point;
                    }
                }

                if ((obj.annotationType === 'image' || obj.annotationType === 'signature') && obj.sourceData) {
                    const isPng = obj.sourceData.startsWith('data:image/png');
                    const imgBytes = await fetch(obj.sourceData).then((r) => r.arrayBuffer());
                    const embeddedImage = isPng
                        ? await editedPdf.embedPng(imgBytes)
                        : await editedPdf.embedJpg(imgBytes);

                    page.drawImage(embeddedImage, {
                        x,
                        y,
                        width,
                        height,
                        opacity: 1,
                    });
                }
            }
        }

        const editedBytes = await editedPdf.save();
        return editedBytes;
    }

    async function exportEditedPdf() {
        const editedBytes = await buildEditedPdfBytes();
        if (!editedBytes) return;

        const blob = new Blob([editedBytes], { type: 'application/pdf' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = 'edited-document.pdf';
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(url);
    }

    pdfInput.addEventListener('change', async (event) => {
        const file = event.target.files?.[0];
        if (!file) return;

        if (file.type !== 'application/pdf') {
            alert('Please upload a valid PDF file.');
            return;
        }

        await loadPdf(file);
        setMode('select');
    });

    textFont.addEventListener('change', scheduleDraftPersist);
    textSize.addEventListener('input', scheduleDraftPersist);
    textColor.addEventListener('change', scheduleDraftPersist);

    prevPageBtn.addEventListener('click', () => goToPage(currentPage - 1));
    nextPageBtn.addEventListener('click', () => goToPage(currentPage + 1));
    jumpPageBtn.addEventListener('click', () => jumpToEnteredPage());
    jumpPageInput.addEventListener('keydown', async (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            await jumpToEnteredPage();
        }
    });

    actionSelect.addEventListener('change', () => {
        if (!pdfDoc) {
            actionSelect.selectedIndex = 0;
            return;
        }

        if (actionSelect.value === 'select') {
            setMode('select');
        }

        if (actionSelect.value === 'highlight') {
            setMode('highlight');
        }

        if (actionSelect.value === 'delete') {
            handleDeleteSelected();
        }

        actionSelect.selectedIndex = 0;
    });

    addTextBtn.addEventListener('click', () => {
        if (!pdfDoc) return;
        setMode('select');
        addTextAnnotation();
    });

    styleSelect.addEventListener('change', () => {
        if (styleSelect.value) {
            if (styleSelect.value === 'align-left') {
                applyTextAlignment('left');
            } else if (styleSelect.value === 'align-center') {
                applyTextAlignment('center');
            } else if (styleSelect.value === 'align-right') {
                applyTextAlignment('right');
            } else {
                toggleTextStyle(styleSelect.value);
            }
        }
        styleSelect.selectedIndex = 0;
    });

    insertSelect.addEventListener('change', () => {
        if (!pdfDoc) {
            insertSelect.selectedIndex = 0;
            return;
        }

        if (insertSelect.value === 'image') {
            imageInput.click();
        }

        if (insertSelect.value === 'signature') {
            signatureInput.click();
        }

        insertSelect.selectedIndex = 0;
    });

    modeEraseBtn.addEventListener('click', () => {
        if (!pdfDoc) return;
        setMode('erase');
    });

    rotatePageBtn.addEventListener('click', async () => {
        if (!pdfDoc) return;
        await rotateCurrentPageClockwise();
    });

    fitPageBtn.addEventListener('click', () => {
        if (!pdfDoc) return;
        openFitMode();
    });

    closeFitBtn.addEventListener('click', () => {
        closeFitMode();
    });

    fitOverlay.addEventListener('click', (event) => {
        if (event.target === fitOverlay) {
            closeFitMode();
        }
    });

    window.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && fitModeActive) {
            closeFitMode();
        }
    });

    imageInput.addEventListener('change', async (event) => {
        const file = event.target.files?.[0];
        event.target.value = '';
        if (!file || !pdfDoc) return;

        const dataUrl = await readFileAsDataURL(file);
        await addImageObject(dataUrl, 'image');
        serializeCurrentPageObjects({ record: true });
    });

    eraserSize.addEventListener('change', () => {
        if (mode === 'erase') {
            setMode('erase');
        }
    });

    undoBtn.addEventListener('click', async () => {
        if (!pdfDoc) return;
        const history = pageHistory[currentPage] || [];
        let index = pageHistoryIndex[currentPage] ?? -1;
        if (history.length < 2 || index <= 0) return;

        index -= 1;
        pageHistoryIndex[currentPage] = index;
        pageStates[currentPage] = cloneJson(history[index] || []);
        await restoreCurrentPageObjects();
        updateUndoState();
        scheduleDraftPersist();
    });

    signatureInput.addEventListener('change', async (event) => {
        const file = event.target.files?.[0];
        event.target.value = '';
        if (!file || !pdfDoc) return;

        const dataUrl = await readFileAsDataURL(file);
        const transparentData = await removeWhiteBackgroundFromDataUrl(dataUrl);
        await addImageObject(transparentData, 'signature');
        serializeCurrentPageObjects();
    });

    downloadPdfBtn.addEventListener('click', async () => {
        try {
            downloadPdfBtn.disabled = true;
            downloadPdfBtn.textContent = 'Preparing...';
            await exportEditedPdf();
        } catch (error) {
            console.error(error);
            alert('Unable to export edited PDF. Please try again.');
        } finally {
            downloadPdfBtn.disabled = false;
            downloadPdfBtn.textContent = 'Download';
        }
    });

    printBtn.addEventListener('click', async () => {
        if (!pdfDoc) return;

        try {
            printBtn.disabled = true;
            printBtn.textContent = 'Printing...';

            const editedBytes = await buildEditedPdfBytes();
            if (!editedBytes) return;

            const blob = new Blob([editedBytes], { type: 'application/pdf' });
            const url = URL.createObjectURL(blob);
            const iframe = document.createElement('iframe');
            iframe.style.position = 'fixed';
            iframe.style.right = '0';
            iframe.style.bottom = '0';
            iframe.style.width = '0';
            iframe.style.height = '0';
            iframe.style.border = '0';

            iframe.onload = () => {
                let cleaned = false;
                let fallbackCleanupTimer = null;

                const cleanup = () => {
                    if (cleaned) return;
                    cleaned = true;
                    if (fallbackCleanupTimer) {
                        clearTimeout(fallbackCleanupTimer);
                    }
                    URL.revokeObjectURL(url);
                    iframe.remove();
                };

                if (iframe.contentWindow) {
                    iframe.contentWindow.addEventListener('afterprint', cleanup, { once: true });
                }

                // Fallback cleanup if afterprint does not fire in some browsers.
                fallbackCleanupTimer = setTimeout(cleanup, 5 * 60 * 1000);

                setTimeout(() => {
                    if (iframe.contentWindow) {
                        iframe.contentWindow.focus();
                        iframe.contentWindow.print();
                    }
                }, 250);
            };

            iframe.src = url;
            document.body.appendChild(iframe);
        } catch (error) {
            console.error(error);
            alert('Unable to print draft. Please try again.');
        } finally {
            printBtn.disabled = false;
            printBtn.textContent = 'Print';
        }
    });

    clearDraftBtn.addEventListener('click', async () => {
        const confirmed = window.confirm('Clear the current draft and remove autosaved local data?');
        if (!confirmed) return;

        try {
            await deleteDraft();
            resetEditorSession({ clearFileInput: true });
        } catch (error) {
            console.error(error);
            alert('Unable to clear draft right now. Please try again.');
        }
    });

    bindCanvasEvents();
    updatePageIndicator();
    updateUndoState();

    (async () => {
        try {
            const draft = await readDraft();
            if (!draft || !draft.pdfBytes) return;

            if (typeof draft.textFont === 'string') textFont.value = draft.textFont;
            if (typeof draft.textSize === 'string' || typeof draft.textSize === 'number') textSize.value = String(draft.textSize);
            if (typeof draft.textColor === 'string') textColor.value = draft.textColor;
            if (typeof draft.eraserSize === 'string' || typeof draft.eraserSize === 'number') eraserSize.value = String(draft.eraserSize);
            if (draft.textStyleState && typeof draft.textStyleState === 'object') {
                textStyleState = {
                    bold: !!draft.textStyleState.bold,
                    italic: !!draft.textStyleState.italic,
                    underline: !!draft.textStyleState.underline,
                };
            }

            await initializePdfSession(draft.pdfBytes, {
                restoredPageRotations: draft.pageRotations || {},
                restoredPageStates: draft.pageStates || {},
                restoredCurrentPage: draft.currentPage || 1,
            });
        } catch (error) {
            console.warn('Draft restore failed:', error);
        }
    })();
})();
</script>
@endsection
