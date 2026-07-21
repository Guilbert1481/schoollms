@extends('layouts.app')

@section('page-title', 'Clearance Signatories')

@section('content')
<div class="w-full max-w-4xl space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-800">Clearance Signatories</h1>
        <p class="text-sm text-slate-500">
            The offices that must sign a student's clearance. "Subject Teachers" expands into one row
            per subject teacher of the student. Existing clearances keep their rows when you edit this list.
        </p>
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
            {{ session('success') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <ul class="list-disc pl-4">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- Add signatory --}}
    <form method="POST" action="{{ route('registrar.settings.clearance-signatories.store') }}"
          class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
        @csrf
        <h2 class="font-bold text-slate-800">Add signatory</h2>
        <div class="grid gap-4 sm:grid-cols-4">
            <div class="sm:col-span-2">
                <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Name</label>
                <input type="text" name="name" required maxlength="100" placeholder="e.g. Property Custodian"
                       class="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Type</label>
                <select name="type" class="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
                    <option value="department">Department</option>
                    <option value="subject_teachers">Subject Teachers</option>
                </select>
            </div>
            <div>
                <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Applies to</label>
                <select name="applies_to" class="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
                    <option value="both">Both</option>
                    <option value="basic">Basic Ed only</option>
                    <option value="higher">Higher Ed only</option>
                </select>
            </div>
        </div>
        <button type="submit"
                class="inline-flex rounded-lg bg-indigo-600 px-5 py-2 text-sm font-bold text-white hover:bg-indigo-700">
            Add signatory
        </button>
    </form>

    {{-- List --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[560px] text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Applies to</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($signatories as $signatory)
                        <tr>
                            <td class="px-4 py-3 font-semibold text-slate-700">{{ $signatory->name }}</td>
                            <td class="px-4 py-3 text-slate-600">
                                {{ $signatory->type === 'subject_teachers' ? 'Subject Teachers (expands per teacher)' : 'Department' }}
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                {{ ['basic' => 'Basic Ed only', 'higher' => 'Higher Ed only', 'both' => 'Both'][$signatory->applies_to] ?? $signatory->applies_to }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <form method="POST" action="{{ route('registrar.settings.clearance-signatories.destroy', $signatory) }}"
                                      onsubmit="return confirm('Remove “{{ $signatory->name }}” from the clearance list? Existing clearances keep their rows.');"
                                      class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-bold text-rose-600 hover:bg-rose-50">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
