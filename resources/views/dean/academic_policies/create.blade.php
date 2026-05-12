@extends('layouts.app')

@section('content')
<div class="container mx-auto px-6 py-6 max-w-4xl">
    <div class="mb-6">
        <a href="{{ route('dean.academic_policies.index') }}" class="text-sm text-indigo-600">← Back to policies</a>
        <h1 class="text-2xl font-bold text-slate-800 mt-2">New Academic Policy</h1>
        <p class="text-sm text-slate-500 mt-1">Define a rule that the enrolment validator and approval router will apply.</p>
    </div>

    @include('dean.academic_policies._form', [
        'action' => route('dean.academic_policies.store'),
        'method' => 'POST',
    ])
</div>
@endsection
