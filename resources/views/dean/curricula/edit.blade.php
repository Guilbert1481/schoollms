@extends('layouts.app')

@section('content')
<div class="container mx-auto px-6 py-6 max-w-4xl">
    <div class="mb-6">
        <a href="{{ route('dean.curricula.index') }}" class="text-sm text-indigo-600">← Back to curricula</a>
        <h1 class="text-2xl font-bold text-slate-800 mt-2">Edit Curriculum</h1>
        <p class="text-sm text-slate-500 mt-1">
            <strong>{{ $curriculum->name }}</strong> · {{ $curriculum->program?->code }}
            · v{{ $curriculum->version }}
        </p>
    </div>

    @include('dean.curricula._form', [
        'action' => route('dean.curricula.update', $curriculum->id),
        'method' => 'PUT',
    ])
</div>
@endsection
