document.addEventListener('DOMContentLoaded', () => {
    const questionsContainer = document.getElementById('questionsContainer');
    const saveBtn = document.getElementById('saveTestBtn');

    function renderMatchingTable() {
        const card = document.createElement('div');
        card.className = 'question-card';
        card.dataset.uid = 'matching-main';

        card.innerHTML = `
            <div class="qb-meta-bar" style="margin-bottom:16px; display: flex; justify-content: start;">
                <div class="qb-meta-left">
                    Difficulty
                    <select class="difficulty-select">
                        <option value="average" selected>Average</option>
                        <option value="advanced">Advanced</option>
                    </select>
                </div>
            </div>

            <div class="matching-table-scroll" style="overflow-x:auto;">
                <table class="matching-table" style="width:100%; border-collapse:separate; border-spacing:0 7px; margin-bottom:12px;">
                    <thead>
                        <tr>
                            <th style="width: 30px; text-align:center;">#</th>
                            <th style="min-width: 320px; text-align:center;">Prompt / Question</th>
                            <th style="width: 160px; text-align:center;">Answer</th>
                            <th style="width:38px;"></th>
                        </tr>
                    </thead>
                    <tbody class="matching-table-body"></tbody>
                </table>
            </div>

            <button type="button" class="add-pair-btn tb-btn primary"
                style="margin:10px 0 0 0;padding:2px 18px;height:32px;display:inline-block;">
                ➕ Add Pair
            </button>
        `;

        questionsContainer.appendChild(card);

        const tbody = card.querySelector('.matching-table-body');
        for(let i=0; i<5; i++) {
            appendRow(tbody, i);
        }

        bindTableButtons(card);
    }

    function appendRow(tbody, idx) {
        const tr = document.createElement('tr');
        tr.className = 'pair-row';

        tr.innerHTML = `
            <td style="text-align:center;font-weight:600;">${idx+1}</td>
            <td>
                <input type="text" class="left-item" placeholder="Enter question/prompt"
                    style="width:98%; min-width:200px; padding:2px 5px; border:1px solid #bbb; border-radius:4px;">
            </td>
            <td style="text-align:center;">
                <input type="text" class="right-item"
                    placeholder="Enter answer"
                    style="width: 150px; padding:2px 4px; border:1px solid #bbb; border-radius:4px;">
            </td>
            <td style="text-align:center;">
                <button type="button" class="delete-pair-btn" title="Delete"
                    style="border:none; background:none; color:#ba2737; font-size:1.15em; cursor:pointer;">×</button>
            </td>
        `;
        tbody.appendChild(tr);
    }

    function bindTableButtons(card) {
        const tbody = card.querySelector('.matching-table-body');
        card.querySelector('.add-pair-btn').addEventListener('click', () => {
            appendRow(tbody, tbody.children.length);
            updateIndicators(tbody);
            bindDeleteButtons(tbody);
        });
        bindDeleteButtons(tbody);
    }

    function updateIndicators(tbody) {
        const rows = Array.from(tbody.querySelectorAll('tr.pair-row'));
        rows.forEach((row, i) => {
            row.children[0].textContent = i+1;
        });
    }

    function bindDeleteButtons(tbody) {
        tbody.querySelectorAll('.delete-pair-btn').forEach((btn, i) => {
            btn.onclick = function() {
                const row = btn.closest('tr.pair-row');
                row.remove();
                updateIndicators(tbody);
            };
        });
    }

    saveBtn.addEventListener('click', async (e) => {
        e.preventDefault();
        const card = document.querySelector('.question-card');
        if (!card) { alert('No matching question found.'); return; }

        const difficulty = card.querySelector('.difficulty-select')?.value || 'average';

        const tbody = card.querySelector('.matching-table-body');
        const rows = Array.from(tbody.querySelectorAll('tr.pair-row'));
        const pairs = [];

        rows.forEach(row => {
            const prompt = row.querySelector('.left-item')?.value?.trim();
            const answer = row.querySelector('.right-item')?.value?.trim();
            if (prompt && answer)
                pairs.push([prompt, answer]);
        });

        if (!pairs.length) {
            alert('You must complete at least one matching pair (question and answer).');
            return;
        }

        const questions = [{
            question_text: '',
            keyword: null,
            difficulty,
            pairs
        }];

        try {
            saveBtn.disabled = true;
            const res = await fetch('/teacher/tests/matching/save', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ questions })
            });
            const data = await res.json();
            if (res.ok && data.success) {
                window.location.href = data.redirect;
            } else {
                console.error(data); alert('Save failed.');
            }
        } catch (err) {
            console.error(err);
            alert('Server error.');
        }
        saveBtn.disabled = false;
    });

    if (questionsContainer && questionsContainer.children.length === 0) {
        renderMatchingTable();
    }
});