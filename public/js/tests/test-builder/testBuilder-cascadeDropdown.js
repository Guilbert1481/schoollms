/**
 * cascading-dropdown.js
 * ==================================================
 * PURPOSE: Match dropdowns to rendered availability table
 * --------------------------------------------------
 * - Subject → Topic → Lesson → Competency
 * - Populates Topic (and Lesson) dropdowns with the same list shown in the availability (Question Settings) table.
 * - MUST CALL after Render Availability!
 * ==================================================
 */

document.addEventListener('DOMContentLoaded', () => {

    const subjectSelect    = document.getElementById('cd-subject');
    const topicSelect      = document.getElementById('cd-topic');
    const lessonSelect     = document.getElementById('cd-lesson');
    const competencySelect = document.getElementById('cd-competency');
    const renderBtn        = document.querySelector('#render-availability-btn') || document.querySelector('button'); // Adjust selector as needed

    if (!subjectSelect || !topicSelect || !lessonSelect) {
        console.warn('Dropdown elements missing.');
        return;
    }

    function reset(select, label) {
        select.innerHTML = `<option value="">${label}</option>`;
        select.disabled = true;
    }

    // This function should be called with the data returned by your "Render Availability" endpoint.
    function populateTopicsFromAvailability(availability) {
        // Filter to "topic" type only, or adjust to your real key
        const topics = availability.filter(item => item.source_type === 'topic');
        topicSelect.innerHTML = '<option value="">Select Topic</option>';
        topics.forEach(t => {
            topicSelect.innerHTML += `<option value="${t.source_id}">${t.source}</option>`;
        });
        topicSelect.disabled = false;
    }

    // Same for lessons, as needed!
    function populateLessonsFromAvailability(availability) {
        // Filter to "lesson" type only, or adjust to your real key
        const lessons = availability.filter(item => item.source_type === 'lesson');
        lessonSelect.innerHTML = '<option value="">Select Lesson</option>';
        lessons.forEach(l => {
            lessonSelect.innerHTML += `<option value="${l.source_id}">${l.source}</option>`;
        });
        lessonSelect.disabled = false;
    }

    // When the subject changes, clear everything (topics will not auto-load).
    subjectSelect.addEventListener('change', () => {
        reset(topicSelect, 'Select Topic');
        reset(lessonSelect, 'Select Lesson');
        if (competencySelect) reset(competencySelect, 'Select Competency');
    });

    // REPURPOSE: When "Render Availability" is clicked, call your backend to get availability,
    // THEN call `populateTopicsFromAvailability()` with that data.
    if (renderBtn) {
        renderBtn.addEventListener('click', async () => {
            const subjectId = subjectSelect.value;
            // Gather other filters as needed (difficulty, academic levels, etc.)
            // Example simple fetch; adjust based on your backend requirements:
            try {
                const res = await fetch(`/teacher/tests/availability?subject_id=${subjectId}`);
                const availability = await res.json();

                // Populate both the on-page availability table and the dropdown here!
                populateTopicsFromAvailability(availability);

                // If you want Lesson dropdowns to match, call:
                // populateLessonsFromAvailability(availability);
            } catch (err) {
                topicSelect.innerHTML = '<option value="">Error loading topics</option>';
            }
        });
    }

    // Rest is unchanged (competency still loads as before by lesson)
    lessonSelect.addEventListener('change', async () => {
        const lessonId = lessonSelect.value;
        if (!competencySelect) return;
        reset(competencySelect, 'Select Competency');
        if (!lessonId) return;
        try {
            const res = await fetch(`/teacher/tests/competencies?lesson_id=${lessonId}`);
            const competencies = await res.json();
            competencies.forEach(c => {
                competencySelect.innerHTML += `<option value="${c}">${c}</option>`;
            });
            competencySelect.disabled = false;
        } catch (err) {
            competencySelect.innerHTML = '<option value="">Error loading competencies</option>';
        }
    });

});