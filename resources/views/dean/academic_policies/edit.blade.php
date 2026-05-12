@extends('layouts.app')

@section('content')
<div class="container mx-auto px-6 py-6 max-w-4xl">
    <div class="mb-6">
        <a href="{{ route('dean.academic_policies.index') }}" class="text-sm text-indigo-600">← Back to policies</a>
        <h1 class="text-2xl font-bold text-slate-800 mt-2">Edit Academic Policy</h1>
        <p class="text-sm text-slate-500 mt-1">
            Scope: <strong>
                {{ $policy->education_level ? ucfirst(str_replace('_',' ', $policy->education_level)) : 'All Levels' }}
                · {{ $policy->program?->code ?: 'All Programs' }}
                · {{ $policy->term?->name ?: 'All Terms' }}
            </strong>
        </p>
    </div>

    @include('dean.academic_policies._form', [
        'action' => route('dean.academic_policies.update', $policy->id),
        'method' => 'PUT',
    ])
</div>
@endsection
