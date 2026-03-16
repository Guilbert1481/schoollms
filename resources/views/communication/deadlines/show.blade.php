@extends('layouts.app')

@section('content')
<div class="container mx-auto py-8">
    <h1 class="text-2xl font-bold mb-4">Deadline Details</h1>
    <div class="bg-white shadow rounded p-6">
        <h2 class="text-xl font-semibold mb-2">{{ $deadline->title }}</h2>
        <p class="mb-2">Due Date: <strong>{{ $deadline->due_date->format('M d, Y') }}</strong></p>
        <p class="mb-4">{{ $deadline->content }}</p>
        <a href="{{ route('communication.deadlines.index') }}" class="text-blue-600 hover:underline">Back to Deadlines</a>
    </div>
</div>
@endsection
