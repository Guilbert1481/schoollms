let uploadLock = false;

document.body.addEventListener('click', function (e) {

    const btn = e.target.closest('.upload-option-image');
    if (!btn) return;

    e.preventDefault();
    e.stopPropagation();

    if (uploadLock) return;
    uploadLock = true;

    const row = btn.closest('.option-row');
    const input = row.querySelector('.option-image-input');

    input.value = '';
    input.click();

    setTimeout(() => uploadLock = false, 500);
});

document.addEventListener('change', function (e) {
    if (!e.target.classList.contains('option-image-input')) return;

    const row = e.target.closest('.option-row');
    const img = row.querySelector('.option-image-preview');

    img.src = URL.createObjectURL(e.target.files[0]);
    img.style.display = 'block';

    // keep image inside the option row
    row.style.alignItems = 'center';
});

