{{-- Draggable Create Subject modal (uses x-modal.form) --}}
@php
    $subjectsLabels = config('tables.tables.subjects.labels', []);
@endphp

<x-modal.form id="subjectCreateModal" title="Add New Subject" widthClass="w-[480px]">
    <form id="subjectCreateForm" method="POST" action="{{ route('program_head.subjects.store') }}">
        @csrf
        <input type="hidden" name="school_id" value="{{ auth()->user()->school_id }}">

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    {{ $subjectsLabels['name'] ?? 'Subject Name' }}
                </label>
                <input name="name" type="text" required placeholder="e.g. Mathematics"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    {{ $subjectsLabels['code'] ?? 'Code' }}
                </label>
                <input name="code" type="text" required placeholder="e.g. MATH-101"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    {{ $subjectsLabels['category'] ?? 'Category' }}
                </label>
                <select name="category" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                    <option value="major">Major</option>
                    <option value="gen_ed">General Education</option>
                    <option value="prof_ed">Professional Education</option>
                    <option value="pe">Physical Education</option>
                    <option value="nstp">NSTP</option>
                    <option value="internship">Internship</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    {{ $subjectsLabels['description'] ?? 'Description' }}
                </label>
                <textarea name="description" rows="2"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none"></textarea>
            </div>

            {{-- Status is managed automatically:
                 - subjects.is_active defaults to active on create.
                 - program_subjects.is_active starts inactive and is flipped active
                   when admission opens an enrollment session. --}}
            <input type="hidden" name="is_active" value="1">
        </div>
    </form>
</x-modal.form>