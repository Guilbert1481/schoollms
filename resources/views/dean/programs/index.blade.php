@extends('layouts.app')
@section('content')

<div x-data="programPage()" class="container mx-auto">

<!-- ============================= -->
<!-- KPI CARDS -->
<!-- ============================= -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">

    <div class="bg-white p-4 rounded shadow border">
        <p class="text-sm text-gray-500">Total Programs</p>
        <h2 class="text-2xl font-bold">{{ \App\Models\Program::count() }}</h2>
    </div>

    <div class="bg-white p-4 rounded shadow border">
        <p class="text-sm text-gray-500">Total Students</p>
        <h2 class="text-2xl font-bold">
            {{ \App\Models\Student::count() }}
        </h2>
    </div>

    <div class="bg-white p-4 rounded shadow border">
        <p class="text-sm text-gray-500">Total Subjects</p>
        <h2 class="text-2xl font-bold">
            {{ \App\Models\Subject::count() }}
        </h2>
    </div>

    <div class="bg-white p-4 rounded shadow border">
        <p class="text-sm text-gray-500">Active Programs</p>
        <h2 class="text-2xl font-bold">
            {{ \App\Models\Program::where('active',1)->count() }}
        </h2>
    </div>

</div>

<!-- ============================= -->
<!-- SEARCH + ADD -->
<!-- ============================= -->
<div class="flex items-center justify-between mb-3">
    <form method="GET" action="{{ route('dean.programs.index') }}">
        <input 
            type="text" 
            name="search" 
            value="{{ request('search') }}" 
            placeholder="Search programs..." 
            class="border rounded px-2 py-1"
        />
        <button type="submit">Search</button>
        @if(request('search'))
            <a href="{{ route('dean.programs.index') }}">Clear</a>
        @endif
    </form>

    <button @click="openAddModal()"
        class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded shadow text-sm">
        + Add Program
    </button>
</div>

<!-- ============================= -->
<!-- PROGRAM TABLE -->
<!-- ============================= -->
<div class="overflow-x-auto rounded shadow mt-6">
<table class="min-w-full bg-white border border-gray-400 rounded shadow">

<thead class="bg-gray-100 border-b-2 border-gray-400">
<tr>
    <th class="w-10 px-3 py-3 border"></th>
    <th class="px-4 py-3 font-semibold border">Program Name</th>
    <th class="px-4 py-3 font-semibold border">Code</th>
    <th class="px-4 py-3 font-semibold border">Students</th>
    <th class="px-4 py-3 font-semibold border">Subjects</th>
    <th class="px-4 py-3 font-semibold border">Status</th>
    <th class="px-4 py-3 font-semibold border">Actions</th>
</tr>
</thead>

<tbody>
@foreach($programs as $program)
<tr class="hover:bg-gray-50 transition">

    <td class="px-3 py-3 border-b text-center">
        <input type="checkbox">
    </td>

    <td class="px-4 py-3 border-b">
        {{ $program->name }}
    </td>

    <td class="px-4 py-3 border-b">
        {{ $program->code }}
    </td>

    <td class="px-4 py-3 border-b">
        {{ $program->students()->count() }}
    </td>

    <td class="px-4 py-3 border-b">
        {{ $program->subjects()->count() }}
    </td>

    <td class="px-4 py-3 border-b">
        @if($program->active)
            <span class="px-3 py-1 text-xs rounded bg-green-100 text-green-700">Active</span>
        @else
            <span class="px-3 py-1 text-xs rounded bg-red-100 text-red-700">Inactive</span>
        @endif
    </td>

    <td class="px-4 py-3 border-b space-x-2">

        <button type="button"
            class="text-gray-600 text-sm"
            @click="openViewModal(`{{ e($program->description) }}`)">
            View
        </button>

        <button type="button"
            class="text-blue-600 text-sm"
            @click="openEditModal({
                id: {{ $program->id }},
                name: `{{ e($program->name) }}`,
                code: `{{ e($program->code) }}`,
                description: `{{ e($program->description) }}`,
                active: {{ $program->active ? 'true' : 'false' }},
                education_node_id: {{ $program->education_node_id ?? 'null' }}
            })">
            Edit
        </button>

        <a href="{{ route('dean.programs.destroy', $program->id) }}"
            onclick="event.preventDefault(); if(confirm('Delete this program?')) document.getElementById('delete-form-{{ $program->id }}').submit();"
            class="text-red-600 text-sm">
            Delete
        </a>

        <form id="delete-form-{{ $program->id }}"
            action="{{ route('dean.programs.destroy', $program->id) }}"
            method="POST" style="display:none;">
            @csrf
            @method('DELETE')
        </form>

    </td>

