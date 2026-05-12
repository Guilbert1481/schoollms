const CERTIFICATE_ORIENTATION_KEY = 'certificate_orientation';
const CERTIFICATE_PAPER_SIZE_KEY = 'certificate_paper_size';
const CERTIFICATE_PAPER_SIZES = {
	a4: { width: 297, height: 210, label: 'A4', css: 'A4' },
	a5: { width: 210, height: 148, label: 'A5', css: 'A5' },
	letter: { width: 279.4, height: 215.9, label: 'Letter', css: 'Letter' },
	legal: { width: 355.6, height: 215.9, label: 'Legal', css: 'Legal' }
};
const CERTIFICATE_CANVAS_LONG_EDGE = 900;

function normalizeOrientation(value) {
	return value === 'portrait' ? 'portrait' : 'landscape';
}

function normalizePaperSize(value) {
	return CERTIFICATE_PAPER_SIZES[value] ? value : 'a4';
}

function saveOrientationPreference(orientation) {
	try {
		localStorage.setItem(CERTIFICATE_ORIENTATION_KEY, orientation);
	} catch (error) {
		// Ignore storage errors, orientation still applies for this session.
	}
}

function savePaperSizePreference(paperSize) {
	try {
		localStorage.setItem(CERTIFICATE_PAPER_SIZE_KEY, paperSize);
	} catch (error) {
		// Ignore storage errors, paper size still applies for this session.
	}
}

function loadOrientationPreference() {
	if (window.savedCertificateSettings && window.savedCertificateSettings.orientation) {
		return normalizeOrientation(window.savedCertificateSettings.orientation);
	}

	try {
		const stored = localStorage.getItem(CERTIFICATE_ORIENTATION_KEY);
		return normalizeOrientation(stored);
	} catch (error) {
		return 'landscape';
	}
}

function loadPaperSizePreference() {
	if (window.savedCertificateSettings && window.savedCertificateSettings.paperSize) {
		return normalizePaperSize(window.savedCertificateSettings.paperSize);
	}

	try {
		const stored = localStorage.getItem(CERTIFICATE_PAPER_SIZE_KEY);
		return normalizePaperSize(stored);
	} catch (error) {
		return 'a4';
	}
}

function updateOrientationSelect(orientation) {
	const orientationSelect = document.getElementById('certificate-orientation');
	if (orientationSelect) {
		orientationSelect.value = orientation;
	}
}

function updatePaperSizeSelect(paperSize) {
	const paperSizeSelect = document.getElementById('certificate-paper-size');
	if (paperSizeSelect) {
		paperSizeSelect.value = paperSize;
	}
}

function getCanvasSizeForPage(paperSize, orientation) {
	const normalizedPaperSize = normalizePaperSize(paperSize);
	const normalizedOrientation = normalizeOrientation(orientation);
	const page = CERTIFICATE_PAPER_SIZES[normalizedPaperSize];

	const longEdge = Math.max(page.width, page.height);
	const shortEdge = Math.min(page.width, page.height);
	const scale = CERTIFICATE_CANVAS_LONG_EDGE / longEdge;

	if (normalizedOrientation === 'portrait') {
		return {
			width: Math.round(shortEdge * scale),
			height: Math.round(longEdge * scale)
		};
	}

	return {
		width: Math.round(longEdge * scale),
		height: Math.round(shortEdge * scale)
	};
}

function applyCanvasPageSettings(options) {
	const normalizedOrientation = normalizeOrientation(options.orientation);
	const normalizedPaperSize = normalizePaperSize(options.paperSize);
	const canvas = document.getElementById('canvas');
	if (!canvas) return;

	const size = getCanvasSizeForPage(normalizedPaperSize, normalizedOrientation);
	canvas.style.width = `${size.width}px`;
	canvas.style.height = `${size.height}px`;
	canvas.dataset.orientation = normalizedOrientation;
	canvas.dataset.paperSize = normalizedPaperSize;

	window.certificateOrientation = normalizedOrientation;
	window.certificatePaperSize = normalizedPaperSize;
	updateOrientationSelect(normalizedOrientation);
	updatePaperSizeSelect(normalizedPaperSize);

	if (options.saveOrientationPreference) {
		saveOrientationPreference(normalizedOrientation);
	}

	if (options.savePaperSizePreference) {
		savePaperSizePreference(normalizedPaperSize);
	}

	if (options.triggerAutoSave && typeof autoSaveToLocal === 'function') {
		autoSaveToLocal();
	}
}

