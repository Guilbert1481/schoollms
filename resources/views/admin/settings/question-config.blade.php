{{-- resources/views/admin/settings/question-config.blade.php --}}

@extends('layouts.admin')

@section('content')
<div class="container">
    <h2>Question Configuration</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.settings.question-config.update') }}" id="config-form">
        @csrf

        @include('admin.settings.partials.config-block', [
            'title' => 'Academic Level',
            'category' => 'academic_level',
            'items' => $academicLevels
        ])

        @include('admin.settings.partials.config-block', [
            'title' => 'Difficulty',
            'category' => 'difficulty',
            'items' => $difficulties
        ])

        @include('admin.settings.partials.config-block', [
            'title' => 'Question Type',
            'category' => 'question_type',
            'items' => $questionTypes
        ])

        @include('admin.settings.partials.config-block', [
            'title' => 'Assessment Type',
            'category' => 'assessment_type',
            'items' => $assessmentTypes
        ])

        @include('admin.settings.partials.config-block', [
            'title' => 'Academic Term Structure',
            'category' => 'term_division',
            'items' => $termDivisions
        ])

        <button type="submit" class="btn btn-primary">Save Configuration</button>
    </form>
</div>
@endsection

@push('scripts')
<script src="/js/admin-config.js"></script>
@endpush