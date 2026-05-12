@extends('layouts.app')

@section('content')
<div class="w-full space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Materials</h1>
            <p class="text-sm text-slate-500">Upload and manage training materials.</p>
        </div>
        <button type="button"
                class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
            Upload Material
        </button>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-slate-500">
        No materials uploaded yet.
    </div>
</div>

<script src="{{ asset('js/training/trainor/materials.js') }}"></script>
@endsection
