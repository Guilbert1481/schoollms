// Robust, attribute-based question_settings capture

function captureQuestionSettings() {
    const tbody = document.getElementById('settingsSourceCell');
    if (!tbody) return [];
    const rows = tbody.querySelectorAll('tr');
    const settings = [];
    rows.forEach(row => {
        const getVal = (type) => parseInt(row.querySelector(`input[data-type="${type}"]`)?.value || 0);
        const setting = {
            source_type: row.dataset.sourceType,
            source_id: parseInt(row.dataset.sourceId),
            mcq: getVal('mcq'),
            tf: getVal('tf'),
            mtf: getVal('mtf'),
            id: getVal('id'),
            match: getVal('match'),
            fib: getVal('fib'),
            enum: getVal('enum'),
            essay: getVal('essay')
        };
        const total = setting.mcq + setting.tf + setting.mtf + setting.id +
            setting.match + setting.fib + setting.enum + setting.essay;
        if (total > 0) settings.push(setting);
    });
    return settings;
}

// Save button logic
document.addEventListener('DOMContentLoaded', () => {
    const saveBtn = document.getElementById('saveTestBtn');
    if (!saveBtn) {
        console.error('❌ saveTestBtn not found in DOM');
        return;
    }

    saveBtn.addEventListener('click', async (e) => {
        e.preventDefault();

        const selectedSections = Array.from(document.querySelectorAll('input[name="sections[]"]:checked')).map(cb => cb.value);
        const questionSettings = captureQuestionSettings();

        if (!questionSettings || questionSettings.length === 0) {
            alert('⚠️ Please render availability and enter question counts first!');
            return;
        }

        const payload = {
            test_id: document.getElementById('testId')?.value || null,
            subject_id: document.getElementById('cd-subject')?.value || null,
            class_id: document.getElementById('class_id')?.value || null,
            topic_id: document.getElementById('cd-topic')?.value || null,
            lesson_id: document.getElementById('cd-lesson')?.value || null,
            competency_id: document.getElementById('cd-competency')?.value || null,
            difficulty: Array.from(document.querySelectorAll('input[name="difficulty[]"]:checked')).map(cb => cb.value),
            academic_levels: Array.from(document.querySelectorAll('input[name="academic_levels[]"]:checked')).map(cb => cb.value),
            assessment_type: document.getElementById('assessment-type-select')?.value || null,
            mode: document.getElementById('mode-select')?.value || null,
            term: document.getElementById('term-select')?.value || null,
            timer_minutes: document.getElementById('time-limit')?.value || null,
            attempts_allowed: document.getElementById('attempts-allowed')?.value || null,
            passing_score: document.getElementById('passingScore')?.value || null,
            start_at: document.getElementById('start-datetime')?.value || null,
            end_at: document.getElementById('end-datetime')?.value || null,
            shuffle_questions: document.getElementById('shuffle-questions')?.checked ? 1 : 0,
            shuffle_mcq_choices: document.getElementById('shuffle-mcq-choices')?.checked ? 1 : 0,
            show_results: document.getElementById('show-results')?.value || null,
            show_correct_answers: document.getElementById('show-correct-answers')?.value || 'after_exam',
            sections: selectedSections,
            question_settings: questionSettings
        };

        if (!payload.subject_id) {
            alert('⚠️ Please select a subject!');
            return;
        }
        if (!payload.difficulty) {
            alert('⚠️ Please select a difficulty level!');
            return;
        }
        if (!payload.academic_levels || payload.academic_levels.length === 0) {
            alert('⚠️ Please select an academic level!');
            return;
        }

        try {
            const tokenMeta = document.querySelector('meta[name="csrf-token"]');
            if (!tokenMeta) {
                alert('CSRF token not found.');
                return;
            }
            const token = tokenMeta.getAttribute('content');
            const res = await fetch('/teacher/tests/builder/save', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json'
                },
                credentials: 'include',
                body: JSON.stringify(payload)
            });

            const rawText = await res.text();
            let data;
            try { data = JSON.parse(rawText); }
            catch { throw new Error('Response is not valid JSON'); }

            if (!res.ok || !data.success) {
                alert(data.message || 'Save failed');
                return;
            }
            if (data.test_id && document.getElementById('testId')) {
                document.getElementById('testId').value = data.test_id;
            }
            alert('✅ Test saved successfully!');
            const saveBtn = document.getElementById('saveTestBtn');
            const printBtn = document.getElementById('printTestBtn');
            const editBtn = document.getElementById('editTestBtn');
            const answerBtn = document.getElementById('answerSheetsBtn');
            if (saveBtn) saveBtn.style.display = 'none';
            if (printBtn) printBtn.style.display = 'inline-block';
            if (editBtn) editBtn.style.display = 'inline-block';
            if (answerBtn) answerBtn.style.display = 'inline-block';
        } catch (err) {
            alert('Something went wrong while saving: ' + err.message);
        }
    });
});