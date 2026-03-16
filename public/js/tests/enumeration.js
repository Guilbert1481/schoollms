document.addEventListener('DOMContentLoaded', () => {
    const questionsContainer = document.getElementById('questionsContainer');
    const addQuestionBtn     = document.getElementById('addQuestionBtn');
    const saveBtn            = document.getElementById('saveTestBtn');

    if (!questionsContainer || !addQuestionBtn || !saveBtn) {
        console.warn('Enumeration builder elements missing');
        return;
    }

    let isSaving = false;

    addQuestionBtn.addEventListener('click', () => {
        addQuestionCard();
    });

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
                <div class="qb-meta-right">
                    Keyword
                    <input type="text" class="keyword-input">
                </div>
            </div>

            <div class="question-input-row">
                <span class="question-number"></span>
                <input type="text" class="question-input" placeholder="Question or Prompt">
            </div>
            <div class="answers-container">
                ${renderAnswer(uid, 1)}
                ${renderAnswer(uid, 2)}
                ${renderAnswer(uid, 3)}
            </div>
            <div class="option-actions">
                <div class="action-item action-narrow" style="margin-bottom:-2px;">
                    <button type="button" id="addAnswerBtn" class="add-answer-btn">
                    + Add Answer
                </button>
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

    function renderAnswer(uid, number) {
        return `
            <div class="answer-row">
                <input type="text" class="answer-input" placeholder="Answer ${number}">
                <button type="button" class="remove-explanation-btn">✕</button>
            </div>
        `;
    }

    function bindCardButtons(card) {
        const addAnswerBtn = card.querySelector('.add-answer-btn');
        const deleteQuestionBtn = card.querySelector('.delete-question-btn');

        if (addAnswerBtn) {
            addAnswerBtn.addEventListener('click', () => {
                const uid = card.dataset.uid;
                const count = card.querySelectorAll('.answer-row').length + 1;
                card.querySelector('.answers-container')
                    .insertAdjacentHTML('beforeend', renderAnswer(uid, count));
                bindDeleteAnswer(card);
            });
        }

        if (deleteQuestionBtn) {
            deleteQuestionBtn.addEventListener('click', () => {
                card.remove();
                renumberQuestions();
            });
        }

        bindDeleteAnswer(card);
    }

    function bindDeleteAnswer(card) {
        card.querySelectorAll('.delete-answer').forEach(btn => {
            btn.onclick = () => {
                const all = card.querySelectorAll('.answer-row');
                if (all.length > 1) { // You may set to >= 1 for always at least 1
                    btn.closest('.answer-row').remove();
                }
            };
        });
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
            const keyword       = card.querySelector('.keyword-input')?.value?.trim() || null;
            const difficulty    = card.querySelector('.difficulty-select')?.value || 'average';

            if (!question_text) return;

            const answers = [];
            card.querySelectorAll('.answer-row').forEach(row => {
                const text = row.querySelector('.answer-input')?.value?.trim();
                if (text) answers.push(text);
            });

            if (answers.length > 0) {
                questions.push({
                    question_text,
                    keyword,
                    difficulty,
                    answers
                });
            }
        });

        if (!questions.length) {
            alert('No valid questions.');
            return;
        }
        isSaving = true;
        try {
            const res = await fetch('/teacher/tests/enumeration/save', {
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

    // Start with one question
    if (questionsContainer && questionsContainer.children.length === 0) {
        addQuestionCard();
    }
});