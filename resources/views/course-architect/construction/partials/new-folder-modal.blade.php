{{-- New Folder modal — contextual creation:
       Level 1 → Topic
       Level 2 → Lesson
       Level 3 → Competency --}}

@php
    $folderLabel = match($level) {
        1 => 'New Topic',
        2 => 'New Lesson Folder',
        3 => 'New Competency',
        default => 'New Folder',
    };
    $folderHint = match($level) {
        1 => 'A topic will be created inside "' . ($subjectModel->name ?? '') . '".',
        2 => 'A lesson will be created inside "' . ($topicModel->name ?? '') . '".',
        3 => 'A competency will be created inside "' . ($lessonModel->name ?? '') . '".',
        default => '',
    };
@endphp

<x-modal.form id="newFolderModal" :title="$folderLabel" widthClass="w-[420px]">
    <form id="newFolderForm" method="POST" action="{{ route('course-architect.lesson-studio.folder.store') }}">
        @csrf

        @if($subjectModel)
            <input type="hidden" name="subject_id" value="{{ $subjectModel->id }}">
        @endif
        @if($topicModel)
            <input type="hidden" name="topic_id" value="{{ $topicModel->id }}">
        @endif
        @if($lessonModel)
            <input type="hidden" name="lesson_id" value="{{ $lessonModel->id }}">
        @endif

        <div class="space-y-3">
            @if($folderHint)
                <p class="text-xs text-slate-500">{{ $folderHint }}</p>
            @endif

            <div>
                <label class="block text-sm font-medium mb-1">Name</label>
                <input type="text" name="name" required maxlength="255" autofocus
                       class="w-full px-3 py-2 border border-gray-300 rounded text-sm"
                       placeholder="Enter a name…">
            </div>
        </div>
    </form>
</x-modal.form>
