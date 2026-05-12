// Course Architect — Dashboard
// Lightweight init hook; relies on Alpine + lucide already loaded by layout.
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide && typeof window.lucide.createIcons === 'function') {
        window.lucide.createIcons();
    }
});
