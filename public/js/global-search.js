// js/global-search.js

// =========================
// OPEN / CLOSE
// =========================
window.openGlobalSearch = function () {
    const modal = document.getElementById('globalSearchModal');

    if (modal) {
        modal.classList.remove('hidden');
        modal.style.display = 'flex';

        setTimeout(() => {
            document.getElementById('globalSearchInput')?.focus();
        }, 100);
    }
}

window.closeGlobalSearch = function () {
    const modal = document.getElementById('globalSearchModal');

    if (modal) {
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }
}

// =========================
// INIT (SAFE LOAD)
// =========================
window.addEventListener("load", function () {

    // ✅ CLEAR BUTTON
    const clearBtn = document.getElementById('clearGlobalSearch');
    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            document.getElementById('globalSearchInput').value = '';
            document.getElementById('globalSearchResults').innerHTML =
                '<div class="text-sm text-slate-400">Start typing to search the system...</div>';
        });
    }

    // ✅ ESC CLOSE
    document.addEventListener('keydown', function (e) {
        if (e.key === "Escape") {
            closeGlobalSearch();
        }
    });

    // ✅ CTRL + K SHORTCUT
    document.addEventListener('keydown', function (e) {
        if (e.ctrlKey && e.key === 'k') {
            e.preventDefault();
            openGlobalSearch();
        }
    });

});

// =========================
// SEARCH FUNCTION
// =========================
window.performGlobalSearch = function () {

    let input = document.getElementById('globalSearchInput');
    let resultsDiv = document.getElementById('globalSearchResults');

    if (!input || !resultsDiv) return;

    let query = input.value;

    if (query.length < 2) return;

    fetch(`/global-search?q=${encodeURIComponent(query)}`)
        .then(res => res.json())
        .then(data => {

            resultsDiv.innerHTML = '';

            if (!data || data.length === 0) {
                resultsDiv.innerHTML =
                    '<div class="text-sm text-slate-400">No results found.</div>';
                return;
            }

            data.forEach(group => {

                // GROUP TITLE
                let groupHTML = `
                    <div class="mt-3">
                        <div class="text-xs text-slate-400 font-bold uppercase mb-1">
                            ${group.type}
                        </div>
                    </div>
                `;

                resultsDiv.innerHTML += groupHTML;

                // ITEMS
                group.items.forEach(item => {
                    resultsDiv.innerHTML += `
                        <a href="${item.link}" class="block p-2 hover:bg-slate-100 rounded">
                            <div class="font-medium">${item.title}</div>
                            <div class="text-xs text-slate-500">${item.subtitle}</div>
                        </a>
                    `;
                });

            });

        })
        .catch(err => {
            console.error('Global Search Error:', err);
            resultsDiv.innerHTML =
                '<div class="text-sm text-red-400">Error loading results.</div>';
        });
}