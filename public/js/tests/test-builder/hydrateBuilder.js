/**
 * Edit-mode hydration for the Test Builder.
 *
 * The builder is a JS-driven form: subjects load only after levels are picked,
 * topics/lessons only after "Render Availability". On the edit page the server
 * pre-checks the Assessment Levels and testBuilder.js re-selects the saved
 * subject (data-selected) — this script restores everything else from
 * window.EDIT_TEST (emitted by TestBuilderController@edit):
 *
 *   1. difficulty checkboxes (before any render — they filter availability),
 *   2. class / term / grade component / mode / assessment type + the online
 *      settings block (mode change re-triggers modeVisibility.js),
 *   3. the availability table: auto-render once the subject lands, drill into
 *      the saved topic when the sources are lesson-level, then fill each
 *      row's count inputs from the saved test_sources.
 */
(function () {
    const data = window.EDIT_TEST;
    if (!data || !data.test) return;

    function setValue(id, value, fireChange = false) {
        const el = document.getElementById(id);
        if (!el || value === null || value === undefined || value === '') return;
        el.value = String(value);
        if (fireChange) el.dispatchEvent(new Event('change'));
    }

    function setChecked(id, on) {
        const el = document.getElementById(id);
        if (el) el.checked = !!on;
    }

    document.addEventListener('DOMContentLoaded', () => {
        const test = data.test;
        const settings = data.settings || {};
        const sources = Array.isArray(data.sources) ? data.sources : [];

        // 1) Difficulty — restored first: it is an availability filter.
        (test.difficulty || []).forEach((v) => {
            const cb = document.querySelector(`input[name="difficulty[]"][value="${CSS.escape(String(v))}"]`);
            if (cb) cb.checked = true;
        });

        // 2) Assignment & classification + online-only settings. Mode fires a
        // change so modeVisibility.js reveals the settings block when online.
        setValue('term-select', settings.term);
        setValue('grade-component-select', test.grade_component_id);
        setValue('assessment-type-select', settings.assessment_type);
        setValue('time-limit', settings.timer_minutes);
        setValue('attempts-allowed', settings.attempts_allowed);
        setValue('passingScore', settings.passing_score);
        setValue('start-datetime', settings.start_at);
        setValue('end-datetime', settings.end_at);
        setValue('show-results', settings.show_results);
        setValue('show-correct-answers', settings.show_correct_answers);
        setChecked('shuffle-questions', settings.shuffle_questions);
        setChecked('shuffle-mcq-choices', settings.shuffle_mcq_choices);
        setValue('mode-select', settings.mode, true);

        // A saved test can be previewed/printed straight away.
        const previewBtn = document.getElementById('printHubBtn');
        if (previewBtn) previewBtn.style.display = 'inline-block';

        // 3) Availability table + saved counts.
        const subjectSel = document.getElementById('cd-subject');
        const topicSel = document.getElementById('cd-topic');
        const lessonSel = document.getElementById('cd-lesson');
        const renderBtn = document.getElementById('renderAvailabilityBtn');
        const tbody = document.getElementById('settingsSourceCell');
        if (!subjectSel || !renderBtn || !tbody || !sources.length) return;

        // Each render lists one drill level (topics → lessons → competencies)
        // and populates the next dropdown, so restoring a deeper drill takes
        // one render per remaining step: select, re-render, repeat, then fill.
        const drillSteps = [];
        if (test.topic_id) drillSteps.push({ select: topicSel, value: test.topic_id });
        if (test.topic_id && test.lesson_id) drillSteps.push({ select: lessonSel, value: test.lesson_id });

        let waitingForSubject = true;
        let done = false;

        subjectSel.addEventListener('change', () => {
            if (!waitingForSubject) return;
            if (String(subjectSel.value) !== String(test.subject_id)) return;
            waitingForSubject = false;
            // Class options are filtered by subject + level (testBuilder.js),
            // so the saved class only survives if restored after both are set.
            setValue('class_id', test.class_id);
            renderBtn.click();
        });

        const observer = new MutationObserver(() => {
            if (waitingForSubject || done) return;
            const rows = tbody.querySelectorAll('tr[data-source-id]');
            if (!rows.length) return; // "Loading…" / empty-state row

            const step = drillSteps.shift();
            if (step) {
                if (step.select) step.select.value = String(step.value);
                renderBtn.click();
                return;
            }

            done = true;
            observer.disconnect();
            fillCounts(rows);
        });
        observer.observe(tbody, { childList: true, subtree: true });

        function fillCounts(rows) {
            const bySource = new Map(sources.map((s) => [`${s.source_type}:${s.source_id}`, s]));
            const types = ['mcq', 'tf', 'mtf', 'id', 'match', 'fib', 'enum', 'essay'];

            rows.forEach((row) => {
                const saved = bySource.get(`${row.dataset.sourceType}:${row.dataset.sourceId}`);
                if (!saved) return;

                types.forEach((type) => {
                    const input = row.querySelector(`input[data-type="${type}"]:not([readonly])`);
                    if (!input || !(saved[type] > 0)) return;
                    // The bank may have shrunk since the save — never exceed max.
                    const max = parseInt(input.dataset.max) || 0;
                    input.value = Math.min(saved[type], max);
                });
            });
        }
    });
})();
