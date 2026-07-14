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

    // All <col>s that share this (table, column) identity. The reusable
    // <x-table.table> always has a unique tableKey so this returns exactly one;
    // hand-rolled record tables (Form 137, the TOR) reuse one tableKey across
    // several stacked tables (one per grade level / semester) so a single drag
    // keeps the same column in sync across every section.
    function colsFor(tableKey, colKey) {
        return Array.from(document.querySelectorAll(
            `col[data-table="${CSS.escape(tableKey)}"][data-col-key="${CSS.escape(colKey)}"]`
        ));
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

    // Natural content width of a column = the widest cell content (header + body)
    // measured single-line, plus the cell's own horizontal padding and a small
    // buffer for the grip. Clamped so one huge cell can't blow the layout out.
    const MAX_FIT = 640;
    function autoFitWidth(tableKey, colKey) {
        const cells = document.querySelectorAll(
            `[data-table="${CSS.escape(tableKey)}"][data-column="${CSS.escape(colKey)}"]`
        );
        if (!cells.length) return null;

        const meas = document.createElement('span');
        meas.style.cssText = 'position:absolute;visibility:hidden;white-space:nowrap;left:-9999px;top:0;';
        document.body.appendChild(meas);

        let max = 0;
        cells.forEach((cell) => {
            const cs = getComputedStyle(cell);
            meas.style.fontSize = cs.fontSize;
            meas.style.fontFamily = cs.fontFamily;
            meas.style.fontWeight = cs.fontWeight;
            meas.style.letterSpacing = cs.letterSpacing;
            meas.style.textTransform = cs.textTransform;
            meas.textContent = (cell.textContent || '').trim();
            const padX = (parseFloat(cs.paddingLeft) || 0) + (parseFloat(cs.paddingRight) || 0);
            max = Math.max(max, meas.offsetWidth + padX);
        });

        meas.remove();
        return Math.min(MAX_FIT, Math.max(MIN_WIDTH, Math.ceil(max) + 14));
    }

    let active = null;

    document.addEventListener('mousedown', function (e) {
        const handle = e.target.closest('.col-resizer');
        if (!handle) return;

        const cols = colsFor(handle.dataset.table, handle.dataset.colKey);
        if (!cols.length) return;

        e.preventDefault();
        active = {
            cols,
            tableKey: handle.dataset.table,
            colKey: handle.dataset.colKey,
            startX: e.clientX,
            startW: cols[0].getBoundingClientRect().width,
        };
        document.body.style.cursor = 'col-resize';
        document.body.style.userSelect = 'none';
    });

    document.addEventListener('mousemove', function (e) {
        if (!active) return;
        const next = Math.max(MIN_WIDTH, active.startW + (e.clientX - active.startX));
        active.cols.forEach((col) => { col.style.width = next + 'px'; });
    });

    document.addEventListener('mouseup', function () {
        if (!active) return;
        saveWidth(active.tableKey, active.colKey, active.cols[0].getBoundingClientRect().width);
        active = null;
        document.body.style.cursor = '';
        document.body.style.userSelect = '';
    });

    // Double-click the grip to auto-fit the column to its content (persisted).
    document.addEventListener('dblclick', function (e) {
        const handle = e.target.closest('.col-resizer');
        if (!handle) return;
        e.preventDefault();

        const tableKey = handle.dataset.table;
        const colKey   = handle.dataset.colKey;
        const cols = colsFor(tableKey, colKey);
        if (!cols.length) return;

        const w = autoFitWidth(tableKey, colKey);
        if (!w) return;

        cols.forEach((col) => { col.style.width = w + 'px'; });
        saveWidth(tableKey, colKey, w);
    });

    document.addEventListener('DOMContentLoaded', applySavedWidths);
})();
