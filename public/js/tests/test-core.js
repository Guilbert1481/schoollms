/* =========================================================
   SECTION ENGINE – CORE BRAIN (FIXED & PRODUCTION READY)
   ========================================================= */

window.TestBuilder = {
    sections: [],
    usedTypes: [],
    activeSectionIndex: null
};

/* =========================================================
   ADD SECTION BUTTON
   ========================================================= */

document.addEventListener('DOMContentLoaded', () => {
    const addSectionBtn = document.getElementById('addSectionBtn');
    if (addSectionBtn) addSectionBtn.addEventListener('click', openSectionModal);

/* =========================================================
   OPEN SECTION MODAL
   ========================================================= */

function openSectionModal() {
    const types = [
        { id: 'mcq', label: 'Multiple Choice' },
        { id: 'true_false', label: 'True / False' },
        { id: 'modified_tf', label: 'Modified True / False' },
        { id: 'identification', label: 'Identification' },
        { id: 'fill_blank', label: 'Fill in the Blanks' },
        { id: 'matching', label: 'Matching Type' },
        { id: 'essay', label: 'Essay' }
    ];

    const available = types.filter(t => !TestBuilder.usedTypes.includes(t.id));
    if (!available.length) return alert('All test types already used.');

    const options = available.map(t => `<option value="${t.id}">${t.label}</option>`).join('');

    document.body.insertAdjacentHTML('beforeend', `
        <div class="section-modal-overlay" id="sectionModal">
            <div class="section-modal">
                <h3>Create New Section</h3>
                <select id="sectionTypeSelect">${options}</select>
                <div class="section-modal-actions">
                    <button onclick="closeSectionModal()">Cancel</button>
                    <button onclick="createSection()">Create Section</button>
                </div>
            </div>
        </div>
    `);
}

function closeSectionModal(){ document.getElementById('sectionModal')?.remove(); }

function createSection(){
    const type = document.getElementById('sectionTypeSelect').value;
    const labelMap = { mcq:'Multiple Choice', true_false:'True / False', modified_tf:'Modified True / False', identification:'Identification', fill_blank:'Fill in the Blanks', matching:'Matching Type', essay:'Essay' };

    const section = { id: Date.now(), type, label: labelMap[type], questions: [] };
    TestBuilder.sections.push(section);
    TestBuilder.usedTypes.push(type);
    renderSections();
    closeSectionModal();
}

/* =========================================================
   RENDER SECTIONS
   ========================================================= */

function renderSections(){
    const container = document.getElementById('questionsContainer');
    container.innerHTML = '';

    TestBuilder.sections.forEach((section, i) => {
        container.insertAdjacentHTML('beforeend', `
            <div class="section-block">
                <h3>Section ${i+1}: ${section.label}</h3>
                <div id="section-${section.id}" class="section-questions"></div>
                <button onclick="addQuestionToSection(${i})">+ Add Question</button>
            </div>
        `);
    });
}

function addQuestionToSection(i){
    const s = TestBuilder.sections[i];
    if (window.renderQuestionByType) renderQuestionByType(s.type, s.id);
}

/* =========================================================
   SAVE ENGINE (MATCHES saveAll CONTROLLER)
   ========================================================= */

function saveTest(){
    const payload = {
        subject_id: document.querySelector('[name="subject_id"]')?.value,
        topic_id: document.querySelector('[name="topic_id"]')?.value,
        title: document.querySelector('[name="test_title"]')?.value,
        description: document.querySelector('[name="description"]')?.value,
        questions: []
    };


    if(!payload.subject_id || !payload.topic_id || !payload.title){
        alert('Complete Test Information first'); return;
    }

    document.querySelectorAll('.question-card').forEach((card, i) => {
        const qText = card.querySelector('.question-text')?.value;
        if(!qText) return;

        const keyword = card.querySelector('.keyword-input')?.value || '';
        const points = card.querySelector('.question-points')?.value || 1;
        const choices = [];
        let correctIndex = 0;

        card.querySelectorAll('.choice-row').forEach((row, idx) => {
            const t = row.querySelector('.choice-text')?.value || '';
            const c = row.querySelector('.choice-correct')?.checked;
            if(t){ choices.push(t); if(c) correctIndex = idx; }
        });

        payload.questions.push({
            keyword, question_text: qText, order: i+1, points, choices, correct_index: correctIndex
        });
    });

    console.log('POSTING TO:', window.saveTestUrl);
    console.log('PAYLOAD:', payload);

    fetch(window.saveTestUrl,{
        method:'POST',
        headers:{'Content-Type':'application/json','X-CSRF-TOKEN':window.csrfToken},
        body: JSON.stringify(payload)
    })
    .then(r=>r.json())
    .then(d=> d.success || d.status==='success' ? alert('Test saved successfully!') : alert('Save failed'))
    .catch(e=>{ console.error(e); alert('Save failed – check console'); });
}
 });