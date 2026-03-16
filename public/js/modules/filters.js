document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('.filter-form');
    if (!form) return;

    // Submit on Enter (optional convenience)
    form.querySelectorAll('input').forEach(input => {
        input.addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                form.submit();
            }
        });
    });
});
