// =============================================================
// Reusable table — drag-to-resize columns (pairwise)
// -------------------------------------------------------------
// Each header cell of <x-table.table> carries a `.col-resizer`
// handle on its right edge. Dragging it moves ONLY that divider:
// the column grows and its immediate right neighbour shrinks by
// the same amount (or vice-versa), so their combined width — and
// therefore the whole table — stays fixed. Every other column
// stays put; the first column's left edge and the last column's
// right edge never move. The Action column is treated as fixed,
// so the last data column's right edge cannot borrow from it.
//
// Widths persist per table+column in localStorage so they survive
// reloads; double-clicking a grip auto-fits that column (still
// borrowing from its neighbour so the total is preserved).
// =============================================================
(function () {
    'use strict';

    const STORAGE_PREFIX = 'tblcolw:';
    // Low floor so a column can be squeezed until it nearly kisses its
    // neighbour, while the grip stays grabbable.
    const MIN_WIDTH = 16;

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

    // The column that gives/takes space when THIS column is resized: its
    // immediate right-neighbour <col>. Returns null when there is none, or when
    // it is the Action column — that column is fixed, so the last data column's
    // right edge can't be dragged (it's resized from the divider on its left).
    function neighborOf(col) {
        const n = col.nextElementSibling;
        if (!n || n.tagName !== 'COL' || n.dataset.colKey === 'actions') {
            return null;
        }
        return n;
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

    // A grip with no resizable neighbour (the last data column, whose right edge
    // sits against the fixed Action column) would do nothing — hide it so there
    // are no dead handles.
    function hideDeadGrips() {
        document.querySelectorAll('.col-resizer').forEach((handle) => {
            const col = colsFor(handle.dataset.table, handle.dataset.colKey)[0];
            if (!col || !neighborOf(col)) {
                handle.style.display = 'none';
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

    // Pair each dragged <col> with its right neighbour and snapshot both widths.
    // Returns [] when nothing is resizable (last data column against Actions).
    function buildPairs(tableKey, colKey) {
        const pairs = [];
        colsFor(tableKey, colKey).forEach((col) => {
            const nb = neighborOf(col);
            if (nb) {
                pairs.push({
                    col,
                    neighbor: nb,
                    startW: col.getBoundingClientRect().width,
                    startN: nb.getBoundingClientRect().width,
                });
            }
        });
        return pairs;
    }

    // Set the grabbed column to `target`, borrowing the difference from its
    // neighbour so the pair's combined width is unchanged. Clamped so neither
    // side drops below MIN_WIDTH. Applied to every synced pair.
    function applyTarget(pairs, target) {
        pairs.forEach((p) => {
            const sum = p.startW + p.startN;
            const w = Math.max(MIN_WIDTH, Math.min(target, sum - MIN_WIDTH));
            p.col.style.width = w + 'px';
            p.neighbor.style.width = (sum - w) + 'px';
        });
    }

    function persistPair(tableKey, colKey, neighborKey, pair) {
        saveWidth(tableKey, colKey, pair.col.getBoundingClientRect().width);
        saveWidth(tableKey, neighborKey, pair.neighbor.getBoundingClientRect().width);
    }

    let active = null;

    document.addEventListener('mousedown', function (e) {
        const handle = e.target.closest('.col-resizer');
        if (!handle) return;

        const pairs = buildPairs(handle.dataset.table, handle.dataset.colKey);
        if (!pairs.length) return;

        e.preventDefault();
        active = {
            pairs,
            tableKey: handle.dataset.table,
            colKey: handle.dataset.colKey,
            neighborKey: pairs[0].neighbor.dataset.colKey,
            startX: e.clientX,
        };
        document.body.style.cursor = 'col-resize';
        document.body.style.userSelect = 'none';
    });

    document.addEventListener('mousemove', function (e) {
        if (!active) return;
        // Drive the whole gesture from the first pair; each pair conserves its own
        // (col + neighbour) sum, so every synced table stays exactly its own width.
        const primary = active.pairs[0];
        applyTarget(active.pairs, primary.startW + (e.clientX - active.startX));
    });

    document.addEventListener('mouseup', function () {
        if (!active) return;
        persistPair(active.tableKey, active.colKey, active.neighborKey, active.pairs[0]);
        active = null;
        document.body.style.cursor = '';
        document.body.style.userSelect = '';
    });

    // Double-click the grip to auto-fit the column to its content — still
    // borrowing from the neighbour so the total width is preserved (persisted).
    document.addEventListener('dblclick', function (e) {
        const handle = e.target.closest('.col-resizer');
        if (!handle) return;
        e.preventDefault();

        const tableKey = handle.dataset.table;
        const colKey = handle.dataset.colKey;
        const pairs = buildPairs(tableKey, colKey);
        if (!pairs.length) return;

        const w = autoFitWidth(tableKey, colKey);
        if (!w) return;

        applyTarget(pairs, w);
        persistPair(tableKey, colKey, pairs[0].neighbor.dataset.colKey, pairs[0]);
    });

    document.addEventListener('DOMContentLoaded', function () {
        applySavedWidths();
        hideDeadGrips();
    });
})();
