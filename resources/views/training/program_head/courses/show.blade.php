@extends('layouts.app')

@section('content')
<div class="w-full space-y-5">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">{{ $course->course_name ?? $course->name }}</h1>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-6">
        <pre class="text-xs text-slate-600">{{ json_encode($course->toArray(), JSON_PRETTY_PRINT) }}</pre>
    </div>
</div>
@endsection
