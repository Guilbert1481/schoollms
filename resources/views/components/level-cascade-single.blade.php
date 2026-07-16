@php
    $name = $name ?? 'academic_level_id';
    $selectedId = $selectedId ?? null;
@endphp

{{-- Single-select variant of the Test Builder's level cascade. Navigates the
     admin education-structure tree (education_nodes) and resolves the branch to
     ONE academic_level id (a question carries exactly one). Unlike the Test
     Builder this is NOT gated by existing questions — the authoring page must
     let a teacher tag any level. Scoped inline styles (build-independent). --}}
@if (! empty($levelTree))
    <style>
        .lcs { width: 100%; }
        .lcs .lcs-selects { display: flex; flex-direction: column; gap: 10px; }
        .lcs .lcs-label { display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 4px; }
        .lcs .lcs-select { width: 100%; height: auto; padding: 8px 10px; border: 1px solid #d1d5db; border-radius: 6px; background: #fff; font-size: 14px; }
        .lcs .lcs-levels { margin-top: 12px; background: #fff; border: 1px solid #d1d5db; border-radius: 6px; padding: 10px 12px; }
        .lcs .lcs-levels-hint { font-size: 12px; color: #94a3b8; font-style: italic; }
        .lcs .lcs-levels-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 6px 18px; }
        .lcs .lcs-check { display: flex; align-items: center; gap: 6px; font-size: 13px; color: #334155; cursor: pointer; }
        .lcs .lcs-selected { margin-top: 12px; }
        .lcs .lcs-selected-label { font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px; }
        .lcs .lcs-chosen { display: inline-flex; align-items: center; gap: 6px; background: #eef2ff; color: #3730a3; border: 1px solid #c7d2fe; border-radius: 999px; padding: 3px 10px; font-size: 12px; font-weight: 600; }
        .lcs .lcs-chosen button { border: 0; background: transparent; color: #4338ca; cursor: pointer; font-size: 14px; line-height: 1; padding: 0; }
        .lcs .lcs-empty { font-size: 12px; color: #94a3b8; font-style: italic; }
    </style>

    <div class="lcs" id="lcsRoot">
        <input type="hidden" name="{{ $name }}" id="lcsValue" value="{{ $selectedId }}">

        <div class="lcs-selects" id="lcsSelects">
            <div class="lcs-field" data-depth="0">
                <label class="lcs-label">Educational Level</label>
                <select class="lcs-select" data-depth="0">
                    <option value="" disabled selected>— Select educational level —</option>
                    @foreach ($levelTree as $root)
                        <option value="{{ $root['id'] }}">{{ $root['name'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="lcs-levels" id="lcsLevels">
            <div class="lcs-levels-hint" id="lcsHint">Select an educational level above to choose a grade / year level.</div>
            <div class="lcs-levels-grid" id="lcsGrid"></div>
        </div>

        <div class="lcs-selected">
            <div class="lcs-selected-label">Selected Level</div>
            <div id="lcsChosen"><span class="lcs-empty">None selected yet.</span></div>
        </div>
    </div>

    <script>
    (function () {
        const TREE           = @json($levelTree);
        const LEVELS_BY_NODE = @json($levelsByNode);

        const nodeById = {};
        (function index(list) {
            (list || []).forEach(n => { nodeById[n.id] = n; index(n.children); });
        })(TREE);

        const selects     = document.getElementById('lcsSelects');
        const grid        = document.getElementById('lcsGrid');
        const hint        = document.getElementById('lcsHint');
        const valueInput  = document.getElementById('lcsValue');
        const chosenWrap  = document.getElementById('lcsChosen');

        let chosen = null; // { id, name } — a question has exactly one level.

        const esc = (s) => String(s).replace(/[&<>"']/g, c =>
            ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

        function renderChosen() {
            valueInput.value = chosen ? String(chosen.id) : '';
            if (!chosen) {
                chosenWrap.innerHTML = '<span class="lcs-empty">None selected yet.</span>';
                return;
            }
            chosenWrap.innerHTML = '<span class="lcs-chosen">' + esc(chosen.name) +
                ' <button type="button" id="lcsClear" aria-label="Clear">&times;</button></span>';
            document.getElementById('lcsClear').addEventListener('click', () => {
                chosen = null;
                const r = grid.querySelector('input[type="radio"]:checked');
                if (r) r.checked = false;
                renderChosen();
            });
        }

        function renderLevels(nodeId) {
            const levels = LEVELS_BY_NODE[nodeId] || [];
            grid.innerHTML = '';
            if (!levels.length) {
                hint.textContent = 'No grade / year levels are configured for this branch.';
                hint.style.display = '';
                return;
            }
            hint.style.display = 'none';
            levels.forEach(lvl => {
                const label = document.createElement('label');
                label.className = 'lcs-check';
                label.innerHTML =
                    '<input type="radio" name="lcs-pick" value="' + lvl.id + '"' +
                    (chosen && Number(chosen.id) === Number(lvl.id) ? ' checked' : '') + '> ' + esc(lvl.name);
                label.querySelector('input').addEventListener('change', () => {
                    chosen = { id: lvl.id, name: lvl.name };
                    renderChosen();
                });
                grid.appendChild(label);
            });
        }

        selects.addEventListener('change', (e) => {
            const sel = e.target.closest('select.lcs-select');
            if (!sel) return;
            const depth = Number(sel.dataset.depth);

            selects.querySelectorAll('.lcs-field').forEach(f => {
                if (Number(f.dataset.depth) > depth) f.remove();
            });

            const node = nodeById[sel.value];
            if (!node) return;

            if (node.drillable) {
                const field = document.createElement('div');
                field.className = 'lcs-field';
                field.dataset.depth = depth + 1;
                const options = ['<option value="" disabled selected>— Select —</option>']
                    .concat((node.children || []).map(c => '<option value="' + c.id + '">' + esc(c.name) + '</option>'))
                    .join('');
                field.innerHTML =
                    '<label class="lcs-label">' + esc(node.name) + '</label>' +
                    '<select class="lcs-select" data-depth="' + (depth + 1) + '">' + options + '</select>';
                selects.appendChild(field);
            }

            renderLevels(node.id);
        });

        renderChosen();
    })();
    </script>
@else
    {{-- Fallback: education tree not set up → flat level dropdown. --}}
    <label class="lcs-fallback-label" style="display:block; font-size:12px; font-weight:700; color:#475569; margin-bottom:4px;">Grade / Year Level</label>
    <select name="{{ $name }}" required>
        <option value="">Select</option>
        @foreach ($academicLevels as $level)
            <option value="{{ $level->id }}" @selected($selectedId == $level->id)>{{ $level->name }}</option>
        @endforeach
    </select>
@endif
