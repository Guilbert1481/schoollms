{{-- views/school/settings/master/ay-terms.blade.php --}}

@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto p-6 space-y-6">

    @include('school.settings/partials/master-data._header')
    @include('partials.tabs', [
    'tabs' => config('tabs.tabs.master_data')
])
    {{-- Flash + Validation Messages --}}
    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800">
            <div class="font-extrabold mb-2">Please fix the following:</div>
            <ul class="list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @include('school.settings/partials.school-settings._table', [
        'academicYears' => $academicYears,
        'termsByAcademicYearId' => $termsByAcademicYearId
    ])

</div>

@include('school.settings/partials.school-settings._ay_modals')
@include('school.settings/partials.school-settings._term_modals')
@include('school.settings/partials.school-settings._subject_offering_modal')
@include('school.settings/partials.school-settings._scripts')

@endsection