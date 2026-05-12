let imageUploadType = 'logo';
let currentBackgroundImage = null;
let currentLogoImage = null;
let backgroundImageSelected = false;

function setBackgroundSelectionState(isSelected) {
    const canvas = document.getElementById('canvas');
    if (!canvas) return;

    backgroundImageSelected = !!isSelected;
    canvas.classList.toggle('background-selected', backgroundImageSelected);
}

window.isBackgroundImageSelected = function () {
    return backgroundImageSelected;
};

window.clearBackgroundSelection = function () {
    setBackgroundSelectionState(false);
};

window.selectBackgroundImage = function () {
    if (!currentBackgroundImage) return;

    if (typeof clearSelectedState === 'function') {
        clearSelectedState();
    }

    window.selectedElement = null;
    setBackgroundSelectionState(true);
};

window.clearBackgroundImage = function () {
    currentBackgroundImage = null;
    applyBackgroundImage(null);
    setBackgroundSelectionState(false);

    if (typeof autoSaveToLocal === 'function') {
        autoSaveToLocal();
    }
};

window.selectImageUploadType = function (type) {
    imageUploadType = type === 'background' ? 'background' : 'logo';

    const uploader = document.getElementById('imageUploader');
    if (!uploader) {
        console.error('imageUploader not found');
        return;
    }

    uploader.click();
};

window.handleImageUploadOption = function (selectEl) {
    if (!selectEl || !selectEl.value) {
        return;
    }

    window.selectImageUploadType(selectEl.value);
    selectEl.value = '';
};

window.getCertificateBackgroundImage = function () {
    return currentBackgroundImage || null;
};

window.getCertificateLogoImage = function () {
    return currentLogoImage || null;
};

window.setCertificateSavedAssets = function (assets) {
    if (!assets || typeof assets !== 'object') return;

    if (Object.prototype.hasOwnProperty.call(assets, 'backgroundImage')) {
        currentBackgroundImage = assets.backgroundImage || null;
        applyBackgroundImage(currentBackgroundImage);
    }

    if (Object.prototype.hasOwnProperty.call(assets, 'logo')) {
        currentLogoImage = assets.logo || null;
    }
};

function resolveImageUrl(value) {
    if (!value) return '';
    if (value.startsWith('data:') || value.startsWith('http://') || value.startsWith('https://') || value.startsWith('/')) {
        return value;
    }

    return '/' + value.replace(/^\/+/, '');
}

function applyBackgroundImage(src) {
    const canvas = document.getElementById('canvas');
    if (!canvas) return;

    if (!src) {
        canvas.style.backgroundImage = '';
        canvas.style.backgroundSize = '';
        canvas.style.backgroundPosition = '';
        canvas.style.backgroundRepeat = '';
        setBackgroundSelectionState(false);
        return;
    }

    canvas.style.backgroundImage = "url('" + resolveImageUrl(src).replace(/'/g, "\\'") + "')";
    canvas.style.backgroundSize = 'cover';
    canvas.style.backgroundPosition = 'center';
    canvas.style.backgroundRepeat = 'no-repeat';
}


// Handle upload
document.addEventListener('DOMContentLoaded', function () {

    const uploader = document.getElementById('imageUploader');
    const canvas = document.getElementById('canvas');

    if (!uploader || !canvas) {
        console.warn('Uploader or canvas not found');
        return;
    }

    if (window.savedCertificateAssets) {
        currentBackgroundImage = window.savedCertificateAssets.backgroundImage || null;
        currentLogoImage = window.savedCertificateAssets.logo || null;
        applyBackgroundImage(currentBackgroundImage);
    }

    canvas.addEventListener('click', function (e) {
        if (e.target !== canvas) {
            return;
        }

        if (!currentBackgroundImage) {
            setBackgroundSelectionState(false);
            return;
        }

        window.selectBackgroundImage();
    });

    uploader.addEventListener('change', function (e) {

        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();

        reader.onload = function (event) {

            try {

                if (imageUploadType === 'background') {
                    currentBackgroundImage = event.target.result;
                    applyBackgroundImage(currentBackgroundImage);
                    setBackgroundSelectionState(true);

                    if (typeof autoSaveToLocal === 'function') {
                        autoSaveToLocal();
                    }

                    return;
                }

                let img = document.createElement('img');
                img.src = event.target.result;
                img.style.width = '120px';
                img.style.display = 'block';

                currentLogoImage = event.target.result;

                // safety check
                if (typeof createWrapper !== 'function') {
                    console.error('createWrapper is not defined (check core.js load order)');
                    return;
                }

                let wrapper = createWrapper(img);

                canvas.appendChild(wrapper);

                if (typeof selectCanvasItem === 'function') {
                    selectCanvasItem(wrapper);
                } else {
                    window.selectedElement = wrapper;
                }

                setBackgroundSelectionState(false);

                if (typeof autoSaveToLocal === 'function') {
                    autoSaveToLocal();
                }

            } catch (err) {
                console.error('Error adding logo:', err);
            }
        };

        reader.readAsDataURL(file);

        // allow same file upload again
        e.target.value = '';
    });

});