</tr>
@endforeach

@if($programs->count() === 0)
<tr>
    <td colspan="7" class="py-8 text-center text-gray-500">
        No programs found.
    </td>
</tr>
@endif

</tbody>
</table>
</div>

<div class="mt-4">
    {{ $programs->links() }}
</div>

<!-- ============================= -->
<!-- VIEW MODAL -->
<!-- ============================= -->
<div x-show="viewModal"
     class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-40"
     x-cloak>

    <div class="bg-white w-96 p-6 rounded shadow-lg">
        <h2 class="text-lg font-bold mb-4">Program Description</h2>
        <p class="text-gray-700 whitespace-pre-line" x-text="viewDescription"></p>
        <div class="mt-4 text-right">
            <button @click="viewModal = false"
                class="bg-gray-600 text-white px-4 py-2 rounded text-sm">
                Close
            </button>
        </div>
    </div>
</div>

<!-- ============================= -->
<!-- ADD / EDIT MODAL -->
<!-- ============================= -->
<div x-show="addModal || editModal"
     class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-40"
     x-cloak>

    <div class="bg-white w-96 p-6 rounded shadow-lg">

        <h2 class="text-lg font-bold mb-4"
            x-text="editModal ? 'Edit Program' : 'Add Program'"></h2>

        <form method="POST"
              :action="editModal 
                ? '{{ url('dean/programs') }}/' + editProgram.id 
                : '{{ route('dean.programs.store') }}'">

            @csrf
            <template x-if="editModal">
                <input type="hidden" name="_method" value="PUT">
            </template>

            <div class="mb-3">
                <label class="block text-sm">Program Name</label>
                <input type="text" name="name" required
                       x-model="editProgram.name"
                       class="w-full border rounded px-3 py-2">
            </div>

            <div class="mb-3">
                <label class="block text-sm">Code</label>
                <input type="text" name="code" required
                       x-model="editProgram.code"
                       class="w-full border rounded px-3 py-2">
            </div>

            <div class="mb-3">
                <label class="block text-sm">Program Type / Category</label>
                <select name="education_node_id"
                        x-model="editProgram.education_node_id"
                        class="w-full border rounded px-3 py-2">
                    <option value="">— Select where this program belongs —</option>
                    @foreach ($programTypeOptions ?? [] as $opt)
                        <option value="{{ $opt['id'] }}">{{ $opt['label'] }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-500 mt-1">
                    Determines where this program appears under the Education Structure tree
                    (e.g., College / Undergraduate, Graduate Programs &gt; Master’s Degree).
                </p>
            </div>

            <div class="mb-3">
                <label class="block text-sm">Description</label>
                <textarea name="description"
                          x-model="editProgram.description"
                          class="w-full border rounded px-3 py-2"></textarea>
            </div>

            <div class="mb-4">
                <label class="block text-sm">Status</label>
                <select name="active"
                        x-model="editProgram.active"
                        class="w-full border rounded px-3 py-2">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>

            <div class="flex justify-end space-x-2">
                <button type="button"
                    @click="closeModal()"
                    class="bg-gray-500 text-white px-4 py-2 rounded text-sm">
                    Cancel
                </button>

                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
                    Save
                </button>
            </div>

        </form>

    </div>
</div>

</div>

<script>
function programPage() {
    return {
        addModal: false,
        editModal: false,
        viewModal: false,
        viewDescription: '',
        editProgram: {
            id: null,
            name: '',
            code: '',
            description: '',
            active: '1',
            education_node_id: '',
        },

        openAddModal() {
            this.editProgram = {
                id: null,
                name: '',
                code: '',
                description: '',
                active: '1',
                education_node_id: ''
            };
            this.addModal = true;
        },

        openEditModal(program) {
            this.editProgram = {
                id: program.id,
                name: program.name,
                code: program.code,
                description: program.description,
                active: program.active ? '1' : '0',
                education_node_id: program.education_node_id ?? ''
            };
            this.editModal = true;
        },

        openViewModal(description) {
            this.viewDescription = description;
            this.viewModal = true;
        },

        closeModal() {
            this.addModal = false;
            this.editModal = false;
            this.viewModal = false;
        }
    }
}
</script>

@endsection