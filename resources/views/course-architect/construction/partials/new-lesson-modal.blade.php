{{-- New Lesson Resource modal — auto-filled from current folder context.
     Renders only at Level 3, so subject/topic/lesson are guaranteed. --}}

<x-modal.form id="newLessonModal" title="New Lesson" widthClass="w-[480px]">
    <form id="newLessonForm"
          method="POST"
          action="{{ route('course-architect.lesson-studio.store') }}"
          enctype="multipart/form-data">
        @csrf

        <input type="hidden" name="subject_id" value="{{ $subjectModel->id }}">
        <input type="hidden" name="topic_id"   value="{{ $topicModel->id }}">
        <input type="hidden" name="lesson_id"  value="{{ $lessonModel->id }}">

        <div class="space-y-3">
            <div class="rounded-xl bg-slate-50 ring-1 ring-slate-200 p-3 text-xs text-slate-600 space-y-1">
                <div><span class="font-semibold text-slate-700">Subject:</span> {{ $subjectModel->name }}</div>
                <div><span class="font-semibold text-slate-700">Topic:</span> {{ $topicModel->name }}</div>
                <div><span class="font-semibold text-slate-700">Lesson:</span> {{ $lessonModel->name }}</div>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Competency <span class="text-xs text-gray-400">(optional)</span></label>
                <select name="competency_id" id="lr_competency"
                        class="w-full px-3 py-2 border border-gray-300 rounded text-sm">
                    <option value="">Loading…</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Upload <span class="text-xs text-gray-500">(optional — video, pdf, ppt — max 200 MB)</span></label>
                <input type="file" name="file"
                       accept=".mp4,.mov,.webm,.mkv,video/*,.pdf,application/pdf,.ppt,.pptx,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation"
                       class="w-full text-sm">
            </div>
        </div>
    </form>
</x-modal.form>

<script>
(function () {
    const lessonId = {{ $lessonModel->id }};
    const sel = document.getElementById('lr_competency');
    if (!sel) return;

    fetch(`/api/lessons/${lessonId}/competencies`, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
    .then(r => r.ok ? r.json() : [])
    .then(items => {
        sel.innerHTML = '<option value="">— None —</option>' +
            items.map(i => `<option value="${i.id}">${i.name}</option>`).join('');
    })
    .catch(() => { sel.innerHTML = '<option value="">— None —</option>'; });
})();
</script>
