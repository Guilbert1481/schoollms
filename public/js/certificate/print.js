function cleanupPreviewNode(node) {
	node.querySelectorAll('.selected').forEach(function (el) {
		el.classList.remove('selected');
	});

	node.querySelectorAll('.resize-handle, .delete-btn').forEach(function (el) {
		el.remove();
	});

	node.querySelectorAll('[contenteditable]').forEach(function (el) {
		el.removeAttribute('contenteditable');
	});
}

function extractBackgroundImageUrl(backgroundImageValue) {
	if (!backgroundImageValue || backgroundImageValue === 'none') return null;

	const match = backgroundImageValue.match(/^url\((['"]?)(.*)\1\)$/);
	if (!match || !match[2]) return null;

	return match[2];
}

function waitForImagesToLoad(doc, timeoutMs) {
	const images = Array.from(doc.images || []);
	if (!images.length) {
		return Promise.resolve();
	}

	const imagePromises = images.map(function (img) {
		if (img.complete) {
			return Promise.resolve();
		}

		return new Promise(function (resolve) {
			const done = function () {
				img.removeEventListener('load', done);
				img.removeEventListener('error', done);
				resolve();
			};

			img.addEventListener('load', done, { once: true });
			img.addEventListener('error', done, { once: true });
		});
	});

	const timeoutPromise = new Promise(function (resolve) {
		setTimeout(resolve, timeoutMs || 2500);
	});

	return Promise.race([
		Promise.all(imagePromises),
		timeoutPromise,
	]);
}

function getPageDimensionsPx(pageSize, pageOrientation) {
	const sizesInches = {
		A4: { width: 8.27, height: 11.69 },
		A5: { width: 5.83, height: 8.27 },
		Letter: { width: 8.5, height: 11 },
		Legal: { width: 8.5, height: 14 }
	};

	const size = sizesInches[pageSize] || sizesInches.A4;
	const widthInches = pageOrientation === 'portrait' ? size.width : size.height;
	const heightInches = pageOrientation === 'portrait' ? size.height : size.width;

	const pxPerInch = 96;

	return {
		width: Math.round(widthInches * pxPerInch),
		height: Math.round(heightInches * pxPerInch)
	};
}

window.printCertificatePreview = function (previewCanvas) {
	if (!previewCanvas) return;

	const orientation =
		previewCanvas.dataset.orientation ||
		(typeof window.getCertificateOrientation === 'function'
			? window.getCertificateOrientation()
			: 'landscape');
	const paperSize =
		previewCanvas.dataset.paperSize ||
		(typeof window.getCertificatePaperSize === 'function'
			? window.getCertificatePaperSize()
			: 'a4');
	const pageOrientation = orientation === 'portrait' ? 'portrait' : 'landscape';
	const pageSize =
		paperSize === 'a5' ? 'A5' :
		paperSize === 'letter' ? 'Letter' :
		paperSize === 'legal' ? 'Legal' :
		'A4';
	const pageDimensions = getPageDimensionsPx(pageSize, pageOrientation);

	const printableCanvas = previewCanvas.cloneNode(true);
	cleanupPreviewNode(printableCanvas);

	const sourceWidth = previewCanvas.offsetWidth || parseFloat(previewCanvas.style.width) || 1;
	const sourceHeight = previewCanvas.offsetHeight || parseFloat(previewCanvas.style.height) || 1;

	const previewStyle = window.getComputedStyle(previewCanvas);
	const backgroundImageUrl = extractBackgroundImageUrl(previewStyle.backgroundImage);

	const iframe = document.createElement('iframe');
	iframe.style.position = 'fixed';
	iframe.style.right = '0';
	iframe.style.bottom = '0';
	iframe.style.width = '0';
	iframe.style.height = '0';
	iframe.style.border = '0';
	iframe.setAttribute('aria-hidden', 'true');

	document.body.appendChild(iframe);

	const doc = iframe.contentWindow.document;
	const canvasStyle = printableCanvas.getAttribute('style') || '';

	doc.open();
	doc.write(`<!doctype html>
<html>
<head>
	<meta charset="utf-8">
	<title>Certificate Print</title>
	<style>
		@import url('https://fonts.googleapis.com/css2?family=Allura&family=Dancing+Script:wght@400;700&family=Great+Vibes&display=swap');

		@page {
			size: ${pageSize} ${pageOrientation};
			margin: 0;
		}
		html, body {
			margin: 0;
			padding: 0;
			background: #ffffff;
		}
		body {
			display: flex;
			justify-content: center;
			align-items: flex-start;
			padding: 0;
			box-sizing: border-box;
		}
		#print-page {
			position: relative;
			overflow: hidden;
			background: #ffffff;
			width: ${pageDimensions.width}px;
			height: ${pageDimensions.height}px;
		}
		#print-canvas {
			position: relative;
			overflow: hidden;
			background: #ffffff;
			transform-origin: top left;
			${canvasStyle}
		}
	</style>
</head>
<body>
	<div id="print-page">
		<div id="print-canvas"></div>
	</div>
</body>
</html>`);
	doc.close();

	const target = doc.getElementById('print-canvas');
	target.innerHTML = printableCanvas.innerHTML;

	target.style.width = sourceWidth + 'px';
	target.style.height = sourceHeight + 'px';

	const scale = Math.min(
		pageDimensions.width / sourceWidth,
		pageDimensions.height / sourceHeight
	);
	target.style.transform = `scale(${scale})`;

	if (backgroundImageUrl) {
		const bgImg = doc.createElement('img');
		bgImg.src = backgroundImageUrl;
		bgImg.alt = '';
		bgImg.setAttribute('aria-hidden', 'true');
		bgImg.style.position = 'absolute';
		bgImg.style.inset = '0';
		bgImg.style.width = '100%';
		bgImg.style.height = '100%';
		bgImg.style.objectFit = 'cover';
		bgImg.style.objectPosition = 'center';
		bgImg.style.zIndex = '0';
		bgImg.style.pointerEvents = 'none';

		target.prepend(bgImg);

		target.querySelectorAll(':scope > *:not(img)').forEach(function (child) {
			if (!child.style.position) {
				child.style.position = 'relative';
			}
			if (!child.style.zIndex) {
				child.style.zIndex = '1';
			}
		});
	}

	const printWindow = iframe.contentWindow;

	const finalize = function () {
		setTimeout(function () {
			iframe.remove();
		}, 200);
	};

	printWindow.onafterprint = finalize;

	waitForImagesToLoad(doc, 3000).finally(function () {
		setTimeout(function () {
			printWindow.focus();
			printWindow.print();
		}, 80);
	});
};
