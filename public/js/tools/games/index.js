(function () {
    const filter = document.getElementById('gamesFilter');
    const grid   = document.getElementById('gamesGrid');
    const count  = document.getElementById('gamesCount');
    if (!filter || !grid) return;

    const cards = Array.from(grid.querySelectorAll('.game-card'));
    const updateCount = (visible) => {
        if (count) count.textContent = `${visible} of ${cards.length} games`;
    };
    updateCount(cards.length);

    filter.addEventListener('input', (e) => {
        const q = (e.target.value || '').toLowerCase().trim();
        let visible = 0;
        cards.forEach(card => {
            const match = !q || (card.dataset.name || '').includes(q);
            card.classList.toggle('hidden', !match);
            if (match) visible++;
        });
        updateCount(visible);
    });

    if (window.lucide && typeof window.lucide.createIcons === 'function') {
        window.lucide.createIcons();
    }
})();
