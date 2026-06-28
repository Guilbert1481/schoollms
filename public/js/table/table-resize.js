// =============================================================
// Reusable table — drag-to-resize columns
// -------------------------------------------------------------
// Each header cell of <x-table.table> carries a `.col-resizer`
// handle on its right edge. Dragging it left/right resizes that
// column (the table is `table-fixed`, so the neighbouring columns
// give and take the space). Widths persist per table+column in
// localStorage so they survive reloads.
// =============================================================
(function () {
    'use strict';

    const STORAGE_PREFIX = 'tblcolw:';
    const MIN_WIDTH = 48;

    function colFor(tableKey, colKey) {
        return document.querySelector(
            `col[data-table="${CSS.escape(tableKey)}"][data-col-key="${CSS.escape(colKey)}"]`
        );
    }

    function loadWidths(tableKey) {
        try {
            return JSON.parse(localStorage.getItem(STORAGE_PREFIX + tableKey) || '{}');
        } catch (e) {
            return {};
        }
    }

    function saveWidth(tableKey, colKey, px) {
        const widths = loadWidths(tableKey);
        widths[colKey] = Math.round(px);
        localStorage.setItem(STORAGE_PREFIX + tableKey, JSON.stringify(widths));
    }

    function applySavedWidths() {
        document.querySelectorAll('col[data-table][data-col-key]').forEach((col) => {
            const w = loadWidths(col.dataset.table)[col.dataset.colKey];
            if (w) {
                col.style.width = w + 'px';
            }
        });
    }

    let active = null;

    document.addEventListener('mousedown', function (e) {
        const handle = e.target.closest('.col-resizer');
        if (!handle) return;

        const col = colFor(handle.dataset.table, handle.dataset.colKey);
        if (!col) return;

        e.preventDefault();
        active = {
            col,
            tableKey: handle.dataset.table,
            colKey: handle.dataset.colKey,
            startX: e.clientX,
            startW: col.getBoundingClientRect().width,
        };
        document.body.style.cursor = 'col-resize';
        document.body.style.userSelect = 'none';
    });

    document.addEventListener('mousemove', function (e) {
        if (!active) return;
        const next = Math.max(MIN_WIDTH, active.startW + (e.clientX - active.startX));
        active.col.style.width = next + 'px';
    });

    document.addEventListener('mouseup', function () {
        if (!active) return;
        saveWidth(active.tableKey, active.colKey, active.col.getBoundingClientRect().width);
        active = null;
        document.body.style.cursor = '';
        document.body.style.userSelect = '';
    });

    document.addEventListener('DOMContentLoaded', applySavedWidths);
})();
