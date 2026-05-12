@extends('layouts.app')

@section('content')
<div class="w-full space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Assessments</h1>
            <p class="text-sm text-slate-500">Create, deliver, and grade trainee assessments.</p>
        </div>
        <button type="button"
                class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
            New Assessment
        </button>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-slate-500">
        No assessments created yet.
    </div>
</div>
@endsection
