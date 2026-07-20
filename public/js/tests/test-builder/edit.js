// Wire the "Edit Test" button to the edit page.
//
// The button is a static element in the builder (hidden until a save reveals it).
// It reads the test id at CLICK time — the id lands on #testId after the test saves,
// and the button is only shown once that has happened. Previously this listened for a
// `testSaved` event that nothing ever dispatched, so the handler was never attached
// and the button did nothing.
document.addEventListener('DOMContentLoaded', () => {
    const editBtn = document.getElementById('editTestBtn');
    if (!editBtn) return;

    editBtn.addEventListener('click', () => {
        const testId = document.getElementById('testId')?.value;
        if (!testId) {
            alert('Save the test before editing.');
            return;
        }
        window.location.href = `/teacher/tests/${testId}/edit`;
    });
});
