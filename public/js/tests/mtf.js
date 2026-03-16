// MTF (Modified True or False) BUILDER

document.addEventListener('DOMContentLoaded', () => {
    const questionsContainer = document.getElementById('questionsContainer');
    const addQuestionBtn     = document.getElementById('addQuestionBtn');
    const saveBtn            = document.getElementById('saveTestBtn');

    if (!questionsContainer || !addQuestionBtn || !saveBtn) {
        console.warn('MTF builder elements missing');
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
                <div class="qb-meta-right">
                    Keyword
                    <input type="text" class="keyword-input">
                </div>
            </div>

            <div class="question-input-row">
                <span class="question-number"></span>
                <input type="text" class="question-input" placeholder="Statement">
            </div>
            <div class="mtf-answer-row">
                <label style="margin-right:22px;">
                    <input type="radio" name="mtf-correct-${uid}" value="true" checked>
                    True
                </label>
                <label>
                    <input type="radio" name="mtf-correct-${uid}" value="false">
                    False
                </label>
            </div>
            <div class="mtf-correction-fields hidden">
                <div style="margin-bottom:6px;">
                    <label style="font-weight:500;color:#e11d48;margin-right:6px;">Incorrect word/phrase:</label>
                    <input type="text" class="incorrect-input" placeholder="Incorrect word/phrase">
                </div>
                <div>
                    <label style="font-weight:500;color:#10b981;margin-right:37px;">Correct answer:</label>
                    <input type="text" class="correct-input" placeholder="Correct answer">
                </div>
            </div>
            <div class="explanation-container hidden" style="margin-top:16px;">
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

        const trueRadio = card.querySelector(`input[type="radio"][value="true"]`);
        const falseRadio = card.querySelector(`input[type="radio"][value="false"]`);
        const correctionFields = card.querySelector('.mtf-correction-fields');

        trueRadio.addEventListener('change', () => {
            if (trueRadio.checked) correctionFields.classList.add('hidden');
        });
        falseRadio.addEventListener('change', () => {
            if (falseRadio.checked) correctionFields.classList.remove('hidden');
        });
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
        let errorFound = false;

        cards.forEach(card => {
            const question_text = card.querySelector('.question-input')?.value?.trim();
            const keyword       = card.querySelector('.keyword-input')?.value?.trim() || null;
            const difficulty    = card.querySelector('.difficulty-select')?.value || 'average';
            const explanation   = card.querySelector('.explanation-input')?.value?.trim() || null;

            const trueRadio = card.querySelector(`input[type="radio"][value="true"]`);
            const falseRadio = card.querySelector(`input[type="radio"][value="false"]`);

            const answer = trueRadio.checked ? "true" : "false";
            let incorrect_phrase = null;
            let correct_phrase = null;

            if (answer === "false") {
                incorrect_phrase = card.querySelector('.incorrect-input')?.value?.trim();
                correct_phrase   = card.querySelector('.correct-input')?.value?.trim();
                if (!incorrect_phrase || !correct_phrase) {
                    errorFound = true;
                    card.querySelector('.mtf-correction-fields').classList.add('error-highlight');
                } else {
                    card.querySelector('.mtf-correction-fields').classList.remove('error-highlight');
                }
            }

            if (!question_text || (answer === "false" && (!incorrect_phrase || !correct_phrase))) return;

            questions.push({
                question_text,
                keyword,
                difficulty,
                explanation,
                answer,
                incorrect_phrase,
                correct_phrase
            });
        });

        if (!questions.length) {
            alert('No valid questions.');
            return;
        }
        if (errorFound) {
            alert('For questions marked False, fill in both Incorrect word/phrase and Correct answer.');
            isSaving = false;
            return;
        }

        isSaving = true;

        try {
            const res = await fetch('/teacher/tests/mtf/save', {
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