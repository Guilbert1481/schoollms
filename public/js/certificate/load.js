function parseStoredLayout(rawValue) {
    if (!rawValue) return null;

    if (Array.isArray(rawValue)) {
        return rawValue;
    }

    if (typeof rawValue === 'string') {
        try {
            const parsed = JSON.parse(rawValue);
            return Array.isArray(parsed) ? parsed : null;
        } catch (error) {
            console.warn('Unable to parse saved certificate layout.', error);
            return null;
        }
    }

    return null;
}

function getDraftStorageKey() {
    const templateId = document.getElementById('template_id')?.value || 'new';
    return 'certificate_draft_' + templateId;
}

function createCanvasContentElement(content) {
    if (!content || !content.tagName) return null;

    let element = document.createElement(content.tagName);

    if (content.className) {
        element.className = content.className;
    }

    if (content.style) {
        element.setAttribute('style', content.style);
    }

    if (content.tagName === 'img') {
        element.src = content.src || '';
    } else {
        element.innerHTML = content.html || '';
    }

    if (element.classList.contains('text-element') && typeof initializeTextElement === 'function') {
        initializeTextElement(element);
        element.contentEditable = false;
    }

    return element;
}

function createCanvasItemFromSavedData(item) {
    if (!item) return null;

    if (item.content) {
        const contentEl = createCanvasContentElement(item.content);
        if (!contentEl) return null;

        const wrapper = createWrapper(contentEl);
        if (item.wrapperStyle) {
            wrapper.setAttribute('style', item.wrapperStyle);
        }

        return wrapper;
    }

    if (item.html) {
        const temp = document.createElement('div');
        temp.innerHTML = item.html;

        const contentEl = temp.querySelector('.text-element, .shape, img') || temp.firstElementChild;
        if (!contentEl) return null;

        const wrapper = createWrapper(contentEl.cloneNode(true));
        if (item.style) {
            wrapper.setAttribute('style', item.style);
        }

        const textEl = wrapper.querySelector('.text-element');
        if (textEl && typeof initializeTextElement === 'function') {
            initializeTextElement(textEl);
            textEl.contentEditable = false;
        }

        return wrapper;
    }

    return null;
}

document.addEventListener('DOMContentLoaded', function () {

    const templateId = document.getElementById('template_id')?.value || null;
    const canvas = document.getElementById('canvas');
    if (!canvas) return;

    // Legacy cleanup: prevent old global draft key from repopulating unrelated templates.
    try {
        localStorage.removeItem('certificate_draft');
        sessionStorage.removeItem('certificate_draft');
    } catch (error) {
        // Ignore storage cleanup failures.
    }

    const storageKey = getDraftStorageKey();
    let saved = localStorage.getItem(storageKey) || sessionStorage.getItem(storageKey);
    const dbLayout = parseStoredLayout(window.savedLayout);

    // For existing templates, DB layout is the source of truth.
    // This avoids stale local drafts leaking content across templates.
    let layout;
    if (templateId) {
        layout = dbLayout;

        // If DB has no layout yet, keep canvas explicitly empty.
        if (!layout || !layout.length) {
            canvas.innerHTML = '';
            return;
        }
    } else {
        layout = parseStoredLayout(saved) || dbLayout;
    }

    if (!layout || !layout.length) return;

    canvas.innerHTML = '';

    layout.forEach(item => {
        const wrapper = createCanvasItemFromSavedData(item);
        if (!wrapper) return;

        canvas.appendChild(wrapper);
    });

});