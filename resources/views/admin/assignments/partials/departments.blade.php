@php
use Illuminate\Support\Facades\DB;
@endphp

<h2 class="text-lg font-bold mb-4">Departments</h2>

<x-table.table
    tableKey="departments"
    :columns="$columns"
    :data="$data"
    :actions="config('tables.table-actions.departments')"
    createModal="departmentCreateModal"
    editModal="departmentEditModal"
    deleteRoute="school.settings.master-data.organization.destroyDepartments"
/>

<x-modal.form id="departmentCreateModal" title="Add Department">

    <form method="POST"
          action="{{ route('school.settings.master-data.organization.storeDepartments') }}">
        @csrf

        @php
            $config = config("tables.tables.departments");
            $relations = $config['relations'] ?? [];
        @endphp

        @foreach($formColumns as $column)
            <div class="mb-3">
                <label class="block text-sm mb-1">
                    {{ $labels[$column] ?? ucwords(str_replace('_',' ', $column)) }}
                </label>

                @if(isset($relations[$column]) && is_array($relations[$column]))
                    @php
                        $relation = $relations[$column];
                        $query = DB::table($relation['table']);

                        if(isset($relation['join_profiles']) && $relation['join_profiles']){
                            $query->join('profiles', 'profiles.user_id', '=', $relation['table'].'.id')
                                ->select(
                                    $relation['table'].'.id',
                                    DB::raw("CONCAT(profiles.first_name, ' ', profiles.last_name) as label")
                                );
                        }

                        if(isset($relation['whereSchool']) && $relation['whereSchool']){
                            $query->where($relation['table'].'.school_id', auth()->user()->school_id);
                        }

                        $options = $query->get();
                    @endphp

                    <select name="{{ $column }}" class="border p-2 w-full">
                        <option value="">Select...</option>
                        @foreach($options as $option)
                            <option value="{{ $option->id }}">{{ $option->label }}</option>
                        @endforeach
                    </select>
                @else
                    <input type="text" name="{{ $column }}" class="border p-2 w-full">
                @endif
            </div>
        @endforeach

    </form>

</x-modal.form>

<x-modal.form id="departmentEditModal" title="Edit Department">

    <form method="POST" action="{{ route('school.system.dynamic.update') }}">
        @csrf
        @method('PUT')

        <input type="hidden" name="table" value="departments">
        <input type="hidden" name="id">

        @foreach($formColumns as $column)
            <div class="mb-3">
                <label class="block text-sm mb-1">
                    {{ $labels[$column] ?? ucwords(str_replace('_',' ', $column)) }}
                </label>

                @if(isset($relations[$column]) && is_array($relations[$column]))
                    @php
                        $relation = $relations[$column];
                        $query = DB::table($relation['table']);

                        if(isset($relation['join_profiles']) && $relation['join_profiles']){
                            $query->join('profiles', 'profiles.user_id', '=', $relation['table'].'.id')
                                ->select(
                                    $relation['table'].'.id',
                                    DB::raw("CONCAT(profiles.first_name, ' ', profiles.last_name) as label")
                                );
                        }

                        if(isset($relation['whereSchool']) && $relation['whereSchool']){
                            $query->where($relation['table'].'.school_id', auth()->user()->school_id);
                        }

                        $options = $query->get();
                    @endphp

                    <select name="{{ $column }}" class="border p-2 w-full">
                        <option value="">Select...</option>
                        @foreach($options as $option)
                            <option value="{{ $option->id }}">{{ $option->label }}</option>
                        @endforeach
                    </select>
                @else
                    <input type="text" name="{{ $column }}" class="border p-2 w-full">
                @endif
            </div>
        @endforeach

    </form>

</x-modal.form>
