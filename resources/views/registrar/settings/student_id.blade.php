@extends('layouts.app')

@section('content')
<div class="mx-auto w-full max-w-2xl space-y-6 p-4 md:p-6">

    <div>
        <h1 class="text-xl font-extrabold text-slate-800">Student ID</h1>
        <p class="text-sm text-slate-500">Display options for the Student Digital ID. The orientation also sets the layout of the student Profile tab.</p>
    </div>

    @if (session('status'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('registrar.settings.student-id.update') }}"
          class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')

        {{-- Orientation --}}
        <div>
            <label class="mb-1 block text-sm font-bold text-slate-700">ID Orientation</label>
            <p class="mb-3 text-xs text-slate-500">How the Student Digital ID is shown — and which Profile layout is used.</p>
            <div class="grid grid-cols-2 gap-3">
                @foreach (['portrait' => 'Portrait', 'landscape' => 'Landscape'] as $val => $lbl)
                    <label class="flex cursor-pointer items-center gap-2 rounded-lg border px-4 py-3 text-sm font-semibold transition
                        {{ $settings->orientation === $val ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-slate-300 text-slate-700 hover:bg-slate-50' }}">
                        <input type="radio" name="orientation" value="{{ $val }}" @checked($settings->orientation === $val)
                               class="text-indigo-600 focus:ring-indigo-500">
                        {{ $lbl }}
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Barcode source --}}
        <div>
            <label for="barcode_source" class="mb-1 block text-sm font-bold text-slate-700">Barcode Source</label>
            <p class="mb-2 text-xs text-slate-500">What the ID barcode encodes.</p>
            <select id="barcode_source" name="barcode_source"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                <option value="lrn" @selected($settings->barcode_source === 'lrn')>LRN</option>
                <option value="student_number" @selected($settings->barcode_source === 'student_number')>Student Number</option>
            </select>
        </div>

        {{-- Show back --}}
        <div>
            <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                <input type="checkbox" name="show_back" value="1" @checked($settings->show_back)
                       class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                Show the ID's back side (Front / Back toggle)
            </label>
        </div>

        <div class="flex justify-end border-t border-slate-100 pt-4">
            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white hover:bg-indigo-700">
                Save Changes
            </button>
        </div>
    </form>
</div>
@endsection