window.getCertificateOrientation = function () {
	const orientationSelect = document.getElementById('certificate-orientation');
	if (orientationSelect && orientationSelect.value) {
		return normalizeOrientation(orientationSelect.value);
	}

	const canvas = document.getElementById('canvas');
	if (canvas && canvas.dataset.orientation) {
		return normalizeOrientation(canvas.dataset.orientation);
	}

	if (window.certificateOrientation) {
		return normalizeOrientation(window.certificateOrientation);
	}

	return loadOrientationPreference();
};

window.getCertificatePaperSize = function () {
	const paperSizeSelect = document.getElementById('certificate-paper-size');
	if (paperSizeSelect && paperSizeSelect.value) {
		return normalizePaperSize(paperSizeSelect.value);
	}

	const canvas = document.getElementById('canvas');
	if (canvas && canvas.dataset.paperSize) {
		return normalizePaperSize(canvas.dataset.paperSize);
	}

	if (window.certificatePaperSize) {
		return normalizePaperSize(window.certificatePaperSize);
	}

	return loadPaperSizePreference();
};

window.setCertificateOrientation = function (orientation) {
	applyCanvasPageSettings({
		orientation,
		paperSize: window.getCertificatePaperSize(),
		saveOrientationPreference: true,
		savePaperSizePreference: false,
		triggerAutoSave: true
	});
};

window.setCertificatePaperSize = function (paperSize) {
	applyCanvasPageSettings({
		orientation: window.getCertificateOrientation(),
		paperSize,
		saveOrientationPreference: false,
		savePaperSizePreference: true,
		triggerAutoSave: true
	});
};

function ensurePreviewModal() {
	let modal = document.getElementById('certificate-preview-modal');
	if (modal) return modal;

	modal = document.createElement('div');
	modal.id = 'certificate-preview-modal';
	modal.style.position = 'fixed';
	modal.style.inset = '0';
	modal.style.background = 'rgba(15, 23, 42, 0.55)';
	modal.style.display = 'none';
	modal.style.alignItems = 'center';
	modal.style.justifyContent = 'center';
	modal.style.zIndex = '9999';
	modal.style.padding = '20px';

	const panel = document.createElement('div');
	panel.style.background = '#ffffff';
	panel.style.borderRadius = '12px';
	panel.style.boxShadow = '0 20px 50px rgba(0, 0, 0, 0.28)';
	panel.style.width = 'min(980px, 96vw)';
	panel.style.maxHeight = '92vh';
	panel.style.overflow = 'auto';
	panel.style.padding = '16px';

	const header = document.createElement('div');
	header.style.display = 'flex';
	header.style.justifyContent = 'space-between';
	header.style.alignItems = 'center';
	header.style.marginBottom = '12px';

	const title = document.createElement('h3');
	title.textContent = 'Certificate Preview';
	title.style.margin = '0';
	title.style.fontSize = '18px';
	title.style.fontWeight = '600';

	const closeBtn = document.createElement('button');
	closeBtn.type = 'button';
	closeBtn.textContent = 'Close';
	closeBtn.style.background = '#e5e7eb';
	closeBtn.style.color = '#111827';
	closeBtn.style.border = '1px solid #d1d5db';
	closeBtn.style.borderRadius = '8px';
	closeBtn.style.padding = '6px 12px';
	closeBtn.style.cursor = 'pointer';

	const printBtn = document.createElement('button');
	printBtn.type = 'button';
	printBtn.textContent = 'Print';
	printBtn.style.background = '#f3f4f6';
	printBtn.style.color = '#111827';
	printBtn.style.border = '1px solid #d1d5db';
	printBtn.style.borderRadius = '8px';
	printBtn.style.padding = '6px 12px';
	printBtn.style.cursor = 'pointer';

	const body = document.createElement('div');
	body.id = 'certificate-preview-body';
	body.style.display = 'flex';
	body.style.justifyContent = 'center';
	body.style.padding = '10px';
	body.style.background = '#f3f4f6';
	body.style.borderRadius = '10px';

	const actions = document.createElement('div');
	actions.style.display = 'flex';
	actions.style.alignItems = 'center';
	actions.style.gap = '8px';
	actions.appendChild(closeBtn);
	actions.appendChild(printBtn);

	header.appendChild(title);
	header.appendChild(actions);
	panel.appendChild(header);
	panel.appendChild(body);
	modal.appendChild(panel);
	document.body.appendChild(modal);

	function closeModal() {
		modal.style.display = 'none';
		document.body.style.overflow = '';
	}

	closeBtn.addEventListener('click', closeModal);
	printBtn.addEventListener('click', function () {
		const previewCanvas = document.getElementById('canvas-preview');
		if (!previewCanvas) return;

		if (typeof window.printCertificatePreview === 'function') {
			window.printCertificatePreview(previewCanvas);
			return;
		}

		window.print();
	});

	modal.addEventListener('click', function (e) {
		if (e.target === modal) {
			closeModal();
		}
	});

	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape' && modal.style.display !== 'none') {
			closeModal();
		}
	});

	return modal;
}

