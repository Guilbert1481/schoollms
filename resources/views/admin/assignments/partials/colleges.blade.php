<div class="bg-white p-6 rounded-xl border">

    <h2 class="text-lg font-bold mb-4">College Assignment</h2>

    @php
        $collegesConfig  = config('tables.tables.colleges');
        $collegesColumns = $collegesConfig['columns'] ?? [];
        $collegesLabels  = $collegesConfig['labels']  ?? [];
    @endphp

    <x-table.table
        tableKey="colleges"
        :columns="$collegesColumns"
        :data="$colleges"
        :actions="config('tables.table-actions.colleges')"
        createModal="collegeCreateModal"
        createLabel="+ New College"
        deleteRoute="admin.assignments.destroyCollege"
    />

    {{-- Pass rows to JS for the edit/assign modal prefill --}}
    <script>
        window.__COLLEGES__ = @json($colleges);

        function findCollege(id) {
            return (window.__COLLEGES__ || []).find(c => c.id == id);
        }

        // Called by colleges table buttons (unique name avoids clash with modal.js)
        function openCollegeModal(modalId, id) {
            const college = findCollege(id);
            if (!college) return;

            if (modalId === 'collegeEditModal') {
                document.getElementById('collegeEditForm').action =
                    "{{ url('admin/assignments/colleges/info') }}/" + id;
                document.getElementById('collegeEdit_code').value        = college.code ?? '';
                document.getElementById('collegeEdit_name').value        = college.name ?? '';
                document.getElementById('collegeEdit_description').value = college.description ?? '';
            }

            if (modalId === 'collegeAssignModal') {
                document.getElementById('collegeAssignForm').action =
                    "{{ route('admin.assignments.storeColleges') }}";
                document.getElementById('collegeAssign_college_id').value = id;
                const sel = document.getElementById('collegeAssign_dean_id');
                if (sel) sel.value = college.dean_id ?? '';
                document.getElementById('collegeAssign_title').textContent =
                    'Assign Dean to ' + (college.name ?? '');
            }

            openModal(modalId);
        }

        // Lock form actions so nothing can override them
        document.addEventListener('DOMContentLoaded', function () {
            const assignUrl = "{{ route('admin.assignments.storeColleges') }}";
            const editBase  = "{{ url('admin/assignments/colleges/info') }}";

            const assignForm = document.getElementById('collegeAssignForm');
            if (assignForm) {
                assignForm.addEventListener('submit', function () {
                    assignForm.action = assignUrl;
                });
            }

            const editForm = document.getElementById('collegeEditForm');
            if (editForm) {
                editForm.addEventListener('submit', function () {
                    const idEl = document.getElementById('collegeAssign_college_id'); // unused here
                    // editForm.action is set by openCollegeModal to include id
                    if (!editForm.action || editForm.action.endsWith('/0')) {
                        console.warn('Edit form action looks invalid:', editForm.action);
                    }
                });
            }
        });
    </script>
</div>

{{-- CREATE MODAL --}}
<x-modal.form id="collegeCreateModal" title="Add New College" widthClass="w-[480px]">
    <form method="POST" action="{{ route('admin.assignments.createCollege') }}">
        @csrf

        <div class="mb-3">
            <label class="block text-sm mb-1">{{ $collegesLabels['code'] ?? 'Code' }}</label>
            <input type="text" name="code" required class="border p-2 w-full rounded">
        </div>

        <div class="mb-3">
            <label class="block text-sm mb-1">{{ $collegesLabels['name'] ?? 'College Name' }}</label>
            <input type="text" name="name" required class="border p-2 w-full rounded">
        </div>

        <div class="mb-3">
            <label class="block text-sm mb-1">{{ $collegesLabels['description'] ?? 'Description' }}</label>
            <textarea name="description" rows="2" class="border p-2 w-full rounded"></textarea>
        </div>
    </form>
</x-modal.form>

{{-- EDIT MODAL --}}
<x-modal.form id="collegeEditModal" title="Edit College" widthClass="w-[480px]">
    <form id="collegeEditForm" method="POST" action="{{ url('admin/assignments/colleges/info/0') }}">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="block text-sm mb-1">{{ $collegesLabels['code'] ?? 'Code' }}</label>
            <input type="text" id="collegeEdit_code" name="code" required class="border p-2 w-full rounded">
        </div>

        <div class="mb-3">
            <label class="block text-sm mb-1">{{ $collegesLabels['name'] ?? 'College Name' }}</label>
            <input type="text" id="collegeEdit_name" name="name" required class="border p-2 w-full rounded">
        </div>

        <div class="mb-3">
            <label class="block text-sm mb-1">{{ $collegesLabels['description'] ?? 'Description' }}</label>
            <textarea id="collegeEdit_description" name="description" rows="2" class="border p-2 w-full rounded"></textarea>
        </div>
    </form>
</x-modal.form>

{{-- ASSIGN DEAN MODAL --}}
<x-modal.form id="collegeAssignModal" title="Assign Dean" widthClass="w-[420px]">
    <h2 id="collegeAssign_title" class="text-base font-semibold mb-3"></h2>

    <form id="collegeAssignForm" method="POST" action="{{ route('admin.assignments.storeColleges') }}">
        @csrf
        <input type="hidden" id="collegeAssign_college_id" name="college_id">

        <div class="mb-3">
            <label class="block text-sm mb-1">{{ $collegesLabels['dean_id'] ?? 'Assigned Dean' }}</label>
            <select id="collegeAssign_dean_id" name="dean_id" class="border p-2 w-full rounded">
                <option value="">-- Unassign --</option>
                @foreach($deans as $dean)
                    <option value="{{ $dean->id }}">{{ $dean->name }}</option>
                @endforeach
            </select>
        </div>
    </form>
</x-modal.form>