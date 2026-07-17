/* =========================================================
   MCQ BUILDER – QUESTION BANK (BATCH, ATOMIC)
========================================================= */

document.addEventListener('DOMContentLoaded', () => {

    console.log('mcq.js initialized (final)');

    const questionsContainer = document.getElementById('questionsContainer');
    const addQuestionBtn     = document.getElementById('addQuestionBtn');
    const saveBtn            = document.getElementById('saveTestBtn');

    if (!questionsContainer || !addQuestionBtn || !saveBtn) {
        console.warn('MCQ builder elements missing');
        return;
    }

    let isSaving = false;

    /* =========================
       CLEAR SESSION ON LEAVE
    ========================= */
    function clearQuestionSession() {
       navigator.sendBeacon('/teacher/tests/session/clear');
    }

    window.addEventListener('beforeunload', () => {
        if (!isSaving) {
            clearQuestionSession();
        }
    });

    /* =========================
       ADD QUESTION
    ========================= */
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

                <button type="button" class="ai-gen-btn" title="Generate questions with AI">✨ AI</button>

                <div class="qb-meta-right">
                    Keyword
                    <input type="text" class="keyword-input">
                </div>
            </div>

            <div class="question-radio-input">
                <div class="question-input-row">
                    <span class="question-number"></span>
                    <input type="text" class="question-input" placeholder="Question">
                </div>

                <div class="options-container">
                    ${renderOption(uid, 1)}
                    ${renderOption(uid, 2)}
                    ${renderOption(uid, 3)}
                    ${renderOption(uid, 4)}
                </div>

                <!-- Explanation (hidden by default) -->
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
            </div>

                <div class="option-actions">

                    <div class="action-item action-narrow">
                        <button class="add-option-btn" title="Add option">🔢</button>
                    </div>

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
        return card;
    }

    function renderOption(uid, number) {
        return `
            <div class="option-row">
                <div class="option-marker">
                    <input type="radio" name="correct_${uid}">
                </div>
                <input type="text" class="option-input" placeholder="Option ${number}">
                <div class="option-tools">
                    <button type="button" class="tool-btn delete-option">✕</button>
                </div>
            </div>
        `;
    }

    /* =========================
       CARD BUTTONS
    ========================= */
    function bindCardButtons(card) {

        const addOptionBtn = card.querySelector('.add-option-btn');
        const deleteQuestionBtn = card.querySelector('.delete-question-btn');
        const showExplanationBtn = card.querySelector('.show-explanation-btn');
        const explanationBox = card.querySelector('.explanation-container');
        const removeExplanationBtn = card.querySelector('.remove-explanation-btn');

        if (addOptionBtn) {
            addOptionBtn.addEventListener('click', () => {
                const uid = card.dataset.uid;
                const count = card.querySelectorAll('.option-row').length + 1;

                card.querySelector('.options-container')
                    .insertAdjacentHTML('beforeend', renderOption(uid, count));

                bindDeleteOption(card);
            });
        }

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

        bindDeleteOption(card);
    }

    function bindDeleteOption(card) {
        card.querySelectorAll('.delete-option').forEach(btn => {
            btn.onclick = () => btn.closest('.option-row').remove();
        });
    }

    function renumberQuestions() {
        document.querySelectorAll('.question-card').forEach((card, i) => {
            const num = card.querySelector('.question-number');
            if (num) num.textContent = i + 1;
        });
    }

    /* =========================
       SAVE MCQ (BATCH)
    ========================= */
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
            const explanation   = card.querySelector('.explanation-input')?.value?.trim() || null;

            if (!question_text) return;

            const choices = [];
            let correct_index = null;

            card.querySelectorAll('.option-row').forEach(row => {
                const text  = row.querySelector('.option-input')?.value?.trim();
                const radio = row.querySelector('input[type="radio"]');

                if (text) {
                    if (radio?.checked) correct_index = choices.length;
                    choices.push(text);
                }
            });

            if (choices.length >= 2 && correct_index !== null) {
                questions.push({
                    question_text,
                    keyword,
                    difficulty,
                    explanation,
                    choices,
                    correct_index
                });
            }
        });

        if (!questions.length) {
            alert('No valid questions.');
            return;
        }

        isSaving = true;

        try {
            const res = await fetch('/teacher/tests/mcq/save', {
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

    /* =========================
       AI GENERATE
    ========================= */
    const aiModal     = document.getElementById('aiModal');
    const aiCreateBtn = document.getElementById('aiCreate');
    const aiCancelBtn = document.getElementById('aiCancel');
    const aiCloseBtn  = document.getElementById('aiClose');
    const aiError     = document.getElementById('aiError');

    function openAiModal() {
        if (!aiModal) return;
        if (aiError) { aiError.hidden = true; aiError.textContent = ''; }
        aiModal.hidden = false;
    }
    function closeAiModal() { if (aiModal) aiModal.hidden = true; }
    function showAiError(msg) {
        if (!aiError) { alert(msg); return; }
        aiError.textContent = msg;
        aiError.hidden = false;
    }

    // The ✨ AI button lives inside each card's meta bar — delegate the click.
    questionsContainer.addEventListener('click', (e) => {
        if (e.target.closest('.ai-gen-btn')) { e.preventDefault(); openAiModal(); }
    });
    if (aiCancelBtn) aiCancelBtn.addEventListener('click', closeAiModal);
    if (aiCloseBtn)  aiCloseBtn.addEventListener('click', closeAiModal);
    if (aiModal)     aiModal.addEventListener('click', (e) => { if (e.target === aiModal) closeAiModal(); });

    if (aiCreateBtn) {
        aiCreateBtn.addEventListener('click', async () => {
            const nq = parseInt(document.getElementById('aiNumQuestions').value, 10);
            const nc = parseInt(document.getElementById('aiNumChoices').value, 10);
            if (!(nq >= 1 && nq <= 20) || !(nc >= 2 && nc <= 6)) {
                showAiError('Enter 1–20 questions and 2–6 choices.');
                return;
            }

            const label = aiCreateBtn.textContent;
            aiCreateBtn.disabled = true;
            aiCreateBtn.textContent = 'Generating…';
            if (aiError) aiError.hidden = true;

            try {
                const res = await fetch('/teacher/tests/mcq/ai-generate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ num_questions: nq, num_choices: nc })
                });
                const data = await res.json();

                if (res.ok && data.success && Array.isArray(data.questions)) {
                    questionsContainer.innerHTML = '';
                    data.questions.forEach(fillNewCard);
                    renumberQuestions();
                    closeAiModal();
                } else {
                    showAiError(data.error || 'Generation failed.');
                }
            } catch (err) {
                console.error(err);
                showAiError('Server error while generating.');
            } finally {
                aiCreateBtn.disabled = false;
                aiCreateBtn.textContent = label;
            }
        });
    }

    // Build a card from an AI-generated question and populate every field.
    function fillNewCard(q) {
        const card = addQuestionCard();

        const diff = card.querySelector('.difficulty-select');
        if (diff) diff.value = (q.difficulty === 'advanced') ? 'advanced' : 'average';

        const qi = card.querySelector('.question-input');
        if (qi) qi.value = q.question_text || '';

        const choices = Array.isArray(q.choices) ? q.choices : [];
        const container = card.querySelector('.options-container');
        const uid = card.dataset.uid;
        if (container && choices.length) {
            container.innerHTML = choices.map((_, i) => renderOption(uid, i + 1)).join('');
            container.querySelectorAll('.option-row').forEach((row, i) => {
                const input = row.querySelector('.option-input');
                if (input) input.value = choices[i] || '';
                if (i === q.correct_index) {
                    const radio = row.querySelector('input[type="radio"]');
                    if (radio) radio.checked = true;
                }
            });
            bindDeleteOption(card);
        }

        if (q.explanation) {
            const box = card.querySelector('.explanation-container');
            const showBtn = card.querySelector('.show-explanation-btn');
            const input = card.querySelector('.explanation-input');
            if (box) box.classList.remove('hidden');
            if (input) input.value = q.explanation;
            if (showBtn) showBtn.style.display = 'none';
        }

        return card;
    }

    // Start with one question
    if (questionsContainer && questionsContainer.children.length === 0) {
    addQuestionCard();
}
    

});