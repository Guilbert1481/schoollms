// Place this after your DOMContentLoaded logic or in a scripts block
document.addEventListener('DOMContentLoaded', function() {
    const pointsBtn = document.getElementById('pointsBtn');
    const pointsModal = document.getElementById('pointsModal');
    const closePointsModal = document.getElementById('closePointsModal');
    const pointsForm = document.getElementById('pointsForm');

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