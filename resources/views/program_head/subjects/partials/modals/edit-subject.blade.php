{{-- Draggable Edit Subject modal (uses x-modal.form) --}}
@php
    $subjectsLabels = config('tables.tables.subjects.labels', []);
@endphp

<x-modal.form id="subjectEditModal" title="Edit Subject" widthClass="w-[480px]">
    <form id="subjectEditForm" method="POST" action="{{ url('staff/program-head/subjects') }}/0">
        @csrf
        @method('PUT')

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    {{ $subjectsLabels['name'] ?? 'Subject Name' }}
                </label>
                <input id="subjectEdit_name" name="name" type="text" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    {{ $subjectsLabels['code'] ?? 'Code' }}
                </label>
                <input id="subjectEdit_code" name="code" type="text" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    {{ $subjectsLabels['category'] ?? 'Category' }}
                </label>
                <select id="subjectEdit_category" name="category"
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
                <textarea id="subjectEdit_description" name="description" rows="2"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none"></textarea>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    {{ $subjectsLabels['is_active'] ?? 'Status' }}
                </label>
                <select id="subjectEdit_active" name="is_active"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
        </div>
    </form>
</x-modal.form>
