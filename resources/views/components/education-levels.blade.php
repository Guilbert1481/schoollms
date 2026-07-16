@php
    // Reusable education-level picker. Navigates the admin education-structure
    // tree (only the school's OFFERED nodes — resolved server-side) and produces
    // academic_level ids for the form. Two modes:
    //   multiple = true  → checkboxes → chips → name="academic_levels[]" (Test Builder)
    //   multiple = false → radios     → one chip → name="academic_level_id"  (Question Metadata)
    $multiple = $multiple ?? false;
    $name = $name ?? ($multiple ? 'academic_levels' : 'academic_level_id');
    $selected = array_map('intval', (array) ($selected ?? []));
@endphp

@if (! empty($levelTree))
    <style>
        .edl { width: 100%; }
        .edl .edl-selects { display: flex; flex-direction: column; gap: 10px; }
        .edl .edl-label { display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 4px; }
        .edl .edl-select { width: 100%; height: auto; padding: 8px 10px; border: 1px solid #d1d5db; border-radius: 6px; background: #fff; font-size: 14px; }
        .edl .edl-levels { margin-top: 12px; background: #fff; border: 1px solid #d1d5db; border-radius: 6px; padding: 10px 12px; }
        .edl .edl-levels-hint { font-size: 12px; color: #94a3b8; font-style: italic; }
        .edl .edl-levels-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 6px 18px; }
        .edl .edl-check { display: flex; align-items: center; gap: 6px; font-size: 13px; color: #334155; cursor: pointer; }
        .edl .edl-selected { margin-top: 12px; }
        .edl .edl-selected-label { font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px; }
        .edl .edl-chips { display: flex; flex-wrap: wrap; gap: 6px; }
        .edl .edl-chip { display: inline-flex; align-items: center; gap: 6px; background: #eef2ff; color: #3730a3; border: 1px solid #c7d2fe; border-radius: 999px; padding: 3px 10px; font-size: 12px; font-weight: 600; }
        .edl .edl-chip button { border: 0; background: transparent; color: #4338ca; cursor: pointer; font-size: 14px; line-height: 1; padding: 0; }
        .edl .edl-empty { font-size: 12px; color: #94a3b8; font-style: italic; }
    </style>

    <div class="edl" id="edlRoot">
        <div class="edl-selects" id="edlSelects">
            <div class="edl-field" data-depth="0">
                <label class="edl-label">Educational Level</label>
                <select class="edl-select" data-depth="0">
                    <option value="" disabled selected>— Select educational level —</option>
                    @foreach ($levelTree as $root)
                        <option value="{{ $root['id'] }}">{{ $root['name'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="edl-levels" id="edlLevels">
            <div class="edl-levels-hint" id="edlHint">Select an educational level above to choose {{ $multiple ? 'grade / year levels' : 'a grade / year level' }}.</div>
            <div class="edl-levels-grid" id="edlGrid"></div>
        </div>

        <div class="edl-selected">
            <div class="edl-selected-label">{{ $multiple ? 'Selected Levels' : 'Selected Level' }}</div>
            <div class="edl-chips" id="edlChips">
                <span class="edl-empty" id="edlChipsEmpty">None selected yet.</span>
            </div>
        </div>
    </div>

    <script>
    (function () {
        const TREE           = @json($levelTree);
        const LEVELS_BY_NODE = @json($levelsByNode);
        const ALL_LEVELS     = @json($academicLevels->pluck('name', 'id'));
        const SAVED          = @json($selected);
        const MULTIPLE       = @json((bool) $multiple);
        const FIELD          = MULTIPLE ? @json($name) + '[]' : @json($name);

        const nodeById = {};
        (function index(list) {
            (list || []).forEach(n => { nodeById[n.id] = n; index(n.children); });
        })(TREE);

        const selects    = document.getElementById('edlSelects');
        const grid       = document.getElementById('edlGrid');
        const hint       = document.getElementById('edlHint');
        const chipsWrap  = document.getElementById('edlChips');
        const chipsEmpty = document.getElementById('edlChipsEmpty');

        // id -> name. In single mode this holds at most one entry.
        const selected = new Map();
        SAVED.forEach(id => { if (ALL_LEVELS[id] !== undefined) selected.set(Number(id), ALL_LEVELS[id]); });

        const esc = (s) => String(s).replace(/[&<>"']/g, c =>
            ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

        function renderChips() {
            chipsWrap.querySelectorAll('.edl-chip, input[data-edl-value]').forEach(el => el.remove());
            chipsEmpty.style.display = selected.size ? 'none' : '';

            selected.forEach((name, id) => {
                const chip = document.createElement('span');
                chip.className = 'edl-chip';
                chip.innerHTML = esc(name) + ' <button type="button" data-id="' + id + '" aria-label="Remove">&times;</button>';
                chipsWrap.appendChild(chip);

                const input = document.createElement('input');
                input.type = 'checkbox';
                input.name = FIELD;
                input.value = String(id);
                input.checked = true;
                input.hidden = true;
                input.setAttribute('data-edl-value', '1');
                chipsWrap.appendChild(input);
            });

            // Level selection can drive dependent fields (e.g. the Test Builder's
            // Subject list). No-op on pages that don't listen.
            document.dispatchEvent(new CustomEvent('assessment-levels:changed'));
        }

        chipsWrap.addEventListener('click', (e) => {
            const btn = e.target.closest('button[data-id]');
            if (!btn) return;
            const id = Number(btn.dataset.id);
            selected.delete(id);
            const box = grid.querySelector('.edl-pick[value="' + id + '"]');
            if (box) box.checked = false;
            renderChips();
        });

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
                label.className = 'edl-check';
                const type = MULTIPLE ? 'checkbox' : 'radio';
                label.innerHTML =
                    '<input type="' + type + '" class="edl-pick" name="edl-pick" value="' + lvl.id + '"' +
                    (selected.has(Number(lvl.id)) ? ' checked' : '') + '> ' + esc(lvl.name);
                label.querySelector('input').addEventListener('change', (ev) => {
                    const id = Number(lvl.id);
                    if (!MULTIPLE) selected.clear();
                    if (ev.target.checked) selected.set(id, lvl.name);
                    else selected.delete(id);
                    renderChips();
                });
                grid.appendChild(label);
            });
        }

        selects.addEventListener('change', (e) => {
            const sel = e.target.closest('select.edl-select');
            if (!sel) return;
            const depth = Number(sel.dataset.depth);

            selects.querySelectorAll('.edl-field').forEach(f => {
                if (Number(f.dataset.depth) > depth) f.remove();
            });

            const node = nodeById[sel.value];
            if (!node) return;

            if (node.drillable) {
                const field = document.createElement('div');
                field.className = 'edl-field';
                field.dataset.depth = depth + 1;
                const options = ['<option value="" disabled selected>— Select —</option>']
                    .concat((node.children || []).map(c => '<option value="' + c.id + '">' + esc(c.name) + '</option>'))
                    .join('');
                field.innerHTML =
                    '<label class="edl-label">' + esc(node.name) + '</label>' +
                    '<select class="edl-select" data-depth="' + (depth + 1) + '">' + options + '</select>';
                selects.appendChild(field);
            }

            renderLevels(node.id);
        });

        renderChips();
    })();
    </script>
@else
    {{-- Fallback: the school offers no education-structure nodes yet, so show the
         flat level vocabulary directly (values are academic_level ids). --}}
    @if ($multiple)
        <div class="edl-fallback-grid" style="display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:6px 18px;">
            @foreach ($academicLevels as $level)
                <label style="display:flex; align-items:center; gap:6px; font-size:13px; color:#334155;">
                    <input type="checkbox" name="{{ $name }}[]" value="{{ $level->id }}" @checked(in_array($level->id, $selected))>
                    {{ $level->name }}
                </label>
            @endforeach
        </div>
    @else
        <label style="display:block; font-size:12px; font-weight:700; color:#475569; margin-bottom:4px;">Grade / Year Level</label>
        <select name="{{ $name }}" required>
            <option value="">Select</option>
            @foreach ($academicLevels as $level)
                <option value="{{ $level->id }}" @selected(in_array($level->id, $selected))>{{ $level->name }}</option>
            @endforeach
        </select>
    @endif
@endif
