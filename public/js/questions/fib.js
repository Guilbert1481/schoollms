document.addEventListener('DOMContentLoaded', () => {
    const questionsContainer = document.getElementById('questionsContainer');
    const addQuestionBtn     = document.getElementById('addQuestionBtn');
    const saveBtn            = document.getElementById('saveTestBtn');
    let isSaving = false;

    if (!questionsContainer || !addQuestionBtn || !saveBtn) {
        console.warn('FIB builder elements missing');
        return;
    }

    addQuestionBtn.addEventListener('click', addQuestionCard);

    function addQuestionCard() {
        const uid = Date.now() + Math.floor(Math.random() * 1000);

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
                    <input type="text" class="keyword-input" placeholder="Keyword">
                </div>
            </div>

            <div class="question-input-row">
                <span class="question-number"></span>
                <div style="display:flex;align-items:center; gap: 8px; width:100%">
                    <input type="text" class="question-input" placeholder="Type your fill-in-the-blank question (use Insert Blank)" style="flex:1;">
                    <button type="button" class="insert-blank-btn tb-btn mini" title="Insert a blank at the cursor">Insert Blank</button>
                </div>
            </div>

            <div class="answers-row"></div>

            <div class="fib-preview" style="margin-top:10px;color:#6366f1;font-style:italic;"></div>

            <div class="explanation-container hidden">
                <label class="explanation-label">Explanation (optional)</label>
                <div class="explanation-row">
                    <textarea class="explanation-input" placeholder="Add explanation or rationale (optional)"></textarea>
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

        // FIB-specific logic
        const questionInput = card.querySelector('.question-input');
        const insertBlankBtn = card.querySelector('.insert-blank-btn');
        const answersRow = card.querySelector('.answers-row');
        const previewDiv = card.querySelector('.fib-preview');

        // Handler for "Insert Blank"
        insertBlankBtn.addEventListener('click', () => {
            const pos = questionInput.selectionStart || 0;
            const before = questionInput.value.slice(0, pos);
            const after = questionInput.value.slice(pos);
            questionInput.value = before + '{{blank}}' + after;
            questionInput.focus();
            // Move cursor after new blank
            setTimeout(() => {
                questionInput.setSelectionRange(pos + 8, pos + 8);
            }, 0);
            updateBlanksAndPreview();
        });

        // Dynamic answers and preview
        questionInput.addEventListener('input', updateBlanksAndPreview);

        function updateBlanksAndPreview() {
            const blanks = (questionInput.value.match(/{{blank}}/g) || []).length;
            answersRow.innerHTML = '';
            for (let i = 0; i < blanks; i++) {
                answersRow.innerHTML += `
                    <div class="answer-input-row" style="margin-bottom:12px;">
                        <label class="answer-label" style="font-weight:600; color:#6366f1;">Answer for Blank ${i + 1}</label>
                        <input type="text" 
                            class="fib-answer-input" 
                            data-blank-index="${i}" 
                            placeholder="Correct answer for blank ${i + 1}" 
                            required 
                            style="
                                min-width:220px;
                                max-width:100%;
                                padding:8px 14px;
                                border:1.5px solid #bdbdbd;
                                border-radius:7px;
                                margin:6px 0 13px 0;
                                background:#fff;
                                font-size:1rem;" 
                        >
                    </div>
                `;
            }
            // Live preview: replace {{blank}} with ____
            let html = questionInput.value
                .replace(/</g, "&lt;")
                .replace(/{{blank}}/g, '<span style="border-bottom:1.5px solid #aaa; padding:0 16px;">&nbsp;</span>');
            previewDiv.innerHTML = `<span style="color:#6366f1;">Preview: </span>` + html;
        }

        // Card-specific buttons (delete, explanation)
        bindCardButtons(card);
        renumberQuestions();

        // Initialize
        updateBlanksAndPreview();
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

    // ========== SAVE BUTTON HANDLER ==========

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
            const keyword = card.querySelector('.keyword-input')?.value?.trim() || null;
            const difficulty = card.querySelector('.difficulty-select')?.value || 'average';
            const explanation = card.querySelector('.explanation-input')?.value?.trim() || null;
            const answerInputs = card.querySelectorAll('.fib-answer-input');
            const answers = [];
            answerInputs.forEach(input => {
                if (input.value.trim() !== '') {
                    answers.push(input.value.trim());
                }
            });

            // Require at least 1 blank and all answers filled
            const blankCount = (question_text.match(/{{blank}}/g) || []).length;
            if (
                !question_text || 
                (blankCount === 0) ||
                (answers.length !== blankCount) ||
                answers.some(ans => !ans)
            ) return;

            questions.push({
                question_text,
                keyword,
                difficulty,
                explanation,
                answers
            });
        });

        if (!questions.length) {
            alert('No valid questions: check that every question has blanks and all blanks have answers.');
            return;
        }

        isSaving = true;

        try {
            // CHANGE THIS URL to your save endpoint as needed!
            const res = await fetch('/teacher/tests/fib/save', {
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

    // Always show one question card by default if empty
    if (questionsContainer.children.length === 0) {
        addQuestionCard();
    }
});