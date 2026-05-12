@extends('layouts.app')

@section('content')
<div class="w-full space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">My Courses</h1>
        <p class="text-sm text-slate-500">Courses assigned to you for facilitation.</p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-slate-500">
        No courses assigned yet.
    </div>
</div>
@endsection
