@extends('layouts.admission')

@section('content')

<div class="max-w-3xl mx-auto bg-white rounded-2xl shadow-sm p-8">

    <h2 class="text-xl font-semibold mb-6">
        Admission Test Settings
    </h2>

    @if(session('success'))
        <div class="mb-4 p-3 rounded-lg bg-green-100 text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('staff.admissions.settings.update') }}">
    @csrf

    <!-- Hidden fallback -->
    <input type="hidden" name="requires_admission_test" value="0">

    <label class="flex items-center gap-3">
        <input type="checkbox"
               name="requires_admission_test"
               value="1"
               {{ $school->requires_admission_test ? 'checked' : '' }}
               class="w-5 h-5">
        Require Admission Test Before Enrollment
    </label>

    <button type="submit"
            class="mt-4 px-6 py-2 bg-indigo-600 text-white rounded-lg">
        Save Settings
    </button>
</form>


</div>

@endsection
