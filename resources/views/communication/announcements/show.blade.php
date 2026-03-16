@extends('communication.layout')

@section('communication-content')

<div class="max-w-3xl mx-auto bg-white border border-gray-100 rounded-2xl shadow-sm p-8">

    <h2 class="text-2xl font-semibold text-gray-900 mb-2">
        {{ $announcement->title }}
    </h2>

    <p class="text-sm text-gray-500 mb-6">
        Published {{ $announcement->created_at->format('M d, Y h:i A') }}
    </p>

    <div class="prose max-w-none text-gray-700">
        {!! nl2br(e($announcement->content)) !!}
    </div>

</div>

@endsection
