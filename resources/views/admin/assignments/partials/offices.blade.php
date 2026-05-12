<div class="bg-white p-6 rounded-xl border">

    <h2 class="text-lg font-bold mb-4">Office Assignment</h2>

    @php
        $officesConfig  = config('tables.tables.offices');
        $officesColumns = $officesConfig['columns'] ?? [];
        $officesLabels  = $officesConfig['labels']  ?? [];
    @endphp

    <x-table.table
        tableKey="offices"
        :columns="$officesColumns"
        :data="$offices"
        :actions="config('tables.table-actions.offices')"
        createModal="officeCreateModal"
        createLabel="+ New Office"
        deleteRoute="admin.assignments.destroyOffice"
    />

    <script>
        window.__OFFICES__ = @json($offices);

        function findOffice(id) {
            return (window.__OFFICES__ || []).find(o => o.id == id);
        }

        function openOfficeModal(modalId, id) {
            const office = findOffice(id);
            if (!office) return;

            if (modalId === 'officeEditModal') {
                document.getElementById('officeEditForm').action =
                    "{{ url('admin/assignments/offices/info') }}/" + id;
                document.getElementById('officeEdit_code').value = office.code ?? '';
                document.getElementById('officeEdit_name').value = office.name ?? '';
                const typeSel = document.getElementById('officeEdit_office_type_id');
                if (typeSel) typeSel.value = office.office_type_id ?? '';
            }

            if (modalId === 'officeAssignModal') {
                document.getElementById('officeAssign_office_id').value = id;
                const sel = document.getElementById('officeAssign_office_head_id');
                if (sel) sel.value = office.office_head_id ?? '';
                document.getElementById('officeAssign_title').textContent =
                    'Assign Head to ' + (office.name ?? '');
            }

            openModal(modalId);
        }

        document.addEventListener('DOMContentLoaded', function () {
            const assignUrl = "{{ route('admin.assignments.storeOffices') }}";
            const assignForm = document.getElementById('officeAssignForm');
            if (assignForm) {
                assignForm.addEventListener('submit', function () {
                    assignForm.action = assignUrl;
                });
            }
        });
    </script>
</div>

{{-- CREATE MODAL --}}
<x-modal.form id="officeCreateModal" title="Add New Office" widthClass="w-[480px]">
    <form method="POST" action="{{ route('admin.assignments.createOffice') }}">
        @csrf

        <div class="mb-3">
            <label class="block text-sm mb-1">{{ $officesLabels['code'] ?? 'Code' }}</label>
            <input type="text" name="code" class="border p-2 w-full rounded">
        </div>

        <div class="mb-3">
            <label class="block text-sm mb-1">{{ $officesLabels['name'] ?? 'Office Name' }}</label>
            <input type="text" name="name" required class="border p-2 w-full rounded">
        </div>

        <div class="mb-3">
            <label class="block text-sm mb-1">{{ $officesLabels['office_type_id'] ?? 'Type' }}</label>
            <select name="office_type_id" class="border p-2 w-full rounded">
                <option value="">-- Select Type --</option>
                @foreach($officeTypes as $type)
                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                @endforeach
            </select>
        </div>
    </form>
</x-modal.form>

{{-- EDIT MODAL --}}
<x-modal.form id="officeEditModal" title="Edit Office" widthClass="w-[480px]">
    <form id="officeEditForm" method="POST" action="{{ url('admin/assignments/offices/info/0') }}">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="block text-sm mb-1">{{ $officesLabels['code'] ?? 'Code' }}</label>
            <input type="text" id="officeEdit_code" name="code" class="border p-2 w-full rounded">
        </div>

        <div class="mb-3">
            <label class="block text-sm mb-1">{{ $officesLabels['name'] ?? 'Office Name' }}</label>
            <input type="text" id="officeEdit_name" name="name" required class="border p-2 w-full rounded">
        </div>

        <div class="mb-3">
            <label class="block text-sm mb-1">{{ $officesLabels['office_type_id'] ?? 'Type' }}</label>
            <select id="officeEdit_office_type_id" name="office_type_id" class="border p-2 w-full rounded">
                <option value="">-- Select Type --</option>
                @foreach($officeTypes as $type)
                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                @endforeach
            </select>
        </div>
    </form>
</x-modal.form>

{{-- ASSIGN HEAD MODAL --}}
<x-modal.form id="officeAssignModal" title="Assign Office Head" widthClass="w-[420px]">
    <h2 id="officeAssign_title" class="text-base font-semibold mb-3"></h2>

    <form id="officeAssignForm" method="POST" action="{{ route('admin.assignments.storeOffices') }}">
        @csrf
        <input type="hidden" id="officeAssign_office_id" name="office_id">

        <div class="mb-3">
            <label class="block text-sm mb-1">{{ $officesLabels['office_head_id'] ?? 'Assigned Head' }}</label>
            <select id="officeAssign_office_head_id" name="office_head_id" class="border p-2 w-full rounded">
                <option value="">-- Unassign --</option>
                @foreach($officeHeads as $head)
                    <option value="{{ $head->id }}">
                        {{ $head->name }}@if(!empty($head->position)) — {{ $head->position }}@endif
                    </option>
                @endforeach
            </select>
        </div>
    </form>
</x-modal.form>
