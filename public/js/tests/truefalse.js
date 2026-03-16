/* ===============================
   TRUE / FALSE MODULE
=============================== */
window.collectTrueFalse = function(card) {
    return card.querySelector('.answer-text')?.value || '';
}

window.TestTypes = window.TestTypes || {};

window.TestTypes.true_false = {

    label: "True / False",

    render(card, uid) {

        const container = card.querySelector('.options-container');

        container.innerHTML = `
            <div class="option-row">
                <div class="option-marker">
                    <input type="radio" name="correct_${uid}" value="true">
                </div>
                <label>True</label>
            </div>

            <div class="option-row">
                <div class="option-marker">
                    <input type="radio" name="correct_${uid}" value="false">
                </div>
                <label>False</label>
            </div>
        `;

        card.querySelector('.add-option-btn').style.display = 'none';
    },

    collect(card) {

        let correctIndex = null;

        card.querySelectorAll('input[type="radio"]').forEach((r, i) => {
            if (r.checked) correctIndex = i;
        });

        return { options: ['True', 'False'], correctIndex };
    }
};


/* =========================================================
   TRUE / FALSE RENDERER MODULE
   ========================================================= */

window.renderTrueFalse = function (sectionId) {

    const qId = Date.now();

    return `
    <div class="question-card" data-section="${sectionId}" data-type="true_false" data-qid="${qId}">
        
        <div class="question-header">
            <span class="question-number">•</span>
            <strong>True / False Question</strong>
        </div>

        <div class="question-input-row">
            <input type="text" class="question-input" placeholder="Enter the statement here">
        </div>

        <div class="options-container">
            <label><input type="radio" name="correct_${qId}" value="true"> True</label>
            <label><input type="radio" name="correct_${qId}" value="false"> False</label>
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

registerTestType('true_false', {
    render(uid) {
        return `
            <div class="tf-options">
            </div>
        `;
    }
});





function trueFalseTemplate(uid) {
    return `
        <div class="option-row">
            <div class="option-marker">
                <input type="radio" name="correct_${uid}" value="true">
            </div>
            <label>True</label>
        </div>
        <div class="option-row">
            <div class="option-marker">
                <input type="radio" name="correct_${uid}" value="false">
            </div>
            <label>False</label>
        </div>
    `;
}


