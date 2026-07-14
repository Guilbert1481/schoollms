{{--
    Drag-to-resize column grips for the hand-rolled record tables (Transcript of
    Records, Form 137). The resize engine itself (public/js/table/table-resize.js)
    is loaded globally and works on any `<col data-table data-col-key>` whose
    header carries a `.col-resizer` handle — but its CSS lives inline inside the
    <x-table.table> component, so these standalone tables need it too.

    Each resizable table must:
      • be `table-layout: fixed` (so a <col> width is authoritative),
      • give every <col> a `data-table` + `data-col-key`,
      • mark each <th> `position: relative` and drop a `.col-resizer` inside it.
    Reusing one `data-table` across several stacked tables keeps a column in
    sync across every grade-level / semester section in one drag.
--}}
<style>
    .col-resizer {
        position: absolute;
        top: 0;
        right: 0;
        height: 100%;
        width: 11px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: col-resize;
        z-index: 10;
        user-select: none;
        touch-action: none;
    }
    .col-resizer-line {
        width: 2px;
        height: 55%;
        border-radius: 9999px;
        background: #cbd5e1; /* slate-300: faint divider, always visible */
        transition: background .12s ease, height .12s ease, width .12s ease;
    }
    .col-resizer:hover .col-resizer-line {
        width: 3px;
        height: 100%;
        background: #6366f1; /* indigo-500 on hover */
    }
    .col-resizer:active .col-resizer-line {
        width: 3px;
        height: 100%;
        background: #4f46e5; /* indigo-600 while dragging */
    }
    /* Fixed layout truncates rather than overflowing when a column is dragged
       narrow; the header grip needs the last cell's content to not spill. */
    table.record-resizable td,
    table.record-resizable th {
        overflow: hidden;
        text-overflow: ellipsis;
    }
</style>
