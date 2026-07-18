// Place this after your DOMContentLoaded logic or in a scripts block
document.addEventListener('DOMContentLoaded', function() {
    const pointsBtn = document.getElementById('pointsBtn');
    const pointsModal = document.getElementById('pointsModal');
    const closePointsModal = document.getElementById('closePointsModal');
    const pointsForm = document.getElementById('pointsForm');

    // Redraw the "Points/type" summary next to the Points button from the modal
    // inputs, so the teacher always sees the current values without opening the
    // modal. Exposed on window so the save handler (testBuilder.js) can refresh
    // it after a save, with no page reload.
    window.renderPointsSummary = function () {
        const summary = document.getElementById('pointsSummary');
        if (!summary) return;
        const map = [
            ['points-mcq', 'MCQ'], ['points-tf', 'TF'], ['points-mtf', 'MTF'],
            ['points-id', 'ID'], ['points-match', 'Match'], ['points-fib', 'FIB'],
            ['points-enum', 'Enum'], ['points-essay', 'Essay'],
        ];
        const parts = [];
        map.forEach(([id, label]) => {
            const v = parseInt(document.getElementById(id)?.value, 10);
            if (!isNaN(v) && v > 0) parts.push(`${label} ${v}`);
        });
        summary.innerHTML = parts.length
            ? `<strong>Points/type:</strong> ${parts.join(' · ')}`
            : '<span style="color:#94a3b8;">No custom points set — defaults to 1 pt each.</span>';
    };
    window.renderPointsSummary();

    pointsBtn.onclick = () => { pointsModal.style.display = 'flex'; };

    closePointsModal.onclick = () => { pointsModal.style.display = 'none'; };
    pointsModal.onclick = (e) => {
        if (e.target === pointsModal) pointsModal.style.display = 'none';
    };

    pointsForm.onsubmit = (e) => {
        e.preventDefault();
        // You can collect the values and do whatever you need here!
        const data = Object.fromEntries(new FormData(pointsForm).entries());
        console.log("Points per type:", data);
        // TODO: Save data to server or in your Vue/React/app state as needed.
        pointsModal.style.display = 'none';
    };
});