function buildPreviewCanvas() {
	const sourceCanvas = document.getElementById('canvas');
	if (!sourceCanvas) return null;

	const previewCanvas = sourceCanvas.cloneNode(true);
	previewCanvas.id = 'canvas-preview';
	previewCanvas.dataset.orientation = window.getCertificateOrientation();
	previewCanvas.dataset.paperSize = window.getCertificatePaperSize();

	previewCanvas.querySelectorAll('.selected').forEach(function (el) {
		el.classList.remove('selected');
	});

	previewCanvas.querySelectorAll('.resize-handle, .delete-btn').forEach(function (el) {
		el.remove();
	});

	previewCanvas.querySelectorAll('[contenteditable]').forEach(function (el) {
		el.removeAttribute('contenteditable');
	});

	return previewCanvas;
}

function openCanvasPreviewModal() {
	const modal = ensurePreviewModal();
	const body = document.getElementById('certificate-preview-body');
	if (!body) return;

	const previewCanvas = buildPreviewCanvas();
	if (!previewCanvas) return;

	body.innerHTML = '';
	body.appendChild(previewCanvas);

	modal.style.display = 'flex';
	document.body.style.overflow = 'hidden';
}

window.previewLayout = function () {
	if (typeof saveLayout !== 'function') {
		openCanvasPreviewModal();
		return;
	}

	let savePromise;

	try {
		savePromise = Promise.resolve(saveLayout());
	} catch (error) {
		console.warn('Save before preview failed synchronously, opening modal anyway.', error);
		openCanvasPreviewModal();
		return;
	}

	savePromise
		.catch(function (error) {
			console.warn('Save before preview failed, opening modal anyway.', error);
		})
		.finally(function () {
			openCanvasPreviewModal();
		});
};

document.addEventListener('DOMContentLoaded', function () {
	const orientationSelect = document.getElementById('certificate-orientation');
	const paperSizeSelect = document.getElementById('certificate-paper-size');
	const initialOrientation = loadOrientationPreference();
	const initialPaperSize = loadPaperSizePreference();

	if (orientationSelect) {
		orientationSelect.addEventListener('change', function () {
			window.setCertificateOrientation(this.value);
		});
	}

	if (paperSizeSelect) {
		paperSizeSelect.addEventListener('change', function () {
			window.setCertificatePaperSize(this.value);
		});
	}

	applyCanvasPageSettings({
		orientation: initialOrientation,
		paperSize: initialPaperSize,
		saveOrientationPreference: false,
		savePaperSizePreference: false,
		triggerAutoSave: false
	});
});
