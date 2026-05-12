@extends('layouts.app')

@section('content')

<div class="max-w-6xl mx-auto p-6 space-y-6">

    @include('school.settings.partials.school-settings._header')

    @include('partials.tabs', [
        'tabs' => config('tabs.tabs.school_settings')
    ])

    <div class="max-w-6xl mx-auto p-6">

    {{-- SUCCESS MESSAGE --}}
    @if(session('success'))
        <div class="bg-green-100 border border-green-300 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    {{-- ERROR MESSAGE --}}
    @if(session('error'))
        <div class="bg-red-100 border border-red-300 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    {{-- VALIDATION ERRORS --}}
    @if($errors->any())
        <div class="bg-red-100 border border-red-300 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc ml-6">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
          action="{{ route('form.save', 'school_settings') }}"
          enctype="multipart/form-data">
        @csrf

        <x-form-renderer
            formKey="school_settings"
            :model="$profile"
        />

        <div class="mt-6">
            <button type="submit"
                    class="bg-blue-600 text-white px-6 py-2 rounded-lg">
                Save
            </button>
        </div>

    </form>

</div>

</div>

@endsection