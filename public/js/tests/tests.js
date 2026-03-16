document.addEventListener('DOMContentLoaded', function () {

    console.log('tests.js (dashboard only) loaded');

    const subjectSelect = document.getElementById('subjectSelect');
    const topicSelect   = document.getElementById('topicSelect');

    // Used in dashboard modals (Add Topic)
    if (!subjectSelect || !topicSelect) return;

    subjectSelect.addEventListener('change', async function () {

        const subjectId = this.value;
        topicSelect.innerHTML = `<option value="">Loading topics...</option>`;

        if (!subjectId) {
            topicSelect.innerHTML = `<option value="">Select Topic</option>`;
            return;
        }

        try {
            const res = await fetch(`/teacher/subjects/${subjectId}/topics`);
            const topics = await res.json();

            topicSelect.innerHTML = `<option value="">Select Topic</option>`;

            topics.forEach(topic => {
                const opt = document.createElement('option');
                opt.value = topic.id;
                opt.textContent = topic.name;
                topicSelect.appendChild(opt);
            });

        } catch (err) {
            console.error('Failed to load topics', err);
            topicSelect.innerHTML = `<option value="">Error loading topics</option>`;
        }
    });

});
