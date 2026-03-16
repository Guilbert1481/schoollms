/* ============================================================
   SEARCH FORM — LARAVEL GET SUBMIT (FIXED)
============================================================ */
document.addEventListener('DOMContentLoaded', () => {
    const searchForm = document.getElementById('testFilterForm');
    if (searchForm) {
        searchForm.addEventListener('submit', function (e) {
            // ✅ Let Laravel handle GET submit
            // No preventDefault, no JS redirect
            console.log("Search form submitted"); // optional debug
        });
    }
});

function resetPanel() {
    const panel = document.querySelector('#testDetailsCard');
    if (!panel) return;

    panel.innerHTML = `
        <div class="empty-state">
            <div class="empty-icon">📘</div>
            <h4>Select a Test</h4>
            <p>Click a test on the left to view details and actions.</p>
        </div>
    `;

    sessionStorage.removeItem('selectedTestId');
}

/* ============================================================
   CONSTANTS
============================================================ */
const STORAGE_KEY = 'selected_tests';

/* ============================================================
   STORAGE HELPERS
============================================================ */
function getSelectedTests() {
    try {
        return JSON.parse(sessionStorage.getItem(STORAGE_KEY)) || {};
    } catch {
        return {};
    }
}

function saveSelectedTests(data) {
    sessionStorage.setItem(STORAGE_KEY, JSON.stringify(data));
}

/* ============================================================
   CHECKBOX HANDLING (UNCHANGED)
============================================================ */
function handleCheckboxChange(checkbox) {
    const testId = checkbox.dataset.testId;
    if (!testId) return;

    const data = getSelectedTests();

    if (checkbox.checked) {
        data[testId] = {
            id: testId,
            title: checkbox.dataset.title || '',
            subject: checkbox.dataset.subject || '',
            topic: checkbox.dataset.topic || '',
        };
    } else {
        delete data[testId];
    }

    saveSelectedTests(data);
    updateReviewCount();
}

function restoreCheckboxState() {}

function updateReviewCount() {
    const el = document.getElementById('selectedCount');
    if (!el) return;

    el.textContent = Object.keys(getSelectedTests()).length;
}

/* ============================================================
   GLOBAL SUBMIT HANDLER (UNCHANGED)
============================================================ */
document.addEventListener('DOMContentLoaded', () => {
    const quickAssignForm = document.getElementById('quickAssignForm');
    if (!quickAssignForm) return;

    quickAssignForm.addEventListener('submit', function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();
    });
});

/* ============================================================
   RIGHT PANEL — ROW CLICK (UNCHANGED)
============================================================ */
document.addEventListener('DOMContentLoaded', () => {
    const rows = document.querySelectorAll('.test-row');
    const panel = document.getElementById('testDetailsCard');

    if (!rows.length || !panel) return;

    rows.forEach(row => {
        row.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            rows.forEach(r => r.classList.remove('active'));
            row.classList.add('active');

            const testId = row.dataset.testId;
            sessionStorage.setItem('selectedTestId', testId);

            panel.innerHTML = `
                <h4>Test Details</h4>
                <p><strong>Subject:</strong> ${row.dataset.subject || '-'}</p>
                <p><strong>Topic:</strong> ${row.dataset.topic || '-'}</p>
                <p><strong>Lesson:</strong> ${row.dataset.title || '-'}</p>
                <p><strong>Learning Outcome:</strong> ${row.dataset.outcome || '-'}</p>
                <p><strong>Status:</strong> Draft</p>
            `;
        });
    });
});

/* ============================================================
   RESTORE SELECTED TEST AFTER REFRESH (UNCHANGED)
============================================================ */
document.addEventListener('DOMContentLoaded', function () {
    const panel = document.getElementById('testDetailsCard');
    const id = sessionStorage.getItem('selectedTestId');

    if (!panel || !id) return;

    const row = document.querySelector(`.test-row[data-test-id="${id}"]`);
    if (!row) return;

    row.classList.add('active');

    panel.innerHTML = `
        <h4>Test Details</h4>
        <p><strong>Subject:</strong> ${row.dataset.subject || '-'}</p>
        <p><strong>Topic:</strong> ${row.dataset.topic || '-'}</p>
        <p><strong>Lesson:</strong> ${row.dataset.title || '-'}</p>
        <p><strong>Learning Outcome:</strong> ${row.dataset.outcome || '-'}</p>
        <p><strong>Status:</strong> Draft</p>
    `;
});

/* ============================================================
   PUBLISH TEST (UNCHANGED)
============================================================ */
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-publish-test');
    if (!btn) return;

    const testId = btn.dataset.testId;
    if (!confirm('Publish this test?')) return;

    fetch(`/teacher/tests/${testId}/publish`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            Accept: 'application/json',
        },
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) location.reload();
        });
});

/* ============================================================
   ASSIGN TEST (PLACEHOLDER)
============================================================ */
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-assign-test');
    if (!btn) return;

    alert('Assign to Class – coming next');
});
