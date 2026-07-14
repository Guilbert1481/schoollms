{{-- Hideable Teacher column. Include once per page; it finds every table on
     the page that has a "Teacher" header, tags that column, adds a tiny caret,
     and collapses ALL teacher columns together (one shared, remembered state)
     so it works whether a page has one table or many (e.g. the transcript). --}}
@once
<style>
    .tcol-caret {
        font-size: 10px;
        line-height: 1;
        opacity: .55;
        margin-left: 4px;
        display: inline-block;
        transition: transform .15s ease;
    }
    th.tcol { cursor: pointer; user-select: none; }
    /* Collapsed: shrink the teacher column to a slim strip that only shows the
       caret; the header/cell content is hidden but the column stays clickable. */
    html.teacher-col-collapsed .tcol {
        width: 26px !important;
        max-width: 26px !important;
        min-width: 26px !important;
        overflow: hidden;
        padding-left: 4px !important;
        padding-right: 4px !important;
    }
    /* Under table-layout:fixed the column width is driven by the <col>, not the
       cell — so collapse that too (beats any inline resize width via !important). */
    html.teacher-col-collapsed col.tcol-col {
        width: 26px !important;
    }
    html.teacher-col-collapsed .tcol-content { display: none; }
    html.teacher-col-collapsed th.tcol .tcol-caret { transform: rotate(180deg); }
</style>
<script>
(function () {
    var KEY = 'teacherColCollapsed';

    function apply(collapsed) {
        document.documentElement.classList.toggle('teacher-col-collapsed', collapsed);
    }

    function tag(cell) {
        if (cell.classList.contains('tcol')) return;
        cell.classList.add('tcol');
        // Wrap existing content so it can be hidden while the caret stays.
        var wrap = document.createElement('span');
        wrap.className = 'tcol-content';
        while (cell.firstChild) wrap.appendChild(cell.firstChild);
        cell.appendChild(wrap);
    }

    window.setupHideableTeacherColumns = function () {
        var collapsed = localStorage.getItem(KEY) === '1';
        document.querySelectorAll('table').forEach(function (table) {
            var head = table.tHead;
            if (!head || !head.rows.length) return;
            var ths = head.rows[0].cells, idx = -1;
            for (var i = 0; i < ths.length; i++) {
                if ((ths[i].textContent || '').trim().toLowerCase().indexOf('teacher') === 0) { idx = i; break; }
            }
            if (idx < 0 || ths[idx].dataset.tcolReady) return;

            // Matching <col> (same index — leading drag/#/row-number cols, if any,
            // are present in both the header row and the colgroup) so fixed-layout
            // tables collapse the whole column, not just the header cell.
            var cg = table.querySelector('colgroup');
            if (cg && cg.children[idx]) cg.children[idx].classList.add('tcol-col');

            // Header cell.
            tag(ths[idx]);
            var caret = document.createElement('span');
            caret.className = 'tcol-caret';
            caret.textContent = '⌄';
            ths[idx].appendChild(caret);
            ths[idx].dataset.tcolReady = '1';
            ths[idx].addEventListener('click', function () {
                var now = !document.documentElement.classList.contains('teacher-col-collapsed');
                apply(now);
                localStorage.setItem(KEY, now ? '1' : '0');
            });

            // Body + foot cells in the same column.
            [table.tBodies, table.tFoot ? [table.tFoot] : []].forEach(function (grp) {
                Array.prototype.forEach.call(grp, function (section) {
                    Array.prototype.forEach.call(section.rows, function (r) {
                        if (r.cells[idx]) tag(r.cells[idx]);
                    });
                });
            });
        });
        apply(collapsed);
    };

    document.addEventListener('DOMContentLoaded', window.setupHideableTeacherColumns);
})();
</script>
@endonce
