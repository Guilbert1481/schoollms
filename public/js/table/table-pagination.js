// public/js/table/table-pagination.js

class TablePagination {
    constructor(tableId, rowsPerPage = 10) {
        this.table = document.getElementById(tableId);
        if (!this.table) return;

        this.rowsPerPage = rowsPerPage;
        this.currentPage = 1;
        this.rows = Array.from(this.table.querySelectorAll("tbody tr"));
        this.filteredRows = [...this.rows];

        this.createPaginationControls();
        this.render();
    }

    // External filter function
    filter(callback) {
        this.filteredRows = this.rows.filter(callback);
        this.currentPage = 1;
        this.render();
    }

    createPaginationControls() {
        // Prevent duplicate pagination
        let existing = this.table.parentNode.querySelector(".table-pagination");
        if (existing) {
            this.paginationDiv = existing;
            return;
        }

        this.paginationDiv = document.createElement("div");
        this.paginationDiv.className = "table-pagination flex justify-center mt-4 gap-2";

        this.table.parentNode.appendChild(this.paginationDiv);
    }

    render() {
        if (!this.table) return;

        const start = (this.currentPage - 1) * this.rowsPerPage;
        const end = start + this.rowsPerPage;

        this.rows.forEach(row => row.style.display = "none");

        this.filteredRows.slice(start, end).forEach(row => {
            row.style.display = "";
        });

        this.renderPaginationButtons();
    }

    renderPaginationButtons() {
        this.paginationDiv.innerHTML = "";

        const totalPages = Math.ceil(this.filteredRows.length / this.rowsPerPage);
        if (totalPages <= 1) return;

        // PREV
        const prevBtn = document.createElement("button");
        prevBtn.innerText = "‹ Prev";
        prevBtn.className = "px-3 py-1 rounded border";
        prevBtn.disabled = this.currentPage === 1;
        prevBtn.onclick = () => {
            this.currentPage--;
            this.render();
        };
        this.paginationDiv.appendChild(prevBtn);

        // PAGE NUMBERS
        for (let i = 1; i <= totalPages; i++) {
            const btn = document.createElement("button");
            btn.innerText = i;

            if (i === this.currentPage) {
                btn.className = "px-3 py-1 rounded bg-gray-800 text-white";
            } else {
                btn.className = "px-3 py-1 rounded border";
            }

            btn.onclick = () => {
                this.currentPage = i;
                this.render();
            };

            this.paginationDiv.appendChild(btn);
        }

        // NEXT
        const nextBtn = document.createElement("button");
        nextBtn.innerText = "Next ›";
        nextBtn.className = "px-3 py-1 rounded border";
        nextBtn.disabled = this.currentPage === totalPages;
        nextBtn.onclick = () => {
            this.currentPage++;
            this.render();
        };
        this.paginationDiv.appendChild(nextBtn);
    }
}


// GLOBAL INIT FUNCTION
function initTable(tableName) {
    setTimeout(() => {
        let tableInstance = new TablePagination(tableName + "Table", 10);
        const filter = document.getElementById(tableName + "Filter");

        if (filter) {
            filter.addEventListener('input', function (e) {
                const value = e.target.value.toLowerCase();

                tableInstance.filter(row => {
                    return row.innerText.toLowerCase().includes(value);
                });
            });
        }

        // Store instance globally
        window[tableName + "Pagination"] = tableInstance;

    }, 200);
}


// AUTO INITIALIZE ALL TABLES
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll("table[id$='Table']").forEach(table => {
        let tableName = table.id.replace("Table", "");
        initTable(tableName);
    });
});


// REFRESH TABLE (For Tabs)
function refreshTable(tableName) {
    setTimeout(() => {
        if (window[tableName + "Pagination"]) {
            window[tableName + "Pagination"].render();
        }
    }, 200);
}