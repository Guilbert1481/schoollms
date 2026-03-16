@extends('layouts.app')

@section('content')

<div class="max-w-3xl mx-auto bg-white p-10 rounded-3xl shadow-2xl border border-slate-100">

    <h1 class="text-3xl font-serif-premium text-slate-900 mb-2">
        School Branding Settings
    </h1>
    <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-8">
        Customize your institution identity
    </p>

    {{-- Success Message --}}
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-2xl text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Error Display --}}
    @if ($errors->any())
        <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-600 rounded-2xl text-sm">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
          action="{{ route('school.update.branding') }}"
          enctype="multipart/form-data"
          class="space-y-8">
        @csrf

        {{-- School Name --}}
        <div>
            <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">
                School Name
            </label>

            <input type="text"
                   name="school_name"
                   value="{{ $school->school_name }}"
                   class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none font-bold text-slate-700">
        </div>

        {{-- Logo Upload --}}
        <div>
            <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">
                School Logo
            </label>

            @if($school->school_logo)
                <div class="mb-4">
                    <img src="{{ asset('storage/' . $school->school_logo) }}"
                         class="w-20 h-20 object-cover rounded-xl shadow-md">
                </div>
            @endif

            <input type="file"
                   name="school_logo"
                   class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl">
        </div>

        {{-- Sidebar Color --}}
        <div>
            <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2">
                Sidebar Color
            </label>

            <div class="flex items-center gap-4">
                <input type="color"
                       name="sidebar_color"
                       value="{{ $school->sidebar_color }}"
                       class="w-20 h-14 rounded-xl border cursor-pointer">

                <span class="text-sm text-slate-500">
                    Choose a primary color for your navigation sidebar
                </span>
            </div>
        </div>

        {{-- Submit --}}
        <div class="pt-4">
            <button type="submit"
                    class="px-8 py-4 bg-slate-900 hover:bg-indigo-600 text-white rounded-2xl font-black uppercase text-xs tracking-[0.2em] transition-all shadow-xl">
                Save Branding
            </button>
        </div>

    </form>

</div>

@endsection
