// =============================
// COLUMN MODAL
// =============================
function openColumnSettings(tableKey) {
    document.getElementById(tableKey + 'ColumnModal').classList.remove('hidden');
}

function closeColumnModal(tableKey) {
    document.getElementById(tableKey + 'ColumnModal').classList.add('hidden');
}

// =============================
// HIDE A SINGLE COLUMN (header click)
// =============================
function hideTableColumn(tableKey, columnKey) {
    // Hide header + body cells for this column
    document.querySelectorAll(
        `[data-table="${tableKey}"][data-column="${columnKey}"]`
    ).forEach(cell => { cell.style.display = 'none'; });

    // Collapse the matching <col> so table-fixed reclaims its width
    document.querySelectorAll(
        `col[data-table="${tableKey}"][data-col-key="${columnKey}"]`
    ).forEach(c => { c.style.visibility = 'collapse'; });

    // Sync the modal checkbox
    document.querySelectorAll(
        '#'+tableKey+'ColumnModal .column-toggle'
    ).forEach(cb => {
        if (cb.value === columnKey) cb.checked = false;
    });

    // Persist visible list
    let allColumns = Array.from(
        document.querySelectorAll('#'+tableKey+'ColumnModal .column-toggle')
    ).map(cb => cb.value);

    let saved = localStorage.getItem(tableKey + '_columns');
    let visible = saved ? JSON.parse(saved) : allColumns.slice();
    visible = visible.filter(k => k !== columnKey);
    localStorage.setItem(tableKey + '_columns', JSON.stringify(visible));
    localStorage.setItem(tableKey + '_known',   JSON.stringify(allColumns));
}

// =============================
// SAVE COLUMN SETTINGS
// =============================
function saveColumnSettings(tableKey) {

    let visibleColumns = [];
    let widths = {};
    let totalWidth = 0;

    document.querySelectorAll('#'+tableKey+'ColumnModal .column-toggle')
        .forEach(cb => {

            let column = cb.value;
            let widthInput = document.querySelector(
                '#'+tableKey+'ColumnModal .column-width[data-column="'+column+'"]'
            );

            if(cb.checked){
                visibleColumns.push(column);

                let width = parseInt(widthInput.value) || 0;
                widths[column] = width;
                totalWidth += width;
            }
        });

    // Save settings
    localStorage.setItem(tableKey + '_columns', JSON.stringify(visibleColumns));
    localStorage.setItem(tableKey + '_widths', JSON.stringify(widths));

    // Track every known column so hidden ones don't get auto-restored on reload
    let allColumns = Array.from(
        document.querySelectorAll('#'+tableKey+'ColumnModal .column-toggle')
    ).map(cb => cb.value);
    localStorage.setItem(tableKey + '_known', JSON.stringify(allColumns));

    // Apply visibility immediately
    document.querySelectorAll(`[data-table="${tableKey}"]`)
        .forEach(cell => {
            cell.style.display = visibleColumns.includes(cell.dataset.column) ? '' : 'none';
        });

    // Collapse / restore the <col> elements (so table-fixed redistributes width)
    document.querySelectorAll(`col[data-table="${tableKey}"]`)
        .forEach(c => {
            c.style.visibility = visibleColumns.includes(c.dataset.colKey) ? '' : 'collapse';
        });

    applyColumnWidths(tableKey);

    closeColumnModal(tableKey);
}

// =============================
// LOAD SAVED COLUMN SETTINGS
// =============================
function loadColumnSettings(tableKey) {
    let savedCols   = localStorage.getItem(tableKey + '_columns');
    let savedKnown  = localStorage.getItem(tableKey + '_known');
    let savedWidths = JSON.parse(localStorage.getItem(tableKey + '_widths') || '{}');

    // Collect all columns currently defined for this table (from the modal checkboxes)
    let allColumns = Array.from(
        document.querySelectorAll('#'+tableKey+'ColumnModal .column-toggle')
    ).map(cb => cb.value);

    // Restore visibility
    if(savedCols){
        let visibleColumns = JSON.parse(savedCols);
        // If the "known" list isn't stored yet (legacy save), seed it with the
        // current full schema so existing hidden columns are NOT re-added.
        let knownColumns = savedKnown ? JSON.parse(savedKnown) : allColumns.slice();
        if (!savedKnown) {
            localStorage.setItem(tableKey + '_known', JSON.stringify(knownColumns));
        }

        // Auto-include only columns we have NEVER seen before (newly added to the schema).
        // Columns the user explicitly hid stay in `known` but out of `visible`.
        let added = false;
        allColumns.forEach(col => {
            if(!knownColumns.includes(col)){
                knownColumns.push(col);
                visibleColumns.push(col);
                added = true;
            }
        });
        if(added){
            localStorage.setItem(tableKey + '_columns', JSON.stringify(visibleColumns));
            localStorage.setItem(tableKey + '_known',   JSON.stringify(knownColumns));
        }

        document.querySelectorAll(`[data-table="${tableKey}"]`)
            .forEach(cell => {
                if(!visibleColumns.includes(cell.dataset.column)){
                    cell.style.display = 'none';
                }
            });

        // Collapse the <col> for hidden columns
        document.querySelectorAll(`col[data-table="${tableKey}"]`)
            .forEach(c => {
                if(!visibleColumns.includes(c.dataset.colKey)){
                    c.style.visibility = 'collapse';
                }
            });

        // Update checkboxes in the modal
        document.querySelectorAll(
            '#'+tableKey+'ColumnModal .column-toggle'
        ).forEach(cb => {
            cb.checked = visibleColumns.includes(cb.value);
        });
    }

    // Restore width inputs in the modal so the form reflects current config
    document.querySelectorAll(
        '#'+tableKey+'ColumnModal .column-width'
    ).forEach(input => {
        let col = input.dataset.column;
        if(savedWidths[col] !== undefined && savedWidths[col] !== 0){
            input.value = savedWidths[col];
        }
    });
}

// =============================
// INIT WHEN PAGE LOADS
// =============================
document.addEventListener("DOMContentLoaded", function(){

    document.querySelectorAll("table").forEach(table => {
        let tableKey = table.id.replace("Table", "");

        loadColumnSettings(tableKey);
        applyColumnWidths(tableKey);
    });

});


function applyColumnWidths(tableKey){

    let widths = JSON.parse(localStorage.getItem(tableKey + '_widths') || '{}');

    Object.keys(widths).forEach(column => {

        // Apply to <col> (authoritative under table-fixed)
        document.querySelectorAll(
            `col[data-table="${tableKey}"][data-col-key="${column}"]`
        ).forEach(c => {
            c.style.width = widths[column] + '%';
        });

        let cells = document.querySelectorAll(
            `[data-table="${tableKey}"][data-column="${column}"]`
        );

        cells.forEach(c => {
            c.style.width = widths[column] + '%';
        });

        let headers = document.querySelectorAll(
            `th[data-table="${tableKey}"][data-column="${column}"]`
        );

        headers.forEach(h => {
            h.style.width = widths[column] + '%';
        });
    });

}