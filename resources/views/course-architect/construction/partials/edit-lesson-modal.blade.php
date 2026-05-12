{{-- Edit Lesson Resource modal — mirrors New Lesson with prefill + PUT. --}}
<x-modal.form id="editLessonModal" title="Edit Lesson" widthClass="w-[560px]">
    <form id="editLessonForm" method="POST" action="" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="space-y-3">

            <div>
                <label class="block text-sm font-medium mb-1">Search Master Subject</label>
                <select name="subject_id" id="elr_subject" required
                        class="w-full px-3 py-2 border border-gray-300 rounded text-sm">
                    <option value="">Loading subjects…</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Topic</label>
                <select name="topic_id" id="elr_topic" required disabled
                        class="w-full px-3 py-2 border border-gray-300 rounded text-sm">
                    <option value="">Select Subject first</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Lesson</label>
                <select name="lesson_id" id="elr_lesson" required disabled
                        class="w-full px-3 py-2 border border-gray-300 rounded text-sm">
                    <option value="">Select Topic first</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Competency</label>
                <select name="competency_id" id="elr_competency" disabled
                        class="w-full px-3 py-2 border border-gray-300 rounded text-sm">
                    <option value="">Select Lesson first</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Replace File <span class="text-xs text-gray-500">(optional — leave blank to keep current)</span></label>
                <input type="file" name="file"
                       accept=".mp4,.mov,.webm,.mkv,video/*,.pdf,application/pdf,.ppt,.pptx,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation"
                       class="w-full text-sm">
                <div id="elr_current_file" class="mt-1 text-xs text-gray-500"></div>
            </div>

        </div>
    </form>
</x-modal.form>

<script>
(function () {
    let elrSubjectsLoaded = false;
    let elrListenersBound = false;

    const fetchJson = async (url) => {
        const r = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
        if (!r.ok) throw new Error(r.status);
        return r.json();
    };
    const fillSelect = (sel, items, placeholder, selectedId) => {
        sel.innerHTML = `<option value="">${placeholder}</option>` +
            items.map(i => `<option value="${i.id}"${String(i.id)===String(selectedId)?' selected':''}>${i.label || i.name}</option>`).join('');
        sel.disabled = items.length === 0;
    };

    async function ensureSubjectsLoaded(selectedId) {
        const subj = document.getElementById('elr_subject');
        if (!elrSubjectsLoaded) {
            try {
                const subjects = await fetchJson('/api/subjects');
                fillSelect(subj, subjects, 'Select Subject', selectedId);
                elrSubjectsLoaded = true;
            } catch {
                subj.innerHTML = '<option value="">Failed to load subjects</option>';
                subj.disabled = true;
                return;
            }
        } else if (selectedId !== undefined) {
            subj.value = String(selectedId ?? '');
        }

        if (!elrListenersBound) {
            subj.addEventListener('change', onSubjectChange);
            document.getElementById('elr_topic').addEventListener('change', onTopicChange);
            document.getElementById('elr_lesson').addEventListener('change', onLessonChange);
            elrListenersBound = true;
        }
    }

    async function onSubjectChange() {
        const subject = document.getElementById('elr_subject');
        const topic = document.getElementById('elr_topic');
        const lesson = document.getElementById('elr_lesson');
        const competency = document.getElementById('elr_competency');
        topic.innerHTML = '<option value="">Loading topics…</option>'; topic.disabled = true;
        lesson.innerHTML = '<option value="">Select Topic first</option>'; lesson.disabled = true;
        competency.innerHTML = '<option value="">Select Lesson first</option>'; competency.disabled = true;
        if (!subject.value) { topic.innerHTML = '<option value="">Select Subject first</option>'; return; }
        try {
            const topics = await fetchJson(`/api/subjects/${subject.value}/topics`);
            fillSelect(topic, topics, topics.length ? 'Select Topic' : 'No topics found', null);
        } catch { topic.innerHTML = '<option value="">Failed to load topics</option>'; }
    }

    async function onTopicChange() {
        const topic = document.getElementById('elr_topic');
        const lesson = document.getElementById('elr_lesson');
        const competency = document.getElementById('elr_competency');
        lesson.innerHTML = '<option value="">Loading lessons…</option>';
        lesson.disabled = true;
        competency.innerHTML = '<option value="">Select Lesson first</option>';
        competency.disabled = true;
        if (!topic.value) { lesson.innerHTML = '<option value="">Select Topic first</option>'; return; }
        try {
            const lessons = await fetchJson(`/api/topics/${topic.value}/lessons`);
            fillSelect(lesson, lessons, lessons.length ? 'Select Lesson' : 'No lessons found', null);
        } catch { lesson.innerHTML = '<option value="">Failed to load lessons</option>'; }
    }

    async function onLessonChange() {
        const lesson = document.getElementById('elr_lesson');
        const competency = document.getElementById('elr_competency');
        competency.innerHTML = '<option value="">Loading competencies…</option>';
        competency.disabled = true;
        if (!lesson.value) { competency.innerHTML = '<option value="">Select Lesson first</option>'; return; }
        try {
            const comps = await fetchJson(`/api/lessons/${lesson.value}/competencies`);
            fillSelect(competency, comps, comps.length ? 'Select Competency (optional)' : 'No competencies — leave blank', null);
            competency.disabled = false;
        } catch { competency.innerHTML = '<option value="">Failed to load competencies</option>'; }
    }

    window.openLessonEdit = async function (id) {
        const form = document.getElementById('editLessonForm');
        form.action = "{{ url('course-architect/lesson-studio') }}/" + id;

        try {
            const d = await fetchJson("{{ url('course-architect/lesson-studio') }}/" + id + "/edit");

            document.getElementById('elr_current_file').textContent = d.filename
                ? 'Current: ' + d.filename
                : 'No file currently attached.';

            // Subject (plain select — no TomSelect dependency)
            await ensureSubjectsLoaded(d.subject_id);

            const topicSel = document.getElementById('elr_topic');
            const lessonSel = document.getElementById('elr_lesson');
            const compSel = document.getElementById('elr_competency');

            if (d.subject_id) {
                const topics = await fetchJson(`/api/subjects/${d.subject_id}/topics`);
                fillSelect(topicSel, topics, 'Select Topic', d.topic_id);
            } else {
                topicSel.innerHTML = '<option value="">Select Subject first</option>'; topicSel.disabled = true;
            }

            if (d.topic_id) {
                const lessons = await fetchJson(`/api/topics/${d.topic_id}/lessons`);
                fillSelect(lessonSel, lessons, 'Select Lesson', d.lesson_id);
            } else {
                lessonSel.innerHTML = '<option value="">Select Topic first</option>'; lessonSel.disabled = true;
            }

            if (d.lesson_id) {
                const comps = await fetchJson(`/api/lessons/${d.lesson_id}/competencies`);
                fillSelect(compSel, comps, 'Select Competency (optional)', d.competency_id);
                compSel.disabled = false;
            } else {
                compSel.innerHTML = '<option value="">Select Lesson first</option>'; compSel.disabled = true;
            }

            openModal('editLessonModal');
        } catch (e) {
            console.error('openLessonEdit failed:', e);
            alert('Failed to load lesson resource for edit.');
        }
    };
})();
</script>
