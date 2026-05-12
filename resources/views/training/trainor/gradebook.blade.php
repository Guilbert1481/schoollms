@extends('layouts.app')

@section('content')
<div class="w-full space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Gradebook</h1>
        <p class="text-sm text-slate-500">Overall performance and grades per trainee.</p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-slate-500">
        No grades recorded yet.
    </div>
</div>

<script src="{{ asset('js/training/trainor/gradebook.js') }}"></script>
@endsection
