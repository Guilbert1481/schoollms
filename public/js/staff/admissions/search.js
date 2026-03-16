document.addEventListener("DOMContentLoaded", function () {

    const input = document.getElementById('globalSearch');
    const resultsBox = document.getElementById('searchResults');

    if (!input || !resultsBox) return;

    let debounceTimer;

    input.addEventListener('keyup', function () {

        const query = this.value.trim();

        clearTimeout(debounceTimer);

        debounceTimer = setTimeout(() => {

            if (query.length < 2) {
                resultsBox.classList.add('hidden');
                resultsBox.innerHTML = '';
                return;
            }

            fetch(`/admission/search?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {

                    let html = '';

                    // Applications
                    if (data.applications && data.applications.length > 0) {
                        html += `
                            <div class="px-4 py-2 text-xs font-semibold text-slate-400 uppercase">
                                Applications
                            </div>
                        `;

                        data.applications.forEach(item => {
                            html += `
                                <a href="/admission/applications/${item.id}"
                                   class="block px-4 py-2 text-sm hover:bg-slate-100 transition">
                                    ${item.name}
                                    <span class="text-slate-400 text-xs">
                                        (${item.email})
                                    </span>
                                </a>
                            `;
                        });
                    }

                    // Students
                    if (data.students && data.students.length > 0) {
                        html += `
                            <div class="px-4 py-2 text-xs font-semibold text-slate-400 uppercase">
                                Students
                            </div>
                        `;

                        data.students.forEach(item => {
                            html += `
                                <a href="/admission/students/${item.id}"
                                   class="block px-4 py-2 text-sm hover:bg-slate-100 transition">
                                    ${item.name}
                                </a>
                            `;
                        });
                    }

                    if (!html) {
                        html = `
                            <div class="px-4 py-3 text-sm text-slate-500">
                                No results found
                            </div>
                        `;
                    }

                    resultsBox.innerHTML = html;
                    resultsBox.classList.remove('hidden');

                })
                .catch(() => {
                    resultsBox.classList.add('hidden');
                });

        }, 300); // debounce delay

    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function (e) {
        if (!input.contains(e.target) && !resultsBox.contains(e.target)) {
            resultsBox.classList.add('hidden');
        }
    });

});
