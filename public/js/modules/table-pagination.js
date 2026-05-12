// js/modules/table-pagination.js

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
        this.paginationDiv = document.createElement("div");
        this.paginationDiv.className = "flex justify-center mt-6 gap-2";
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
        prevBtn.innerText = "‹ PREV";
        prevBtn.className = "px-4 py-2 rounded bg-gray-200";
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
                btn.className = "px-4 py-2 rounded bg-gray-700 text-white";
            } else {
                btn.className = "px-4 py-2 rounded bg-gray-200";
            }

            btn.onclick = () => {
                this.currentPage = i;
                this.render();
            };

            this.paginationDiv.appendChild(btn);
        }

        // NEXT
        const nextBtn = document.createElement("button");
        nextBtn.innerText = "NEXT ›";
        nextBtn.className = "px-4 py-2 rounded bg-gray-200";
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
        let table = new TablePagination(tableName + "Table", 10);
        const filter = document.getElementById(tableName + "Filter");

        if(filter){
            filter.addEventListener('input', function(e) {
                const value = e.target.value.toLowerCase();

                table.filter(row => {
                    return row.innerText.toLowerCase().includes(value);
                });
            });
        }

        // store instance globally so we can refresh later
        window[tableName + "Pagination"] = table;

    }, 300);
}


//IF THERE IS MULTIPLE TABS ON THE PAGE
function refreshTable(tableName) {
    setTimeout(() => {
        if (window[tableName + "Pagination"]) {
            window[tableName + "Pagination"].render();
        }
    }, 200);
}