/**
 * ============================================
 * Reusable Modal Handler
 * Handles Add Subject & Add Topic modals
 * ============================================
 */

document.addEventListener('DOMContentLoaded', function () {

    function setupModal(modalId, openBtnId, cancelBtnId) {
        const modal = document.getElementById(modalId);
        const openBtn = document.getElementById(openBtnId);
        const cancelBtn = document.getElementById(cancelBtnId);

        // If any element is missing, do nothing (prevents JS crash)
        if (!modal || !openBtn || !cancelBtn) return;

        // Open modal
        openBtn.addEventListener('click', () => {
            modal.classList.add('active');
        });

        // Close via cancel button
        cancelBtn.addEventListener('click', () => {
            modal.classList.remove('active');
        });

        // Close when clicking outside modal box
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.remove('active');
            }
        });
    }

    // Initialize modals
    setupModal('addSubjectModal', 'addSubjectBtn', 'cancelAddSubject');
    setupModal('addTopicModal', 'addTopicBtn', 'cancelAddTopic');

});
