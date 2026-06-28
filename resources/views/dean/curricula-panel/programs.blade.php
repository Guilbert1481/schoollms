@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto p-6 space-y-6">

    @include('dean.curricula-panel.partials._header')

    @include('partials.tabs', [
        'tabs' => config('tabs.tabs.curricula_panel'),
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
        <h2 class="text-lg font-bold">Program Subjects</h2>
    </div>

    {{-- Reusable table with extra header buttons via slot --}}
    <x-table.table
        tableKey="program_subjects"
        :columns="$columns"
        :data="$data"
        :actions="[
            [
                'name'    => 'edit',
                'label'   => 'Edit',
                'class'   => 'bg-blue-500 text-white',
                'type'    => 'js',
                'handler' => 'editProgramSubject',
            ],
            [
                'name'  => 'delete',
                'label' => 'Remove',
                'class' => 'bg-red-500 text-white',
                'type'  => 'delete',
            ],
        ]"
        :deleteRoute="'dean.curricula-panel.program-subjects.destroy'"
    >
        {{-- Program selector --}}
        <form method="GET" action="{{ route('dean.curricula-panel.programs') }}" class="flex items-center gap-2">
            <select name="program_id"
                    onchange="this.form.submit()"
                    class="border border-gray-300 rounded px-2 py-2 text-sm">
                <option value="">Select Program…</option>
                @foreach($programs as $p)
                    <option value="{{ $p->id }}" @selected((int)$programId === (int)$p->id)>
                        {{ $p->code }} — {{ $p->name }}
                    </option>
                @endforeach
            </select>
        </form>

        {{-- Add Subject --}}
        <button type="button"
                onclick="openModal('addProgramSubjectModal')"
                @disabled(! $programId)
                class="px-4 py-2 rounded text-sm text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50">
            Add Subject
        </button>

        {{-- Import CSV --}}
        <button type="button"
                onclick="openModal('importProgramSubjectsModal')"
                @disabled(! $programId)
                class="px-4 py-2 rounded text-sm text-white bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50">
            Import CSV
        </button>
    </x-table.table>

    {{-- Add Subject modal (draggable) --}}
    <x-modal.form id="addProgramSubjectModal" title="Add Subject to Program">
        <form method="POST" action="{{ route('dean.curricula-panel.program-subjects.store') }}">
            @csrf
            <input type="hidden" name="program_id" value="{{ $programId }}">

            <div class="mb-3">
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-sm">Subjects <span class="text-xs text-gray-500">(select one or more)</span></label>
                    <span class="text-xs text-gray-700">
                        <span id="subjectSelectedCount">0</span> selected
                        / {{ $availableSubjects->count() }} available
                    </span>
                </div>
                <div class="mb-2 flex items-center gap-2">
                    <input type="text"
                           id="subjectFilter"
                           placeholder="Filter subjects…"
                           class="border p-2 w-full text-sm"
                           onkeyup="filterSubjectCheckboxes(this.value)">
                </div>
                <div id="subjectCheckboxList" class="border rounded max-h-60 overflow-y-auto p-2 space-y-1">
                    @forelse($availableSubjects as $s)
                        <label class="flex items-start gap-2 text-sm py-1 px-1 hover:bg-gray-50 rounded cursor-pointer subject-row">
                            <input type="checkbox" name="subject_ids[]" value="{{ $s->id }}" class="mt-0.5" onchange="updateSubjectSelectedCount()">
                            <span><strong>{{ $s->code }}</strong> — {{ $s->name }}</span>
                        </label>
                    @empty
                        <div class="text-sm text-gray-500 italic">No available subjects.</div>
                    @endforelse
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="mb-3">
                    <label class="block text-sm mb-1">Year Level</label>
                    <select name="year_level" class="border p-2 w-full" required>
                        @for($y = 1; $y <= 5; $y++)
                            <option value="{{ $y }}">Year {{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="mb-3">
                    <label class="block text-sm mb-1">Semester</label>
                    <select name="semester_number" class="border p-2 w-full" required>
                        <option value="1">1st</option>
                        <option value="2">2nd</option>
                        <option value="3">3rd</option>
                        <option value="4">4th</option>
                        <option value="5">Summer</option>
                    </select>
                </div>
            </div>
        </form>
    </x-modal.form>

    {{-- Import CSV modal --}}
    <x-modal.form id="importProgramSubjectsModal" title="Import Program Subjects (CSV)">
        <form method="POST"
              action="{{ route('dean.curricula-panel.program-subjects.import') }}"
              enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="program_id" value="{{ $programId }}">

            <p class="text-sm text-gray-700 mb-3">
                CSV must have a header row with columns:
                <code class="bg-gray-100 px-1 rounded">subject_code</code>,
                <code class="bg-gray-100 px-1 rounded">year_level</code>,
                <code class="bg-gray-100 px-1 rounded">semester_number</code>
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
<pre class="bg-gray-50 border rounded p-2 mt-1">subject_code,year_level,semester_number,is_active
G1-MATH,1,1,1
G1-FIL,1,1,1
G2-MATH,2,1,1</pre>
            </details>
        </form>
    </x-modal.form>

    {{-- Edit Program-Subject modal (draggable) --}}
    <x-modal.form id="editProgramSubjectModal" title="Edit Program Subject">
        <form id="editProgramSubjectForm" method="POST" action="">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="block text-sm mb-1">Subject</label>
                <input type="text" id="editProgramSubjectName" class="border p-2 w-full bg-gray-50" disabled>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="mb-3">
                    <label class="block text-sm mb-1">Year Level</label>
                    <select name="year_level" id="editProgramSubjectYear" class="border p-2 w-full" required>
                        @for($y = 1; $y <= 5; $y++)
                            <option value="{{ $y }}">Year {{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="mb-3">
                    <label class="block text-sm mb-1">Semester</label>
                    <select name="semester_number" id="editProgramSubjectSem" class="border p-2 w-full" required>
                        <option value="1">1st</option>
                        <option value="2">2nd</option>
                        <option value="3">3rd</option>
                        <option value="4">4th</option>
                        <option value="5">Summer</option>
                    </select>
                </div>
            </div>
        </form>
    </x-modal.form>

    <script>
        // Pivot row data for the edit modal
        window.__programSubjects = @json($data->keyBy('id'));

        function editProgramSubject(pivotId) {
            var row = window.__programSubjects[pivotId];
            if (!row) { alert('Row not found.'); return; }

            var form = document.getElementById('editProgramSubjectForm');
            form.action = "{{ url('dean/curricula-panel/program-subjects') }}/" + pivotId;

            document.getElementById('editProgramSubjectName').value = (row.code || '') + ' — ' + (row.name || '');
            document.getElementById('editProgramSubjectYear').value = row.year_level;
            document.getElementById('editProgramSubjectSem').value  = row.semester_number;

            openModal('editProgramSubjectModal');
        }
        function filterSubjectCheckboxes(term) {
            term = (term || '').toLowerCase();
            document.querySelectorAll('#subjectCheckboxList .subject-row').forEach(function (row) {
                row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
            });
        }

        function updateSubjectSelectedCount() {
            var count = document.querySelectorAll('#subjectCheckboxList input[type="checkbox"]:checked').length;
            var el = document.getElementById('subjectSelectedCount');
            if (el) el.textContent = count;
        }
    </script>

</div>
@endsection
