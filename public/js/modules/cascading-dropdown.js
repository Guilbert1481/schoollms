/**
 * template-cascadeDropdown.js
 * ==================================================
 * PURPOSE: Reusable cascading dropdown module
 * --------------------------------------------------
 * Flow:
 *   Subject → Topic → Lesson → Competency
 *
 * RULES:
 * - No page guards
 * - No forced disabling beyond cascade logic
 * - Safe for Metadata and other template pages
 * ==================================================
 */

document.addEventListener('DOMContentLoaded', () => {

    console.log('TEMPLATE CASCADING DROPDOWN LOADED');

    const subjectSelect    = document.getElementById('tpl-subject');
    const topicSelect      = document.getElementById('tpl-topic');
    const lessonSelect     = document.getElementById('tpl-lesson');
    const competencySelect = document.getElementById('tpl-competency');

    if (!subjectSelect || !topicSelect || !lessonSelect || !competencySelect) {
        console.warn('Template cascade: required elements not found');
        return;
    }

    /* =========================
       INITIAL STATE
    ========================= */
    reset(topicSelect, 'Select Topic');
    reset(lessonSelect, 'Select Lesson');
    reset(competencySelect, 'Select Competency');

    /* =========================
       SUBJECT → TOPIC
    ========================= */
    subjectSelect.addEventListener('change', async () => {

        const subjectId = subjectSelect.value;

        reset(topicSelect, 'Select Topic');
        reset(lessonSelect, 'Select Lesson');
        reset(competencySelect, 'Select Competency');

        if (!subjectId) return;

        try {
            const res = await fetch(`/api/subjects/${subjectId}/topics`, {
                headers: { 
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest' // Helps Laravel identify it as an AJAX call
                }
            });

            if (!res.ok) throw new Error(res.status);

            const topics = await res.json();
            populate(topicSelect, topics);
            topicSelect.disabled = false;

        } catch (e) {
            console.error('Template cascade: topic load failed', e);
        }
    });

    /* =========================
       TOPIC → LESSON
    ========================= */
    topicSelect.addEventListener('change', async () => {

        const topicId = topicSelect.value;

        reset(lessonSelect, 'Select Lesson');
        reset(competencySelect, 'Select Competency');

        if (!topicId) return;

        try {
            const res = await fetch(`/teacher/topics/${topicId}/lessons`, {
                headers: { 'Accept': 'application/json' }
            });

            if (!res.ok) throw new Error(res.status);

            const lessons = await res.json();
            populate(lessonSelect, lessons);
            lessonSelect.disabled = false;

        } catch (e) {
            console.error('Template cascade: lesson load failed', e);
        }
    });

    /* =========================
       LESSON → COMPETENCY
    ========================= */
    lessonSelect.addEventListener('change', async () => {

        const lessonId = lessonSelect.value;

        reset(competencySelect, 'Select Competency');

        if (!lessonId) return;

        try {
            const res = await fetch(`/teacher/lessons/${lessonId}/competencies`, {
                headers: { 'Accept': 'application/json' }
            });

            if (!res.ok) throw new Error(res.status);

            const competencies = await res.json();

            if (!competencies.length) {
                competencySelect.innerHTML =
                    `<option value="">No competencies found</option>`;
                competencySelect.disabled = true;
                return;
            }

            populate(competencySelect, competencies);
            competencySelect.disabled = false;

        } catch (e) {
            console.error('Template cascade: competency load failed', e);
        }
    });

    /* =========================
       HELPERS
    ========================= */
    function reset(select, label) {
        select.innerHTML = `<option value="">${label}</option>`;
        select.disabled = true;
    }

    function populate(select, items) {
        items.forEach(item => {
            const opt = document.createElement('option');
            opt.value = item.id;
            opt.textContent = item.name;
            select.appendChild(opt);
        });
    }

});
