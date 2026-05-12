/**
 * Student Subjects page
 * - Live search across the subject cards
 * - View toggle: cards <-> table
 */
(function () {
    const root = document.getElementById('student-subjects');
    if (!root) return;

    const search   = root.querySelector('[data-subjects-search]');
    const cards    = root.querySelectorAll('[data-subject-card]');
    const rows     = root.querySelectorAll('[data-subject-row]');
    const counter  = root.querySelector('[data-subjects-count]');
    const cardWrap = root.querySelector('[data-view="cards"]');
    const tableWrap= root.querySelector('[data-view="table"]');
    const toggles  = root.querySelectorAll('[data-view-toggle]');
    const empty    = root.querySelector('[data-subjects-empty]');

    const matches = (el, term) => {
        if (!term) return true;
        return (el.dataset.search || '').toLowerCase().includes(term);
    };

    const applyFilter = () => {
        const term = (search?.value || '').trim().toLowerCase();
        let visible = 0;

        cards.forEach(c => {
            const ok = matches(c, term);
            c.classList.toggle('hidden', !ok);
            if (ok) visible++;
        });
        rows.forEach(r => {
            r.classList.toggle('hidden', !matches(r, term));
        });

        if (counter) counter.textContent = visible;
        if (empty)   empty.classList.toggle('hidden', visible !== 0 || cards.length === 0);
    };

    const setView = (mode) => {
        cardWrap?.classList.toggle('hidden', mode !== 'cards');
        tableWrap?.classList.toggle('hidden', mode !== 'table');
        toggles.forEach(btn => {
            const active = btn.dataset.viewToggle === mode;
            btn.classList.toggle('bg-indigo-600', active);
            btn.classList.toggle('text-white', active);
            btn.classList.toggle('text-slate-700', !active);
            btn.classList.toggle('bg-white', !active);
        });
    };

    search?.addEventListener('input', applyFilter);
    toggles.forEach(btn => btn.addEventListener('click', () => setView(btn.dataset.viewToggle)));

    setView('cards');
    applyFilter();
})();
