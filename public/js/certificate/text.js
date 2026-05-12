function getSelectedTextElement() {
    if (!window.selectedElement) return null;

    // ✅ ONLY get real text element
    let el = window.selectedElement.querySelector('.text-element');

    return el || null;
}

function getSelectedCanvasItem() {
    return window.selectedElement || null;
}

window.initializeTextElement = function(text) {
    if (!text || text.dataset.textInitialized === 'true') return;

    text.classList.add('text-element');
    text.style.cursor = 'text';

    text.addEventListener('click', function (e) {
        e.stopPropagation();

        text.contentEditable = true;
        text.focus();

        window.isEditingText = true;
    });

    text.addEventListener('blur', function () {
        text.contentEditable = false;
        window.isEditingText = false;

        autoSaveToLocal();
    });

    text.addEventListener('input', function () {
        autoSaveToLocal();
    });

    text.dataset.textInitialized = 'true';
};

/* =========================
   TYPOGRAPHY CONTROLS
========================= */

window.setFontFamily = function(font) {
    let el = getSelectedTextElement();
    if (!el || !font) return;

    const scriptFonts = ['Great Vibes', 'Dancing Script', 'Allura', 'Monotype Corsiva'];
    const fallback = scriptFonts.includes(font) ? 'cursive' : 'sans-serif';

    el.style.fontFamily = `"${font}", ${fallback}`;

    autoSaveToLocal();
};

window.setFontSize = function(size) {
    let el = getSelectedTextElement();
    if (!el) return;

    size = parseInt(size);

    if (isNaN(size) || size <= 0) return;

    el.style.fontSize = size + 'px';

    autoSaveToLocal();
};

window.setTextColor = function(color) {
    let el = getSelectedTextElement();
    if (!el) return;

    el.style.color = color;
    autoSaveToLocal();
};

window.setAlign = function(align) {
    let wrapper = getSelectedCanvasItem();
    let el = getSelectedTextElement();
    let canvas = document.getElementById('canvas');

    if (!wrapper || !canvas) return;

    const canvasWidth = canvas.clientWidth;
    const elementWidth = wrapper.offsetWidth;

    let left = wrapper.offsetLeft;

    if (align === 'left') {
        left = 0;
    } else if (align === 'center') {
        left = Math.max(0, (canvasWidth - elementWidth) / 2);
    } else if (align === 'right') {
        left = Math.max(0, canvasWidth - elementWidth);
    } else {
        return;
    }

    if (el) {
        el.style.textAlign = align;
    }

    wrapper.style.left = left + 'px';

    autoSaveToLocal();
};

window.toggleBold = function() {
    let el = getSelectedTextElement();
    if (!el) return;

    el.style.fontWeight =
        el.style.fontWeight === 'bold' ? 'normal' : 'bold';

    autoSaveToLocal();
};

window.toggleItalic = function() {
    let el = getSelectedTextElement();
    if (!el) return;

    el.style.fontStyle =
        el.style.fontStyle === 'italic' ? 'normal' : 'italic';

    autoSaveToLocal();
};

window.setLetterSpacing = function(value) {
    let el = getSelectedTextElement();
    if (!el) return;

    el.style.letterSpacing = value + 'px';
    autoSaveToLocal();
};

window.setLineHeight = function(value) {
    let el = getSelectedTextElement();
    if (!el) return;

    el.style.lineHeight = value;
    autoSaveToLocal();
};

window.toggleShadow = function() {
    let el = getSelectedTextElement();
    if (!el) return;

    el.style.textShadow =
        el.style.textShadow
            ? ''
            : '2px 2px 5px rgba(0,0,0,0.3)';

    autoSaveToLocal();
};

window.addText = function () {

    let canvas = document.getElementById('canvas');
    if (!canvas) return;

    let text = document.createElement('div');
    text.innerText = 'Edit Text';

    text.contentEditable = false;
    text.style.fontSize = '24px';
    text.style.minWidth = '50px';
    text.style.whiteSpace = 'pre-wrap';

    initializeTextElement(text);

    let wrapper = createWrapper(text);

    canvas.appendChild(wrapper);

    if (typeof selectCanvasItem === 'function') {
        selectCanvasItem(wrapper);
    } else {
        window.selectedElement = wrapper;
    }

    autoSaveToLocal();
};

window.addNameText = function () {

    let canvas = document.getElementById('canvas');
    if (!canvas) return;

    let text = document.createElement('div');
    text.innerText = '{{ recipient_name }}';

    const fontFamilyControl = document.getElementById('certificate-font-family');
    const fontSizeControl = document.getElementById('certificate-font-size');
    const selectedFont = fontFamilyControl ? String(fontFamilyControl.value || '').trim() : '';
    const selectedSize = fontSizeControl ? parseInt(fontSizeControl.value, 10) : 24;

    text.contentEditable = false;
    text.style.fontSize = (Number.isFinite(selectedSize) && selectedSize > 0 ? selectedSize : 24) + 'px';
    text.style.fontWeight = '700';
    text.style.minWidth = '180px';
    text.style.whiteSpace = 'pre-wrap';
    text.style.textAlign = 'center';
    text.style.lineHeight = '1.1';
    if (selectedFont) {
        const scriptFonts = ['Great Vibes', 'Dancing Script', 'Allura', 'Monotype Corsiva'];
        const fallback = scriptFonts.includes(selectedFont) ? 'cursive' : 'sans-serif';
        text.style.fontFamily = '"' + selectedFont + '", ' + fallback;
    } else {
        text.style.fontFamily = '"Times New Roman", serif';
    }
    text.style.color = '#1f2937';

    text.classList.add('recipient-name-slot');

    initializeTextElement(text);

    let wrapper = createWrapper(text);
    wrapper.style.left = '300px';
    wrapper.style.top = '280px';

    canvas.appendChild(wrapper);

    if (typeof selectCanvasItem === 'function') {
        selectCanvasItem(wrapper);
    } else {
        window.selectedElement = wrapper;
    }

    autoSaveToLocal();
};

window.toggleUnderline = function() {
    let el = getSelectedTextElement();
    if (!el) return;

    el.style.textDecoration =
        el.style.textDecoration === 'underline'
            ? 'none'
            : 'underline';

    autoSaveToLocal();
};