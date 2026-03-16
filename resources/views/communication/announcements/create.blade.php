@extends('communication.layout')

@section('communication-content')

<div class="max-w-5xl mx-auto">

    <!-- Header -->
    <div class="mb-8">
        <h2 class="text-2xl font-semibold text-gray-900 flex items-center gap-2">
            <span class="w-1.5 h-6 bg-indigo-600 rounded-full"></span>
            Create Announcement
        </h2>
        <p class="text-sm text-gray-600 mt-1">
            Publish an institutional announcement for selected users.
        </p>
    </div>

    <!-- Form Card -->
    <div class="bg-gray-50 border border-gray-200 rounded-2xl p-10 shadow-sm">

        <form action="{{ route('communication.announcements.store') }}" method="POST">
            @csrf

            <!-- Title -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Title
                </label>
                <input type="text"
                       name="title"
                       class="w-full h-14 px-4 rounded-xl border border-gray-300 bg-white shadow-sm
                              focus:ring-2 focus:ring-indigo-600 focus:border-indigo-600 transition"
                       required>
            </div>

            <!-- Content -->
            <div class="mb-8">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Content
                </label>
                <textarea name="content"
                          rows="6"
                          class="w-full px-4 py-3 rounded-xl border border-gray-300 bg-white shadow-sm
                                 focus:ring-2 focus:ring-indigo-600 focus:border-indigo-600 transition"
                          required></textarea>
            </div>


           
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2"><x-assignable-dropdown :groups="$groups" /></div>
                <div class="md:col-span-1"></div>
                
            </div>

           
            <div class="flex justify-between items-center mt-6 gap-4 w-full">
    <!-- Priority box on the left -->
    <div class="flex items-center bg-white border border-gray-300 rounded-lg px-4 h-12">
        <input type="checkbox"
                name="priority_level"
                id="super_priority"
                value="super"
                class="w-4 h-4 rounded text-red-600 focus:ring-red-500 border-gray-300">
        <label for="super_priority"
                class="ml-2 text-red-600 font-medium cursor-pointer text-sm whitespace-nowrap">
                🚨 Priority (expires in 1 hr)
        </label>
    </div>
    <!-- Buttons on the right -->
    <div class="flex gap-2">
        <a href="{{ route('communication.announcements.index') }}"
            class="px-4 py-2 rounded-lg border bg-gray-100 hover:bg-gray-200 transition h-12 flex items-center">
            Cancel
        </a>
        <button type="submit"
                class="px-6 py-2 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 transition shadow h-12 flex items-center">
            Publish
        </button>
    </div>
</div>

            </div>

        </form>

    </div>

</div>

@endsection
