{{-- Quick-upload modal for the lesson list (Level 2).
     Uploads a resource straight to a lesson without drilling in; competency is
     left unset (tag one from inside the lesson if needed). `stay=1` returns to
     this list so several lessons can be filled in a row. Renders only for
     content-adders at the lessons level. --}}

<x-modal.form id="lessonListUploadModal" title="Upload Resource" widthClass="w-[480px]">
    <form method="POST"
          action="{{ route('course-architect.lesson-studio.store') }}"
          enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="subject_id" value="{{ $subjectModel->id }}">
        <input type="hidden" name="topic_id"   value="{{ $topicModel->id }}">
        <input type="hidden" name="lesson_id"  id="quickUploadLessonId">
        <input type="hidden" name="stay"       value="1">

        <div class="space-y-3">
            <div class="rounded-xl bg-slate-50 ring-1 ring-slate-200 p-3 text-xs text-slate-600 space-y-1">
                <div><span class="font-semibold text-slate-700">Topic:</span> {{ $topicModel->name }}</div>
                <div><span class="font-semibold text-slate-700">Lesson:</span> <span id="quickUploadLessonName"></span></div>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">
                    Upload <span class="text-xs text-gray-500">(video, pdf, ppt — max 200 MB)</span>
                </label>
                <input type="file" name="file" required
                       accept=".mp4,.mov,.webm,.mkv,video/*,.pdf,application/pdf,.ppt,.pptx,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation"
                       class="w-full text-sm">
            </div>
        </div>
    </form>
</x-modal.form>

<script>
(function () {
    const names = @json((object) $lessonNames);
    // Called by the "Upload" row action (passes the lesson row id).
    window.lsUploadResource = function (id) {
        document.getElementById('quickUploadLessonId').value = id;
        document.getElementById('quickUploadLessonName').textContent = names[id] || '';
        openModal('lessonListUploadModal');
    };
})();
</script>
