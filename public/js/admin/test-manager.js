/* =========================================================
   TEST MANAGER – BASELINE JS (STABLE)
   ========================================================= */

console.log('TEST MANAGER JS LOADED');

/* ===============================
   MODAL CONTROLS
================================ */

function openModal(id) {
    const overlay = document.getElementById(id);
    if (!overlay) return;
    overlay.style.display = 'flex';
}

function closeModal(id) {
    const overlay = document.getElementById(id);
    if (!overlay) return;
    overlay.style.display = 'none';
}

/* close modal when clicking outside */
document.addEventListener('click', function (e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.style.display = 'none';
    }
});

/* close modal on ESC */
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.style.display = 'none';
        });
    }
});


/* ===============================
   LESSON: SUBJECT → TOPIC
================================ */

const lessonSubject = document.getElementById('lesson_subject_id');
const lessonTopic   = document.getElementById('lesson_topic_id');

if (lessonSubject && lessonTopic) {
    lessonSubject.addEventListener('change', function () {
        const subjectId = this.value;

        lessonTopic.innerHTML = '<option value="">Loading...</option>';

        if (!subjectId) {
            lessonTopic.innerHTML = '<option value="">-- select subject first --</option>';
            return;
        }

        fetch(`/admin/test-manager/topics/by-subject/${subjectId}`)
            .then(res => res.json())
            .then(data => {
                lessonTopic.innerHTML = '<option value="">-- select topic --</option>';
                data.forEach(topic => {
                    lessonTopic.innerHTML += `<option value="${topic.id}">${topic.name}</option>`;
                });
            })
            .catch(() => {
                lessonTopic.innerHTML = '<option value="">Error loading topics</option>';
            });
    });
}


/* ===============================
   COMPETENCY: SUBJECT → TOPIC → LESSON
================================ */

const compSubject = document.getElementById('competency_subject_id');
const compTopic   = document.getElementById('competency_topic_id');
const compLesson  = document.getElementById('competency_lesson_id');

if (compSubject && compTopic && compLesson) {

    /* SUBJECT → TOPIC */
    compSubject.addEventListener('change', function () {
        const subjectId = this.value;

        compTopic.innerHTML  = '<option value="">Loading...</option>';
        compLesson.innerHTML = '<option value="">-- select topic first --</option>';

        if (!subjectId) {
            compTopic.innerHTML = '<option value="">-- select subject first --</option>';
            return;
        }

        fetch(`/admin/test-manager/topics/by-subject/${subjectId}`)
            .then(res => res.json())
            .then(data => {
                compTopic.innerHTML = '<option value="">-- select topic --</option>';
                data.forEach(topic => {
                    compTopic.innerHTML += `<option value="${topic.id}">${topic.name}</option>`;
                });
            })
            .catch(() => {
                compTopic.innerHTML = '<option value="">Error loading topics</option>';
            });
    });

    /* TOPIC → LESSON */
    compTopic.addEventListener('change', function () {
        const topicId = this.value;

        compLesson.innerHTML = '<option value="">Loading lessons...</option>';

        if (!topicId) {
            compLesson.innerHTML = '<option value="">-- select topic first --</option>';
            return;
        }

        fetch(`/admin/test-manager/lessons/by-topic/${topicId}`)
            .then(res => res.json())
            .then(data => {
                compLesson.innerHTML = '<option value="">-- select lesson --</option>';
                data.forEach(lesson => {
                    compLesson.innerHTML += `<option value="${lesson.id}">${lesson.name}</option>`;
                });
            })
            .catch(() => {
                compLesson.innerHTML = '<option value="">Error loading lessons</option>';
            });
    });
}


/* ===============================
   BULK INPUT HELPER (SAFE)
================================ */

/*
  Used by ALL bulk modals:
  Subject, Topic, Lesson, Competency
*/
function addInput(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;

    const input = document.createElement('input');
    input.type = 'text';
    input.name = 'names[]';
    input.placeholder = 'Enter name';

    container.appendChild(input);
}
