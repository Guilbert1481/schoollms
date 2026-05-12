// Open modal and fill fields for "Edit Session"
function openEditSessionModal(data) {
    // Back-compat: allow old positional args (id, title, start, end)
    if (typeof data !== 'object' || data === null) {
        data = {
            id: arguments[0],
            title: arguments[1],
            start_date: arguments[2],
            end_date: arguments[3],
        };
    }

    const modal = document.getElementById('editSessionModal');
    const form = document.getElementById('editSessionForm');
    // Set correct action URL (with id)
    form.action = "/admission/enrollment-settings/update/" + data.id;

    const setVal = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.value = val ?? '';
    };

    setVal('edit_title', data.title);
    setVal('edit_start_date', data.start_date);
    setVal('edit_end_date', data.end_date);
    setVal('edit_price', data.price);
    setVal('edit_instructor_title', data.instructor_title);
    setVal('edit_instructor_name', data.instructor_name);
    setVal('edit_course_details', data.course_details);

    const previewWrap = document.getElementById('edit_cover_preview_wrap');
    const preview = document.getElementById('edit_cover_preview');
    if (previewWrap && preview) {
        if (data.cover_image) {
            preview.src = data.cover_image;
            previewWrap.classList.remove('hidden');
        } else {
            preview.src = '';
            previewWrap.classList.add('hidden');
        }
    }

    modal.classList.remove('hidden');
}

// Hide the edit modal
function closeEditSessionModal() {
    document.getElementById('editSessionModal').classList.add('hidden');
}



// For "Create Session From Term" Modal, same idea:
function openSessionFromTerm(termId, academicYearId, name, startDate, endDate, enrollmentType) {
    const modal = document.getElementById('sessionFromTermModal');

    // Hidden fields
    document.getElementById('modal_term_id').value = termId;
    document.getElementById('modal_academic_year_id').value = academicYearId;

    // Name (set BOTH if you keep display + hidden)
    document.getElementById('modal_name').value = name;

    // Title mirrors the name by default (required by controller)
    const titleInput = document.getElementById('modal_title');
    if (titleInput) titleInput.value = name;

    const displayInput = document.getElementById('modal_name_display');
    if (displayInput) {
        displayInput.value = name;
    }

    // Dates
    document.getElementById('modal_start_date').value = startDate;
    document.getElementById('modal_end_date').value = endDate;

    // Show/hide training-only fields based on enrollment type.
    // Regular semesters only need name + start/end dates.
    const isRegular = (enrollmentType || '').toLowerCase() === 'regular';
    modal.querySelectorAll('[data-training-only]').forEach(el => {
        el.classList.toggle('hidden', isRegular);
        // Clear values so they aren't submitted for regular terms.
        if (isRegular) {
            el.querySelectorAll('input, select, textarea').forEach(input => {
                if (input.type === 'file') input.value = '';
                else input.value = '';
            });
        }
    });

    // Show modal (Tailwind way)
    modal.classList.remove('hidden');
}

function closeSessionFromTermModal() {
    const modal = document.getElementById('sessionFromTermModal');
    modal.classList.add('hidden');
    modal.style.display = 'none';
}