@extends('layouts.app')

@section('page-title', 'Create Chat')

@section('content')
<div class="max-w-3xl mx-auto">

    <div class="bg-white shadow-lg rounded-2xl p-8 border border-slate-100">

        <h2 class="text-xl font-semibold text-slate-800 mb-6">
            Start New Conversation
        </h2>

        <form method="POST" action="{{ route('communication.chat.store') }}">
            @csrf

            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-600 mb-2">
                    Group Title (Optional for Private Chat)
                </label>

                <input type="text" name="title"
                    class="w-full border border-slate-200 rounded-xl p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                    placeholder="e.g. BSIT 3A Project Team">
            </div>


            <div class="mb-6">
                <x-assignable-dropdown :groups="$groups" />

                @error('participants')
                    <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end gap-3">

                <a href="{{ route('communication.chat.index') }}"
                   class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                    Cancel
                </a>

                <button type="submit"
                    class="px-6 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition">
                    Create Chat
                </button>

            </div>
        </form>

    </div>

</div>
@endsection
