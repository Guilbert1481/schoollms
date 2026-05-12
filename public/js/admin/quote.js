// Quote page helpers. Open/close/drag is handled by public/js/modal/modal.js.

// ==========================
// EDIT QUOTE MODAL
// Uniquely named to avoid clashing with global openEditModal(modalId, id, table)
// ==========================
window.openEditQuote = function (id, theme, author, content) {
    const modal = document.getElementById('edit-quote-modal');
    if (!modal) {
        console.error('Edit modal not found');
        return;
    }

    const form = modal.querySelector('form');
    if (form) {
        form.action = '/admin/quotes/' + id;
    }

    modal.querySelector('[name="id"]').value = id;
    modal.querySelector('[name="theme"]').value = theme;
    modal.querySelector('[name="author"]').value = author;
    modal.querySelector('[name="content"]').value = content;

    if (typeof window.openModal === 'function') {
        window.openModal('edit-quote-modal');
    } else {
        modal.classList.remove('hidden');
    }
};


// ==========================
// ADD ANOTHER QUOTE BLOCK + SEARCH
// ==========================
document.addEventListener('DOMContentLoaded', function () {

    const addBtn  = document.getElementById('add-quote-btn');
    const wrapper = document.getElementById('quotes-wrapper');

    if (addBtn && wrapper) {
        let quoteIndex = wrapper.querySelectorAll('.quote-block').length;

        addBtn.addEventListener('click', function () {
            const first = wrapper.querySelector('.quote-block');
            if (!first) return;

            const clone = first.cloneNode(true);

            clone.querySelectorAll('input, textarea, select').forEach(field => {
                const name = field.name;
                if (name) {
                    field.name = name.replace(/\[\d+\]/, '[' + quoteIndex + ']');
                }
                if (field.tagName === 'SELECT') {
                    field.selectedIndex = 0;
                } else {
                    field.value = '';
                }
            });

            wrapper.appendChild(clone);
            quoteIndex++;
        });
    }

    // AJAX search
    const searchInput = document.getElementById('quote-search');
    if (searchInput) {
        let timer;
        searchInput.addEventListener('keyup', function () {
            clearTimeout(timer);
            timer = setTimeout(() => {
                fetch('/admin/quotes?search=' + encodeURIComponent(this.value), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => res.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newTbody = doc.querySelector('tbody');
                    const currentTbody = document.querySelector('tbody');
                    if (newTbody && currentTbody) {
                        currentTbody.innerHTML = newTbody.innerHTML;
                        if (typeof lucide !== 'undefined') lucide.createIcons();
                    }
                });
            }, 300);
        });
    }
});


// ==========================
// CLICK OUTSIDE CLOSES QUOTE MODALS
// ==========================
window.addEventListener('click', function (e) {
    ['create-quote-modal', 'update-display-modal', 'edit-quote-modal'].forEach(id => {
        const modal = document.getElementById(id);
        if (modal && e.target === modal) {
            if (typeof window.closeModal === 'function') {
                window.closeModal(id);
            } else {
                modal.classList.add('hidden');
            }
        }
    });
});
// ==========================
// MODAL CONTROL (namespaced to avoid collision with global modal.js)
// ==========================
window.openQuoteModal = function(id){
    const modal = document.getElementById(id);
    if (!modal) {
        console.error("Modal not found:", id);
        return;
    }
    modal.style.display = 'flex';
};

window.closeQuoteModal = function(id){
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.style.display = 'none';
};


// ==========================
// EDIT MODAL
// ==========================
window.openEditQuoteModal = function(id, theme, author, content) {
    const modal = document.getElementById('edit-quote-modal');
    if (!modal) {
        console.error('Edit modal not found');
        return;
    }

    // Set the form action dynamically
    const form = modal.querySelector('form');
    if (form) {
        form.action = '/admin/quotes/' + id;
    }

    modal.querySelector('[name="id"]').value = id;
    modal.querySelector('[name="theme"]').value = theme;
    modal.querySelector('[name="author"]').value = author;
    modal.querySelector('[name="content"]').value = content;

    modal.style.display = 'flex';
};


// ==========================
// DOM READY
// ==========================
document.addEventListener('DOMContentLoaded', function () {

    // ADD QUOTE
    const addBtn = document.getElementById('add-quote-btn');
    const wrapper = document.getElementById('quotes-wrapper');

    if (addBtn && wrapper) {
        let quoteIndex = wrapper.querySelectorAll('.quote-block').length;

        addBtn.addEventListener('click', function () {
            const first = wrapper.querySelector('.quote-block');
            if (!first) return;

            const clone = first.cloneNode(true);

            clone.querySelectorAll('input, textarea, select').forEach(field => {
                let name = field.name;

                if (name) {
                    field.name = name.replace(/\[\d+\]/, `[${quoteIndex}]`);
                }

                if (field.tagName === 'SELECT') {
                    field.selectedIndex = 0;
                } else {
                    field.value = '';
                }
            });

            wrapper.appendChild(clone);
            quoteIndex++;
        });
    }


    // SEARCH
    const searchInput = document.getElementById("quote-search");

    if (searchInput) {
        let timer;

        searchInput.addEventListener("keyup", function () {
            clearTimeout(timer);

            timer = setTimeout(() => {
                fetch(`/admin/quotes?search=${encodeURIComponent(this.value)}`, {
                    headers: { "X-Requested-With": "XMLHttpRequest" }
                })
                .then(res => res.text())
                .then(html => {

                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, "text/html");

                    const newTbody = doc.querySelector("tbody");
                    const currentTbody = document.querySelector("tbody");

                    if (newTbody && currentTbody) {
                        currentTbody.innerHTML = newTbody.innerHTML;

                        if (typeof lucide !== "undefined") {
                            lucide.createIcons();
                        }
                    }
                });
            }, 300);
        });
    }

});


// ==========================
// CLICK OUTSIDE CLOSE (only for quote modals)
// ==========================
window.addEventListener('click', function(e) {
    ['create-quote-modal','update-display-modal','edit-quote-modal'].forEach(id => {
        const modal = document.getElementById(id);
        if (modal && e.target === modal) {
            modal.style.display = 'none';
        }
    });
});