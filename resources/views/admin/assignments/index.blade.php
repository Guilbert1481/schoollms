@extends('layouts.app')

@section('content')

<div x-data="assignmentPage()" class="container mx-auto">

    <h1 class="text-2xl font-bold mb-6">Assignment Management</h1>

    <!-- ========================= -->
    <!-- TABS -->
    <!-- ========================= -->

    <div class="flex space-x-6 border-b mb-6">
        <button @click="tab = 'colleges'"
            :class="tab === 'colleges' ? activeTab : inactiveTab"
            class="pb-2">
            Colleges
        </button>

        <button @click="tab = 'programs'"
            :class="tab === 'programs' ? activeTab : inactiveTab"
            class="pb-2">
            Programs
        </button>
    </div>

    <!-- ========================= -->
    <!-- COLLEGES TABLE -->
    <!-- ========================= -->

    <div x-show="tab === 'colleges'">

        <table class="min-w-full border">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-4 py-2 text-left">College</th>
                    <th class="border px-4 py-2 text-left">Assigned Dean</th>
                    <th class="border px-4 py-2 text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($colleges as $college)
                <tr>
                    <td class="border px-4 py-2">
                        {{ $college->name }}
                    </td>

                    <td class="border px-4 py-2">
                        {{ $college->dean->name ?? 'Unassigned' }}
                    </td>

                    <td class="border px-4 py-2 text-center">
                        <button
                            @click="openModal(
                                'college',
                                {{ $college->id }},
                                '{{ $college->name }}',
                                {{ $college->dean_id ?? 'null' }}
                            )"
                            class="bg-blue-600 text-white px-3 py-1 rounded text-sm">
                            {{ $college->dean_id ? 'Update' : 'Assign' }}
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

    </div>

    <!-- ========================= -->
    <!-- PROGRAMS TABLE -->
    <!-- ========================= -->

    <div x-show="tab === 'programs'" x-cloak>

        <table class="min-w-full border">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-4 py-2 text-left">Program</th>
                    <th class="border px-4 py-2 text-left">College</th>
                    <th class="border px-4 py-2 text-left">Assigned Head</th>
                    <th class="border px-4 py-2 text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($programs as $program)
                <tr>
                    <td class="border px-4 py-2">
                        {{ $program->name }}
                    </td>

                    <td class="border px-4 py-2">
                        {{ $program->college->name }}
                    </td>

                    <td class="border px-4 py-2">
                        {{ $program->programHead->name ?? 'Unassigned' }}
                    </td>

                    <td class="border px-4 py-2 text-center">
                        <button
                            @click="openModal(
                                'program',
                                {{ $program->id }},
                                '{{ $program->name }}',
                                {{ $program->program_head_id ?? 'null' }}
                            )"
                            class="bg-green-600 text-white px-3 py-1 rounded text-sm">
                            {{ $program->program_head_id ? 'Update' : 'Assign' }}
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

    </div>

    <!-- ========================= -->
    <!-- REUSABLE MODAL -->
    <!-- ========================= -->

    <div x-show="showModal"
         class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-40"
         x-cloak>

        <div class="bg-white w-96 p-6 rounded shadow">

            <h2 class="text-lg font-bold mb-4" x-text="modalTitle"></h2>

            <form method="POST" :action="formAction">
                @csrf

                <input type="hidden" name="college_id" x-show="type === 'college'" :value="itemId">
                <input type="hidden" name="program_id" x-show="type === 'program'" :value="itemId">

                <div class="mb-4">

                    <label class="block mb-1" x-text="selectLabel"></label>

                    <select
                        :name="type === 'college' ? 'dean_id' : 'program_head_id'"
                        class="w-full border px-3 py-2 rounded">

                        <option value="">-- Unassign --</option>

                        <template x-if="type === 'college'">
                            @foreach($deans as $dean)
                                <option value="{{ $dean->id }}">
                                    {{ $dean->name }}
                                </option>
                            @endforeach
                        </template>

                        <template x-if="type === 'program'">
                            @foreach($programHeads as $head)
                                <option value="{{ $head->id }}">
                                    {{ $head->name }}
                                </option>
                            @endforeach
                        </template>

                    </select>
                </div>

                <div class="flex justify-end space-x-2">
                    <button type="button"
                        @click="showModal = false"
                        class="bg-gray-500 text-white px-4 py-2 rounded">
                        Cancel
                    </button>

                    <button type="submit"
                        class="bg-blue-600 text-white px-4 py-2 rounded">
                        Save
                    </button>
                </div>

            </form>

        </div>
    </div>

</div>


<script>
function assignmentPage() {
    return {
        tab: 'colleges',
        showModal: false,
        type: '',
        itemId: null,
        selectedId: null,
        modalTitle: '',
        selectLabel: '',
        formAction: '',
        activeTab: 'border-b-2 border-blue-600 font-semibold',
        inactiveTab: '',

        openModal(type, id, name, assignedId) {

            this.type = type;
            this.itemId = id;
            this.selectedId = assignedId;
            this.showModal = true;

            if (type === 'college') {
                this.modalTitle = 'Assign Dean to ' + name;
                this.selectLabel = 'Select Dean';
                this.formAction = "{{ route('admin.assign.dean') }}";
            } else {
                this.modalTitle = 'Assign Program Head to ' + name;
                this.selectLabel = 'Select Program Head';
                this.formAction = "{{ route('admin.assign.programHead') }}";
            }

            // Wait for DOM update then set selected value
            this.$nextTick(() => {
                const select = document.querySelector('select[name="' + 
                    (type === 'college' ? 'dean_id' : 'program_head_id') + '"]');

                if (select) {
                    select.value = assignedId ?? '';
                }
            });
        }
    }
}
</script>
@endsection