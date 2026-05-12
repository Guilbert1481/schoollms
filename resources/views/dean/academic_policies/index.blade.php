@extends('layouts.app')

@section('content')
<div class="container mx-auto px-6 py-6">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Academic Policies</h1>
            <p class="text-sm text-slate-500 mt-1">
                Configure unit limits, payment gating, overload thresholds, and section capacity overrides.
                The most-specific match (school → level → program → term) wins at validation time.
            </p>
        </div>
        <a href="{{ route('dean.academic_policies.create') }}"
           class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded shadow text-sm font-semibold">
            + New Policy
        </a>
    </div>

    @if (session('success'))
        <div class="mb-4 p-3 bg-green-100 border border-green-300 text-green-800 rounded">
            {{ session('success') }}
        </div>
    @endif

    {{-- ----------------- FILTERS ----------------- --}}
    <form method="GET" class="bg-white border rounded p-4 mb-4 grid grid-cols-1 md:grid-cols-5 gap-3 text-sm">
        <select name="education_level" class="border rounded px-2 py-1.5">
            <option value="">All Levels</option>
            @foreach ($levels as $lvl)
                <option value="{{ $lvl }}" @selected(($filters['education_level'] ?? '') === $lvl)>
                    {{ ucfirst(str_replace('_',' ', $lvl)) }}
                </option>
            @endforeach
        </select>
        <select name="program_id" class="border rounded px-2 py-1.5">
            <option value="">All Programs</option>
            @foreach ($programs as $p)
                <option value="{{ $p->id }}" @selected((string)($filters['program_id'] ?? '') === (string)$p->id)>
                    {{ $p->code }} — {{ $p->name }}
                </option>
            @endforeach
        </select>
        <select name="term_id" class="border rounded px-2 py-1.5">
            <option value="">All Terms</option>
            @foreach ($terms as $t)
                <option value="{{ $t->id }}" @selected((string)($filters['term_id'] ?? '') === (string)$t->id)>
                    {{ $t->name }}
                </option>
            @endforeach
        </select>
        <select name="status" class="border rounded px-2 py-1.5">
            <option value="">All Statuses</option>
            <option value="active"   @selected(($filters['status'] ?? '') === 'active')>Active</option>
            <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
        </select>
        <div class="flex gap-2">
            <button class="px-3 py-1.5 bg-slate-700 text-white rounded">Filter</button>
            <a href="{{ route('dean.academic_policies.index') }}" class="px-3 py-1.5 bg-slate-200 rounded">Clear</a>
        </div>
    </form>

    {{-- ----------------- TABLE ----------------- --}}
    <div class="bg-white border rounded shadow overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 border-b text-slate-600 text-xs uppercase tracking-wide">
                <tr>
                    <th class="px-4 py-3 text-left">Scope</th>
                    <th class="px-4 py-3 text-left">Units (min/max)</th>
                    <th class="px-4 py-3 text-left">Subjects</th>
                    <th class="px-4 py-3 text-left">Overload ≥</th>
                    <th class="px-4 py-3 text-left">Payment Gate</th>
                    <th class="px-4 py-3 text-left">Effective</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($policies as $pol)
                    <tr class="border-b hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <div class="font-semibold text-slate-800">
                                {{ $pol->education_level ? ucfirst(str_replace('_',' ', $pol->education_level)) : 'All Levels' }}
                            </div>
                            <div class="text-xs text-slate-500 mt-0.5">
                                {{ $pol->program ? ($pol->program->code.' — '.$pol->program->name) : 'All Programs' }}
                                · {{ $pol->term?->name ?: 'All Terms' }}
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="font-mono">
                                {{ $pol->min_units !== null ? rtrim(rtrim($pol->min_units, '0'), '.') : '—' }}
                                /
                                {{ $pol->max_units !== null ? rtrim(rtrim($pol->max_units, '0'), '.') : '—' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">{{ $pol->max_subjects ?? '—' }}</td>
                        <td class="px-4 py-3">
                            {{ $pol->overload_threshold_units !== null ? rtrim(rtrim($pol->overload_threshold_units, '0'), '.') : '—' }}
                        </td>
                        <td class="px-4 py-3">
                            @if ($pol->requires_payment_to_enrol)
                                <span class="text-amber-700 font-semibold">{{ rtrim(rtrim($pol->min_payment_percent, '0'), '.') }}% required</span>
                            @else
                                <span class="text-slate-400">Not required</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-600">
                            {{ $pol->effective_from?->format('Y-m-d') ?: '—' }}
                            <br>to {{ $pol->effective_to?->format('Y-m-d') ?: '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('dean.academic_policies.toggle', $pol->id) }}">
                                @csrf
                                <button class="px-2 py-1 rounded text-xs font-bold {{ $pol->is_active ? 'bg-green-100 text-green-800' : 'bg-slate-200 text-slate-600' }}">
                                    {{ $pol->is_active ? 'ACTIVE' : 'INACTIVE' }}
                                </button>
                            </form>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('dean.academic_policies.edit', $pol->id) }}"
                               class="text-indigo-600 hover:underline mr-3">Edit</a>
                            <form method="POST"
                                  action="{{ route('dean.academic_policies.destroy', $pol->id) }}"
                                  class="inline"
                                  onsubmit="return confirm('Delete this policy?');">
                                @csrf @method('DELETE')
                                <button class="text-red-600 hover:underline">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-10 text-center text-slate-500">
                            No academic policies match these filters.
                            <a href="{{ route('dean.academic_policies.create') }}" class="text-indigo-600 underline ml-1">Create one</a>.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $policies->links() }}</div>
</div>
@endsection
