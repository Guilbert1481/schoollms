// ESSAY BUILDER – QUESTION BANK

document.addEventListener('DOMContentLoaded', () => {
    const questionsContainer = document.getElementById('questionsContainer');
    const addQuestionBtn     = document.getElementById('addQuestionBtn');
    const saveBtn            = document.getElementById('saveTestBtn');

    if (!questionsContainer || !addQuestionBtn || !saveBtn) {
        console.warn('Essay builder elements missing');
        return;
    }

    let isSaving = false;

    addQuestionBtn.addEventListener('click', addQuestionCard);

    function addQuestionCard() {
        const uid = Date.now();

        const card = document.createElement('div');
        card.className = 'question-card';
        card.dataset.uid = uid;

        card.innerHTML = `
            <div class="qb-meta-bar">
                <div class="qb-meta-left">
                    Difficulty
                    <select class="difficulty-select">
                        <option value="average" selected>Average</option>
                        <option value="advanced">Advanced</option>
                    </select>
                </div>
            </div>

            <div class="question-input-row">
                <span class="question-number"></span>
                <textarea class="question-input" placeholder="Essay question"></textarea>
            </div>
            <div class="explanation-container hidden">
                <label class="explanation-label">Explanation (optional)</label>
                <div class="explanation-row">
                    <textarea 
                        class="explanation-input"
                        placeholder="Add explanation or rationale (optional)"
                    ></textarea>
                    <button type="button" class="remove-explanation-btn">✕</button>
                </div>
            </div>

            <div class="option-actions">
                <div class="action-item action-narrow">
                    <button class="explanation-btn show-explanation-btn" title="Add explanation">📝</button>
                </div>
                <div class="action-item action-narrow">
                    <button class="delete-question-btn" title="Delete question">🗑️</button>
                </div>
            </div>
        `;

        questionsContainer.appendChild(card);
        bindCardButtons(card);
        renumberQuestions();
    }

    function bindCardButtons(card) {
        const deleteQuestionBtn = card.querySelector('.delete-question-btn');
        const showExplanationBtn = card.querySelector('.show-explanation-btn');
        const explanationBox = card.querySelector('.explanation-container');
        const removeExplanationBtn = card.querySelector('.remove-explanation-btn');

        if (deleteQuestionBtn) {
            deleteQuestionBtn.addEventListener('click', () => {
                card.remove();
                renumberQuestions();
            });
        }

        if (showExplanationBtn && explanationBox) {
            showExplanationBtn.addEventListener('click', () => {
                explanationBox.classList.remove('hidden');
                showExplanationBtn.style.display = 'none';
            });
        }

        if (removeExplanationBtn && explanationBox && showExplanationBtn) {
            removeExplanationBtn.addEventListener('click', () => {
                explanationBox.classList.add('hidden');
                showExplanationBtn.style.display = 'inline-block';
                card.querySelector('.explanation-input').value = '';
            });
        }
    }

    function renumberQuestions() {
        document.querySelectorAll('.question-card').forEach((card, i) => {
            const num = card.querySelector('.question-number');
            if (num) num.textContent = i + 1;
        });
    }

    saveBtn.addEventListener('click', async (e) => {
        e.preventDefault();
        e.stopPropagation();

        if (isSaving) return;

        const cards = document.querySelectorAll('.question-card');
        if (!cards.length) {
            alert('No questions to save.');
            return;
        }

        const questions = [];

        cards.forEach(card => {
            const question_text = card.querySelector('.question-input')?.value?.trim();
            const difficulty    = card.querySelector('.difficulty-select')?.value || 'average';
            const explanation   = card.querySelector('.explanation-input')?.value?.trim() || null;
            const points        = parseInt(card.querySelector('.points-input')?.value || 1, 10);

            if (!question_text || !points) return;
            questions.push({
                question_text,
                difficulty,
                explanation,
                points
            });
        });

        if (!questions.length) {
            alert('No valid questions.');
            return;
        }

        isSaving = true;

        try {
            const res = await fetch('/teacher/tests/essay/save', {
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
                console.error(data);
                alert('Save failed.');
                isSaving = false;
            }

        } catch (err) {
            console.error(err);
            alert('Server error.');
            isSaving = false;
        }
    });

    // Start with one question by default
    if (questionsContainer && questionsContainer.children.length === 0) {
        addQuestionCard();
    }
});