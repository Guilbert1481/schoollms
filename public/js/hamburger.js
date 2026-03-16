document.addEventListener('DOMContentLoaded', () => {
    const hamburger = document.querySelector('.mobile-hamburger');
    const sidebar = document.querySelector('.sidebar');
    const backdrop = document.querySelector('.sidebar-backdrop');

    if (!hamburger || !sidebar || !backdrop) return;

    hamburger.addEventListener('click', () => {
        sidebar.classList.toggle('sidebar-open');
        backdrop.classList.toggle('show');
    });

    backdrop.addEventListener('click', () => {
        sidebar.classList.remove('sidebar-open');
        backdrop.classList.remove('show');
    });
});
