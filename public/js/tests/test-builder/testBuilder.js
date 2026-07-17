document.addEventListener('DOMContentLoaded', () => {
    const renderBtn  = document.getElementById('renderAvailabilityBtn');
    const subjectSel = document.getElementById('cd-subject');
    const topicSel   = document.getElementById('cd-topic');
    const lessonSel  = document.getElementById('cd-lesson');
    const tableBody  = document.getElementById('settingsSourceCell');

    // For select/deselect all
    const selectAllBtn = document.getElementById('selectAllSources');
    const deselectAllBtn = document.getElementById('deselectAllSources');
    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', () => {
            const checkboxes = tableBody.querySelectorAll('.source-checkbox');
            checkboxes.forEach(cb => cb.checked = true);
        });
    }
    if (deselectAllBtn) {
        deselectAllBtn.addEventListener('click', () => {
            const checkboxes = tableBody.querySelectorAll('.source-checkbox');
            checkboxes.forEach(cb => cb.checked = false);
        });
    }

    if (!renderBtn || !tableBody) {
        console.error('Test Builder: required elements missing');
        return;
    }

    // =====================
    // LEVEL-GATED SUBJECTS (Option 1)
    // The Assessment Levels picker is the source of truth for the Subject list:
    // Subject stays disabled until a grade/year level is chosen, then it is
    // populated with only the subjects that have questions in the bank at the
    // selected level(s). Keeps the dropdown short instead of dumping the whole
    // subject catalogue.
    // =====================
    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, c =>
            ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    let subjectReqSeq = 0;
    async function refreshSubjectsForLevels() {
        if (!subjectSel) return;

        const levels = Array.from(document.querySelectorAll('input[name="academic_levels[]"]:checked'))
            .map(cb => cb.value);
        const preselect = subjectSel.dataset.selected || '';

        if (!levels.length) {
            subjectSel.innerHTML = '<option value="">Select grade / year level first</option>';
            subjectSel.disabled = true;
            subjectSel.dispatchEvent(new Event('change'));
            return;
        }

        const query = new URLSearchParams();
        levels.forEach(v => query.append('academic_levels[]', v));

        // Guard against out-of-order responses when levels are toggled quickly.
        const seq = ++subjectReqSeq;
        try {
            const res = await fetch(`/teacher/tests/available-subjects?${query}`, {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' },
            });
            if (seq !== subjectReqSeq) return; // a newer request superseded this one
            const subjects = res.ok ? await res.json() : [];
            if (seq !== subjectReqSeq) return;

            if (!Array.isArray(subjects) || !subjects.length) {
                subjectSel.innerHTML = '<option value="">No subjects have questions at this level yet</option>';
                subjectSel.disabled = true;
            } else {
                subjectSel.innerHTML = '<option value="">Select Subject</option>' +
                    subjects.map(s => `<option value="${s.id}">${escapeHtml(s.name)}</option>`).join('');
                subjectSel.disabled = false;
                if (preselect && subjects.some(s => String(s.id) === String(preselect))) {
                    subjectSel.value = String(preselect);
                }
            }
        } catch (e) {
            if (seq !== subjectReqSeq) return;
            subjectSel.innerHTML = '<option value="">Error loading subjects</option>';
            subjectSel.disabled = true;
        }

        // Reset the downstream cascade (topic/lesson/competency) for the new list.
        subjectSel.dispatchEvent(new Event('change'));
    }

    // The cascade picker fires this custom event; the flat-list fallback fires a
    // native change on its academic_levels[] checkboxes.
    document.addEventListener('assessment-levels:changed', refreshSubjectsForLevels);
    document.addEventListener('change', (e) => {
        if (e.target && e.target.name === 'academic_levels[]') refreshSubjectsForLevels();
    });

    // Initial run — covers the edit page where levels are already selected.
    refreshSubjectsForLevels();

    // =====================
    // CLASS DROPDOWN SCOPING
    // Show only the teacher's classes whose subject matches the chosen Test
    // Source subject AND whose section year level matches a selected grade/year.
    // =====================
    function filterClasses() {
        const classSel = document.getElementById('class_id');
        if (!classSel) return;

        const chosenSubject = subjectSel ? subjectSel.value : '';
        const levelYears = window.CLASS_LEVEL_YEARS || {};
        const years = new Set(
            Array.from(document.querySelectorAll('input[name="academic_levels[]"]:checked'))
                .map((cb) => String(levelYears[cb.value]))
                .filter((y) => y !== 'undefined' && y !== 'null' && y !== '')
        );

        Array.from(classSel.options).forEach((opt) => {
            if (!opt.value || opt.value === 'others') return; // always keep "None" / "Others"
            const subjectOk = chosenSubject !== '' && opt.dataset.subjectId === String(chosenSubject);
            const yearOk = years.size === 0 || years.has(String(opt.dataset.yearLevel));
            const show = subjectOk && yearOk;
            opt.hidden = !show;
            opt.disabled = !show;
        });

        // Drop a stale selection that's no longer valid for the current filter.
        const current = classSel.selectedOptions[0];
        if (current && current.value && current.hidden) classSel.value = '';
    }

    document.addEventListener('assessment-levels:changed', filterClasses);
    document.addEventListener('change', (e) => {
        if (e.target && e.target.name === 'academic_levels[]') filterClasses();
    });
    if (subjectSel) subjectSel.addEventListener('change', filterClasses);
    filterClasses(); // initial

    // =====================
    // RENDER AVAILABILITY
    // =====================
    renderBtn.addEventListener('click', async () => {
        tableBody.innerHTML = `<tr><td colspan="9" style="text-align:center; color:#999;">Loading…</td></tr>`;

        const lessonId = lessonSel?.value || '';
        const difficulty = [...document.querySelectorAll('input[name="difficulty[]"]:checked')].map(cb => cb.value);
        const selectedLevels = Array.from(document.querySelectorAll('input[name="academic_levels[]"]:checked')).map(cb => cb.value);

        const params = {
            subject_id: subjectSel?.value || '',
            topic_id: topicSel?.value || '',
            lesson_id: lessonId,
            difficulty: difficulty,
            academic_levels: selectedLevels
        };

        const query = new URLSearchParams();
        Object.entries(params).forEach(([key, value]) => {
            if (Array.isArray(value)) {
                value.forEach(v => query.append(key + '[]', v));
            } else {
                query.append(key, value);
            }
        });

        let res;
        try {
            res = await fetch(`/teacher/tests/availability?${query}`, {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }
            });
        } catch (e) {
            tableBody.innerHTML = `<tr><td colspan="9" style="text-align:center; color:#999;">Network error</td></tr>`;
            // On error, also disable both selects
            if (topicSel) {
                topicSel.innerHTML = `<option value="">Select Topic</option>`;
                topicSel.disabled = true;
            }
            if (lessonSel) {
                lessonSel.innerHTML = `<option value="">Select Lesson</option>`;
                lessonSel.disabled = true;
            }
            return;
        }

        if (!res.ok) {
            tableBody.innerHTML = `<tr><td colspan="9" style="text-align:center; color:#999;">Error loading availability (${res.status})</td></tr>`;
            // On error, also disable both selects
            if (topicSel) {
                topicSel.innerHTML = `<option value="">Select Topic</option>`;
                topicSel.disabled = true;
            }
            if (lessonSel) {
                lessonSel.innerHTML = `<option value="">Select Lesson</option>`;
                lessonSel.disabled = true;
            }
            return;
        }

        const rows = await res.json();
        if (!Array.isArray(rows) || !rows.length) {
            tableBody.innerHTML = `<tr><td colspan="9" style="text-align:center; color:#999;">No questions match this filter</td></tr>`;
            // Disable both dropdowns if there is nothing
            if (topicSel) {
                topicSel.innerHTML = `<option value="">Select Topic</option>`;
                topicSel.disabled = true;
            }
            if (lessonSel) {
                lessonSel.innerHTML = `<option value="">Select Lesson</option>`;
                lessonSel.disabled = true;
            }
            return;
        }

        // ---- NEW BLOCK: Populate both Topic and Lesson dropdowns from availability table data ----
        if (topicSel) {
            const availableTopics = rows.filter(row => row.source_type === 'topic');
            if (availableTopics.length > 0) {
                topicSel.innerHTML = `<option value="">Select Topic</option>`;
                availableTopics.forEach(row => {
                    topicSel.innerHTML += `<option value="${row.source_id}">${row.source}</option>`;
                });
                topicSel.disabled = false;
            } else {
                // Only disable if no topic is selected in the filter (subject-level query)
                // If a topic is selected, keep Topic dropdown as-is/active
                if (!topicSel.value) {
                    topicSel.innerHTML = `<option value="">Select Topic</option>`;
                    topicSel.disabled = true;
                }
            }
        }

        if (lessonSel) {
            const availableLessons = rows.filter(row => row.source_type === 'lesson');
            if (availableLessons.length > 0) {
                lessonSel.innerHTML = `<option value="">Select Lesson</option>`;
                availableLessons.forEach(row => {
                    lessonSel.innerHTML += `<option value="${row.source_id}">${row.source}</option>`;
                });
                lessonSel.disabled = false;
            } else {
                lessonSel.innerHTML = `<option value="">Select Lesson</option>`;
                lessonSel.disabled = true;
            }
        }
        // ----------------------------------------------------------------------

        tableBody.innerHTML = rows.map(row => {
            return `
            <tr data-source-id="${row.source_id}" data-source-type="${row.source_type}">
                <td style="background-color: #f5f5f5; padding: 8px;">
                    <label style="display: flex; align-items: center; cursor: pointer;">
                        <input type="checkbox" class="source-checkbox" checked style="margin-right: 8px;">
                        <span style="color: #333; font-weight: 500;">${row.source}</span>
                    </label>
                </td>
                ${renderCell(row.mcq, 'mcq')}
                ${renderCell(row.tf, 'tf')}
                ${renderCell(row.mtf, 'mtf')}
                ${renderCell(row.id, 'id')}
                ${renderCell(row.match, 'match')}
                ${renderCell(row.fib, 'fib')}
                ${renderCell(row.enum, 'enum')}
                ${renderCell(row.essay, 'essay')}
            </tr>
            `;
        }).join('');

        addInputValidation();
    });

    function renderCell(val, type) {
        const count = parseInt(val) || 0;
        if (count === 0) {
            // Hidden input for robust consistent indexing if needed
            return `<td style="text-align:center; background-color: #fafafa; color:#ccc;">
                <input type="number" data-type="${type}" value="0" readonly style="display:none;">
                —
            </td>`;
        }
        return `<td style="text-align:center; padding: 8px;">
            <input type="number"
                data-type="${type}"
                min="0"
                max="${count}"
                value=""
                placeholder="${count}"
                class="question-input"
                data-max="${count}"
                style="width: 70px; text-align: center; padding: 6px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
        </td>`;
    }

    function addInputValidation() {
        const inputs = tableBody.querySelectorAll('input[type="number"]:not([readonly])');
        inputs.forEach(input => {
            input.addEventListener('input', (e) => {
                const max = parseInt(e.target.dataset.max) || 0;
                let val = parseInt(e.target.value);
                if (val > max) {
                    e.target.value = max;
                    e.target.style.borderColor = 'red';
                    setTimeout(() => { e.target.style.borderColor = '#ddd'; }, 1000);
                }
                if (val < 0) e.target.value = 0;
            });
            input.addEventListener('focus', (e) => {
                const max = e.target.dataset.max;
                e.target.style.borderColor = '#4CAF50';
                e.target.title = `Available: ${max} questions`;
            });
            input.addEventListener('blur', (e) => {
                e.target.style.borderColor = '#ddd';
            });
        });
    }

    // =====================
    // SAVE POINTS MODAL (NEW BLOCK)
    // =====================

    // Adjust these selectors for your modal elements
    const savePointsBtn = document.getElementById('savePointsModalButton');
    if (savePointsBtn) {
        savePointsBtn.addEventListener('click', function(e) {
            e.preventDefault(); // Just in case the button is still inside a <form>
            // Collect points per type from modal inputs; 
            // update these names/IDs if your HTML differs!
            const pointsData = {
                mcq: document.getElementById('points-mcq')?.value || null,
                tf: document.getElementById('points-tf')?.value || null,
                mtf: document.getElementById('points-mtf')?.value || null,
                id: document.getElementById('points-id')?.value || null,
                match: document.getElementById('points-match')?.value || null,
                fib: document.getElementById('points-fib')?.value || null,
                enum: document.getElementById('points-enum')?.value || null,
                essay: document.getElementById('points-essay')?.value || null,
            };

            // Remove keys with null or '' values
            Object.keys(pointsData).forEach(k => {
                if (pointsData[k] === null || pointsData[k] === '') delete pointsData[k];
            });

            fetch('/teacher/test-builder/save-points-to-session', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ question_type_points: pointsData })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Refresh the inline "Points/type" summary so the new values
                    // show immediately, without a page reload.
                    window.renderPointsSummary?.();
                    // Hide the modal (pure JS way):
                    document.getElementById('pointsModal').style.display = 'none';
                    // OR if you use jQuery/Bootstrap 4 modal: $('#pointsModal').modal('hide');
                } else {
                    alert('Saving points failed. Please try again.');
                }
            })
            .catch((err) => {
                alert('There was an error saving the points. Please try again.');
                console.error('Points save error:', err);
            });
        });
    }

});