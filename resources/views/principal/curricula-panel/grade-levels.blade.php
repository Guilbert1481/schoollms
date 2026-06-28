@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto p-6 space-y-6">

    @include('principal.curricula-panel.partials._header')

    @include('partials.tabs', [
        'tabs' => config('tabs.tabs.principal_curricula_panel'),
    ])

    @if(session('success'))
        <div class="bg-green-100 border border-green-300 text-green-700 px-4 py-3 rounded">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-100 border border-red-300 text-red-700 px-4 py-3 rounded">
            <ul class="list-disc pl-5 text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="flex items-center justify-between">
        <h2 class="text-lg font-bold">Grade Level Subjects</h2>
    </div>

    <x-table.table
        tableKey="grade_level_subjects"
        :columns="$columns"
        :data="$data"
        :actions="[
            [
                'name'  => 'delete',
                'label' => 'Remove',
                'class' => 'bg-red-500 text-white',
                'type'  => 'delete',
            ],
        ]"
        :deleteRoute="'principal.curricula-panel.grade-level-subjects.destroy'"
    >
        {{-- Grade level selector --}}
        <form method="GET" action="{{ route('principal.curricula-panel.grade-levels') }}" class="flex items-center gap-2">
            <select name="education_node_id"
                    onchange="this.form.submit()"
                    class="border border-gray-300 rounded px-2 py-2 text-sm">
                <option value="">Select Grade Level…</option>
                @foreach($gradeLevels as $g)
                    <option value="{{ $g->id }}" @selected((int)$nodeId === (int)$g->id)>
                        {{ $g->label ?? $g->name }}
                    </option>
                @endforeach
            </select>
        </form>

        {{-- Add Subject --}}
        <button type="button"
                onclick="openModal('addGradeLevelSubjectModal')"
                @disabled(! $nodeId)
                class="px-4 py-2 rounded text-sm text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50">
            Add Subject
        </button>

        {{-- Import CSV --}}
        <button type="button"
                onclick="openModal('importGradeLevelSubjectsModal')"
                @disabled(! $nodeId)
                class="px-4 py-2 rounded text-sm text-white bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50">
            Import CSV
        </button>
    </x-table.table>

    {{-- Add Subject modal --}}
    <x-modal.form id="addGradeLevelSubjectModal" title="Add Subject to Grade Level">
        <form method="POST" action="{{ route('principal.curricula-panel.grade-level-subjects.store') }}">
            @csrf
            <input type="hidden" name="education_node_id" value="{{ $nodeId }}">

            <div class="mb-3">
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-sm">Subjects <span class="text-xs text-gray-500">(select one or more)</span></label>
                    <span class="text-xs text-gray-700">
                        <span id="glSubjectSelectedCount">0</span> selected
                        / {{ $availableSubjects->count() }} available
                    </span>
                </div>
                <div class="mb-2 flex items-center gap-2">
                    <input type="text"
                           id="glSubjectFilter"
                           placeholder="Filter subjects…"
                           class="border p-2 w-full text-sm"
                           onkeyup="filterGlSubjectCheckboxes(this.value)">
                </div>
                <div id="glSubjectCheckboxList" class="border rounded max-h-60 overflow-y-auto p-2 space-y-1">
                    @forelse($availableSubjects as $s)
                        <label class="flex items-start gap-2 text-sm py-1 px-1 hover:bg-gray-50 rounded cursor-pointer gl-subject-row">
                            <input type="checkbox" name="subject_ids[]" value="{{ $s->id }}" class="mt-0.5" onchange="updateGlSubjectSelectedCount()">
                            <span><strong>{{ $s->code }}</strong> — {{ $s->name }}</span>
                        </label>
                    @empty
                        <div class="text-sm text-gray-500 italic">No available subjects.</div>
                    @endforelse
                </div>
            </div>
        </form>
    </x-modal.form>

    {{-- Import CSV modal --}}
    <x-modal.form id="importGradeLevelSubjectsModal" title="Import Grade Level Subjects (CSV)">
        <form method="POST"
              action="{{ route('principal.curricula-panel.grade-level-subjects.import') }}"
              enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="education_node_id" value="{{ $nodeId }}">

            <p class="text-sm text-gray-700 mb-3">
                CSV must include a header row with at least the
                <code class="bg-gray-100 px-1 rounded">subject_code</code> column
                (optional <code class="bg-gray-100 px-1 rounded">is_active</code>).
                Subjects are matched by their <strong>code</strong> within this school.
            </p>

            <div class="mb-3">
                <label class="block text-sm mb-1">CSV File</label>
                <input type="file" name="csv" accept=".csv,text/csv" required
                       class="border p-2 w-full text-sm">
            </div>

            <details class="text-xs text-gray-600 mb-2">
                <summary class="cursor-pointer">Sample CSV</summary>
<pre class="bg-gray-50 border rounded p-2 mt-1">subject_code,is_active
G1-MATH,1
G1-FIL,1</pre>
            </details>
        </form>
    </x-modal.form>

    <script>
        function filterGlSubjectCheckboxes(term) {
            term = (term || '').toLowerCase();
            document.querySelectorAll('#glSubjectCheckboxList .gl-subject-row').forEach(function (row) {
                row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
            });
        }
        function updateGlSubjectSelectedCount() {
            var count = document.querySelectorAll('#glSubjectCheckboxList input[type="checkbox"]:checked').length;
            var el = document.getElementById('glSubjectSelectedCount');
            if (el) el.textContent = count;
        }
    </script>

</div>
@endsection
