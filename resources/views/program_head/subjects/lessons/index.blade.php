@extends('layouts.app')

@section('content')
<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4">Lessons for Subject: {{ $subject->name }}</h1>
    @foreach($subject->topics as $topic)
        <div class="mb-6">
            <h2 class="text-xl font-semibold">Topic: {{ $topic->name }}</h2>
            @if($topic->lessons->count())
                <ul class="list-disc ml-6">
                    @foreach($topic->lessons as $lesson)
                        <li>{{ $lesson->name }}</li>
                    @endforeach
                </ul>
            @else
                <p class="text-gray-500">No lessons for this topic.</p>
            @endif
        </div>
    @endforeach
</div>
@endsection
