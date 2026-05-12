function getCanvasContentElement(wrapper) {
    if (!wrapper) return null;

    return wrapper.querySelector('.text-element, .shape, img') || wrapper.firstElementChild;
}

function serializeCanvasElement(wrapper) {
    const contentEl = getCanvasContentElement(wrapper);
    if (!contentEl) return null;

    const tagName = contentEl.tagName.toLowerCase();

    return {
        wrapperStyle: wrapper.getAttribute('style') || '',
        content: {
            tagName,
            className: contentEl.className || '',
            style: contentEl.getAttribute('style') || '',
            html: tagName === 'img' ? '' : contentEl.innerHTML || '',
            src: tagName === 'img' ? contentEl.getAttribute('src') || '' : ''
        }
    };
}

function collectCanvasElements() {
    let elements = [];

    document.querySelectorAll('#canvas > *').forEach(wrapper => {
        const serialized = serializeCanvasElement(wrapper);

        if (serialized) {
            elements.push(serialized);
        }
    });

    return elements;
}

let autoSaveRequest = null;

function getDraftStorageKey() {
    const templateId = document.getElementById('template_id')?.value || 'new';
    return 'certificate_draft_' + templateId;
}

function sendCanvasDraftToServer(elements) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const templateId = document.getElementById('template_id')?.value || null;
    const orientation = typeof window.getCertificateOrientation === 'function'
        ? window.getCertificateOrientation()
        : null;
    const paperSize = typeof window.getCertificatePaperSize === 'function'
        ? window.getCertificatePaperSize()
        : null;
    const backgroundImage = typeof window.getCertificateBackgroundImage === 'function'
        ? window.getCertificateBackgroundImage()
        : null;
    const logo = typeof window.getCertificateLogoImage === 'function'
        ? window.getCertificateLogoImage()
        : null;

    if (!templateId) {
        return Promise.reject(new Error('No certificate template ID was found for this builder page.'));
    }

    return fetch('/school/settings/master-data/certificates/save', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {})
        },
        body: JSON.stringify({
            template_id: templateId,
            layout_json: elements,
            orientation: orientation,
            paper_size: paperSize,
            background_image: backgroundImage,
            logo: logo
        })
    }).then(async function (response) {
        let data = null;

        try {
            data = await response.json();
        } catch (error) {
            data = null;
        }

        if (!response.ok) {
            const message = data && data.message
                ? data.message
                : 'Certificate draft save failed with status ' + response.status;
            throw new Error(message);
        }

        return data;
    });
}

function setSaveStatus(message, tone) {
    const statusEl = document.getElementById('save-status');
    if (!statusEl) return;

    statusEl.textContent = message || '';
    statusEl.className = 'text-sm ' + (
        tone === 'error'
            ? 'text-red-600'
            : tone === 'success'
                ? 'text-green-600'
                : 'text-gray-600'
    );
}

function setSaveButtonState(isSaving) {
    const saveButton = document.getElementById('save-layout-button');
    if (!saveButton) return;

    saveButton.disabled = isSaving;
    saveButton.textContent = isSaving ? 'Saving...' : 'Save';
    saveButton.classList.toggle('opacity-70', isSaving);
    saveButton.classList.toggle('cursor-not-allowed', isSaving);
}

function persistCanvasDraft(elements) {
    const payload = JSON.stringify(elements);
    const storageKey = getDraftStorageKey();

    try {
        localStorage.setItem(storageKey, payload);
    } catch (error) {
        try {
            sessionStorage.setItem(storageKey, payload);
        } catch (storageError) {
            console.warn('Unable to store certificate draft in browser storage.', storageError);
        }
    }

    window.lastCertificateDraft = elements;

    if (autoSaveRequest) {
        window.clearTimeout(autoSaveRequest);
    }

    autoSaveRequest = window.setTimeout(function () {
        sendCanvasDraftToServer(elements).catch(function (error) {
            console.warn('Unable to auto-save certificate draft.', error);
        });
    }, 300);
}

function autoSaveToLocal() {
    persistCanvasDraft(collectCanvasElements());
}

window.saveLayout = async function() {
    let elements = collectCanvasElements();

    persistCanvasDraft(elements);
    setSaveButtonState(true);
    setSaveStatus('Saving certificate layout...', 'info');

    try {
        const result = await sendCanvasDraftToServer(elements);

        if (result && typeof result.background_image !== 'undefined') {
            window.savedCertificateAssets = window.savedCertificateAssets || {};
            window.savedCertificateAssets.backgroundImage = result.background_image || null;
        }

        if (result && typeof result.logo !== 'undefined') {
            window.savedCertificateAssets = window.savedCertificateAssets || {};
            window.savedCertificateAssets.logo = result.logo || null;
        }

        if (typeof window.setCertificateSavedAssets === 'function') {
            window.setCertificateSavedAssets({
                backgroundImage: window.savedCertificateAssets?.backgroundImage || null,
                logo: window.savedCertificateAssets?.logo || null,
            });
        }

        setSaveStatus('Certificate layout saved.', 'success');

        if (typeof showInfo === 'function') {
            showInfo('Certificate layout saved successfully.');
        }

        return result;
    } catch (error) {
        setSaveStatus(error.message || 'Unable to save the certificate layout.', 'error');
        throw error;
    } finally {
        setSaveButtonState(false);
    }
};
