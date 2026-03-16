
document.addEventListener('click', function (e) {

    if (!e.target.closest('.delete-option')) return;

    const row = e.target.closest('.option-row');
    if (!row) return;

    row.remove();
});
