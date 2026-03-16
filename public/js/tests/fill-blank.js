/* ===============================
   FILL IN THE BLANKS MODULE
=============================== */

window.TestTypes = window.TestTypes || {};

window.TestTypes.fill_blank = {

    label: "Fill in the Blanks",

    render(card) {

        const container = card.querySelector('.options-container');

        container.innerHTML = `
            <input type="text" class="option-input" placeholder="Correct Word / Phrase">
        `;

        card.querySelector('.add-option-btn').style.display = 'none';
    },

    collect(card) {

        const answer = card.querySelector('.option-input').value.trim();
        return { options: [answer], correctIndex: 0 };
    }
};


/* =========================================================
   FILL IN THE BLANKS RENDERER MODULE
   ========================================================= */

window.renderFillBlank = function (sectionId) {

    const qId = Date.now();

    return `
    <div class="question-card" data-section="${sectionId}" data-type="fill_blank" data-qid="${qId}">
        
        <div class="question-header">
            <span class="question-number">•</span>
            <strong>Fill in the Blanks Question</strong>
        </div>

        <div class="question-input-row">
            <input type="text" class="question-input" placeholder="Enter the sentence with a blank (e.g., The capital of France is ____)">
        </div>

        <div class="options-container">
            <input type="text" class="option-input" placeholder="Correct Word / Phrase">
        </div>
    </div>`;
};

(function waitForRouter(){
    if (window.registerTestType) {
        registerTestType('mcq', {
            render(uid) {
                return `
                    <div class="option-row">
                        <input type="radio" name="correct_${uid}">
                        <input type="text" class="option-input" placeholder="Option 1">
                    </div>
                    <div class="option-row">
                        <input type="radio" name="correct_${uid}">
                        <input type="text" class="option-input" placeholder="Option 2">
                    </div>
                `;
            }
        });
    } else {
        setTimeout(waitForRouter, 50);
    }
})();



