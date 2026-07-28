{{-- Bulk-add button (Add Topic / Add Lesson) — rendered just to the left of the
     view toggle, so the "add child" action lives with the table controls. Only
     shows when there is a parent row to tick (folders present, level < 3). --}}
@php $bulkChild = ['Topic', 'Lesson', 'Competency'][$level] ?? null; @endphp
{{-- Adding to the outline is the subject_coordinator's job on basic-ed subjects;
     a course_architect only adds content, so it never sees this button. --}}
@if($canManageFolders && $level < 3 && count($folders) > 0)
    <button type="button"
            onclick="lsOpenBulkAdd()"
            class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-md hover:bg-indigo-700">
        <i data-lucide="plus" class="w-4 h-4"></i> Add {{ $bulkChild }}
    </button>
@endif
