/**
 * previewTest.js
 * -----------------------------
 * Reserved for print-preview enhancements.
 * No DOM mutation by default.
 * Safe for production printing.
 */

document.addEventListener('DOMContentLoaded', () => {

    const previewBtn = document.getElementById('previewTestBtn');
    if (!previewBtn) return;

    previewBtn.addEventListener('click', (e) => {
        e.preventDefault();

        const testId = previewBtn.dataset.testId;
        if (!testId) {
            alert('Test ID not found.');
            return;
        }

        // Open preview in a new tab (print-safe)
        window.open(
            `/teacher/tests/${testId}/preview`,
            '_blank'
        );
    });

});
