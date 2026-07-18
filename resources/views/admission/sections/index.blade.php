@extends('layouts.app')

@section('content')
<div class="p-6">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-extrabold">Sections — Publish for Enrolment</h1>

        <form method="GET" class="flex items-center gap-2">
            <label class="text-xs font-bold">Term</label>
            <select name="term_id" onchange="this.form.submit()"
                    class="border rounded-lg px-2 py-1 text-sm">
                @foreach($terms as $t)
                    <option value="{{ $t->id }}" @selected($termId == $t->id)>
                        {{ $t->name }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>

    @if(session('success'))
        <div class="mb-3 rounded-lg bg-emerald-50 border border-emerald-200 px-3 py-2 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-3 rounded-lg bg-rose-50 border border-rose-200 px-3 py-2 text-sm text-rose-700">
            {{ session('error') }}
        </div>
    @endif

    @if($termId)
        <div class="grid grid-cols-2 gap-4 mb-4">
            {{-- Auto-generate from program_subjects --}}
            <form method="POST" action="{{ route('admission.sections.auto-generate') }}"
                  class="rounded-2xl border bg-white p-4"
                  onsubmit="return confirm('Auto-generate sections from active program_subjects for this term?');">
                @csrf
                <input type="hidden" name="term_id" value="{{ $termId }}">
                <h2 class="font-bold mb-2">Auto-generate from Curriculum</h2>
                <p class="text-xs text-slate-500 mb-3">
                    Creates one draft section per (program, year level) cohort using active program_subjects.
                </p>
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div>
                        <label class="text-xs font-bold">Sections per cohort</label>
                        <input type="number" min="1" max="5" name="sections_per_cohort" value="1"
                               class="w-full border rounded-lg px-2 py-1.5">
                    </div>
                    <div>
                        <label class="text-xs font-bold">Default capacity</label>
                        <input type="number" min="1" max="500" name="capacity" value="40"
                               class="w-full border rounded-lg px-2 py-1.5">
                    </div>
                </div>
                <button class="mt-3 px-3 py-1.5 bg-indigo-600 text-white text-sm rounded-lg font-bold">
                    Auto-generate
                </button>
            </form>

            {{-- Manual create --}}
            <form method="POST" action="{{ route('admission.sections.store') }}"
                  class="rounded-2xl border bg-white p-4">
                @csrf
                <input type="hidden" name="term_id" value="{{ $termId }}">
                <h2 class="font-bold mb-2">Create Section Manually</h2>
                <div class="grid grid-cols-3 gap-2 text-sm">
                    <div class="col-span-2">
                        <label class="text-xs font-bold">Program</label>
                        <select name="program_id" required class="w-full border rounded-lg px-2 py-1.5">
                            <option value="">—</option>
                            @foreach($programs as $p)
                                <option value="{{ $p->id }}">{{ $p->code }} — {{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-bold">Year</label>
                        <input type="number" min="1" max="8" name="year_level" required
                               class="w-full border rounded-lg px-2 py-1.5">
                    </div>
                    <div class="col-span-2">
                        <label class="text-xs font-bold">Section Name</label>
                        <input type="text" name="name" required maxlength="50" placeholder="e.g. BSEDMATH-2A"
                               class="w-full border rounded-lg px-2 py-1.5">
                    </div>
                    <div>
                        <label class="text-xs font-bold">Capacity</label>
                        <input type="number" min="1" max="500" name="capacity" value="40"
                               class="w-full border rounded-lg px-2 py-1.5">
                    </div>
                </div>
                <button class="mt-3 px-3 py-1.5 bg-slate-700 text-white text-sm rounded-lg font-bold">
                    Create Draft
                </button>
            </form>
        </div>
    @endif

    <div class="rounded-2xl border bg-white">
        <div class="flex items-center justify-between px-4 py-3 border-b">
            <p class="text-sm text-slate-600">
                {{ $sections->where('status', 'draft')->count() }} draft •
                {{ $sections->where('status', 'published')->count() }} published •
                {{ $sections->count() }} total
            </p>

            @if($termId && $sections->where('status', 'draft')->count())
                <form method="POST" action="{{ route('admission.sections.publish-all') }}"
                      onsubmit="return confirm('Publish all draft sections for this term?');">
                    @csrf
                    <input type="hidden" name="term_id" value="{{ $termId }}">
                    <button class="px-3 py-1.5 bg-indigo-600 text-white text-sm rounded-lg">
                        Publish all drafts
                    </button>
                </form>
            @endif
        </div>

        <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="text-left text-slate-500">
                <tr>
                    <th class="px-4 py-2">Program</th>
                    <th class="px-4 py-2">Year</th>
                    <th class="px-4 py-2">Section</th>
                    <th class="px-4 py-2">Capacity</th>
                    <th class="px-4 py-2">Status</th>
                    <th class="px-4 py-2 text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sections as $s)
                    <tr class="border-t">
                        <td class="px-4 py-2">{{ optional($s->program)->code ?? optional($s->program)->name ?? '—' }}</td>
                        <td class="px-4 py-2">Y{{ $s->year_level }}</td>
                        <td class="px-4 py-2 font-bold">{{ $s->name }}</td>
                        <td class="px-4 py-2">{{ $s->capacity ?: '—' }}</td>
                        <td class="px-4 py-2">
                            @if($s->status === 'published')
                                <span class="inline-flex items-center rounded-full bg-emerald-100 text-emerald-700 px-2 py-0.5 text-xs font-bold">Published</span>
                            @elseif($s->status === 'archived')
                                <span class="inline-flex items-center rounded-full bg-slate-200 text-slate-700 px-2 py-0.5 text-xs font-bold">Archived</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-amber-100 text-amber-700 px-2 py-0.5 text-xs font-bold">Draft</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-right">
                            @if($s->status === 'published')
                                <form method="POST" action="{{ route('admission.sections.unpublish', $s) }}" class="inline">
                                    @csrf
                                    <button class="text-xs text-slate-600 hover:underline">Unpublish</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admission.sections.publish', $s) }}" class="inline">
                                    @csrf
                                    <button class="text-xs text-indigo-600 font-bold hover:underline">Publish</button>
                                </form>
                                <form method="POST" action="{{ route('admission.sections.destroy', $s) }}" class="inline ml-2"
                                      onsubmit="return confirm('Delete this draft section?');">
                                    @csrf @method('DELETE')
                                    <button class="text-xs text-rose-600 hover:underline">Delete</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-slate-500">
                            No sections for this term yet. Generate them in the Scheduler.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
@endsection
