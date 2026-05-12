{{-- Curricula list partial (no @extends; safe to @include) --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-bold text-slate-800">Curricula</h2>
        <p class="text-sm text-slate-500 mt-1">
            Define the course-of-study for each program. Choose <strong>2 terms/year (bi-semester)</strong> or
            <strong>3 terms/year (trimester)</strong>; the wizard and seeders will use the matching term layout.
        </p>
    </div>
    <a href="{{ route('dean.curricula.create') }}"
       class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded shadow text-sm font-semibold">
        + New Curriculum
    </a>
</div>

@if (session('success'))
    <div class="mb-4 p-3 bg-green-100 border border-green-300 text-green-800 rounded">
        {{ session('success') }}
    </div>
@endif

{{-- Filters --}}
<form method="GET" class="bg-white border rounded p-4 mb-4 grid grid-cols-1 md:grid-cols-4 gap-3 text-sm">
    <input type="hidden" name="tab" value="curricula">
    <select name="program_id" class="border rounded px-2 py-1.5">
        <option value="">All Programs</option>
        @foreach ($programs as $p)
            <option value="{{ $p->id }}" @selected((string)($filters['program_id'] ?? '') === (string)$p->id)>
                {{ $p->code }} — {{ $p->name }}
            </option>
        @endforeach
    </select>
    <select name="status" class="border rounded px-2 py-1.5">
        <option value="">All Statuses</option>
        <option value="active"   @selected(($filters['status'] ?? '') === 'active')>Active</option>
        <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
    </select>
    <div class="flex gap-2 md:col-span-2">
        <button class="px-3 py-1.5 bg-slate-700 text-white rounded">Filter</button>
        <a href="{{ route('dean.curricula-panel.index') }}?tab=curricula" class="px-3 py-1.5 bg-slate-200 rounded">Clear</a>
    </div>
</form>

{{-- Table --}}
<div class="bg-white border rounded shadow overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 border-b text-slate-600 text-xs uppercase tracking-wide">
            <tr>
                <th class="px-4 py-3 text-left">Program / Curriculum</th>
                <th class="px-4 py-3 text-left">Version</th>
                <th class="px-4 py-3 text-left">Term Mode</th>
                <th class="px-4 py-3 text-left">Subjects</th>
                <th class="px-4 py-3 text-left">Effective</th>
                <th class="px-4 py-3 text-left">Status</th>
                <th class="px-4 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($curricula as $cur)
                <tr class="border-b hover:bg-slate-50">
                    <td class="px-4 py-3">
                        <div class="font-semibold text-slate-800">{{ $cur->name }}</div>
                        <div class="text-xs text-slate-500 mt-0.5">
                            {{ $cur->program?->code }} — {{ $cur->program?->name }}
                        </div>
                    </td>
                    <td class="px-4 py-3 font-mono text-xs">{{ $cur->version }}</td>
                    <td class="px-4 py-3">
                        @if ((int) $cur->terms_per_year === 3)
                            <span class="px-2 py-1 rounded bg-purple-100 text-purple-800 text-xs font-bold">Trimester (3)</span>
                        @else
                            <span class="px-2 py-1 rounded bg-blue-100 text-blue-800 text-xs font-bold">Bi-semester (2)</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">{{ $cur->subjects_count }}</td>
                    <td class="px-4 py-3 text-xs text-slate-600">
                        {{ $cur->effective_from?->format('Y-m-d') ?: '—' }}
                        <br>to {{ $cur->effective_to?->format('Y-m-d') ?: '—' }}
                    </td>
                    <td class="px-4 py-3">
                        <form method="POST" action="{{ route('dean.curricula.toggle', $cur->id) }}">
                            @csrf
                            <button class="px-2 py-1 rounded text-xs font-bold {{ $cur->is_active ? 'bg-green-100 text-green-800' : 'bg-slate-200 text-slate-600' }}">
                                {{ $cur->is_active ? 'ACTIVE' : 'INACTIVE' }}
                            </button>
                        </form>
                    </td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <a href="{{ route('dean.curricula.show', $cur->id) }}"
                           class="text-slate-700 hover:underline mr-3">View Subjects</a>
                        <a href="{{ route('dean.curricula.edit', $cur->id) }}"
                           class="text-indigo-600 hover:underline mr-3">Edit</a>
                        <form method="POST"
                              action="{{ route('dean.curricula.destroy', $cur->id) }}"
                              class="inline"
                              onsubmit="return confirm('Delete this curriculum?');">
                            @csrf @method('DELETE')
                            <button class="text-red-600 hover:underline">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-10 text-center text-slate-500">
                        No curricula yet.
                        <a href="{{ route('dean.curricula.create') }}" class="text-indigo-600 underline ml-1">Create one</a>.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $curricula->links() }}</div>
