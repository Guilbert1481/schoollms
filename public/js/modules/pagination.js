document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.pagination-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            if (!btn.classList.contains('disabled')) {
                btn.blur();
            }
        });
    });
});
