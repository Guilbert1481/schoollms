@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-b from-gray-50 to-gray-100">

    <div class="mb-10">
        <h1 class="text-3xl font-bold text-gray-900 tracking-tight">
            Communication
        </h1>
        <p class="text-gray-600 mt-2 text-sm">
            Institutional announcements, deadlines, and messaging
        </p>
    </div>

    <div class="bg-white rounded-2xl shadow-md border border-gray-200 px-6 py-4 mb-8">
        <nav class="flex items-center gap-4 relative">

            {{-- Announcements --}}
            <a href="{{ route('communication.announcements.index') }}"
               class="flex items-center px-4 py-2 text-sm font-semibold rounded-xl transition-all duration-200
               {{ request()->routeIs('communication.announcements.*') 
                    ? 'bg-indigo-50 text-indigo-600' 
                    : 'text-gray-500 hover:bg-gray-50 hover:text-gray-700' }}">

                Announcements

                @if(isset($unreadAnnouncements) && $unreadAnnouncements > 0)
                    <span class="ml-2 bg-red-500 text-white px-2 py-0.5 rounded-full text-[10px] font-semibold">
                        {{ $unreadAnnouncements }}
                    </span>
                @endif
            </a>

            {{-- deadlines --}}
            <div class="relative">
                <button type="button"
                        id="deadlineToggle"
                        class="flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-xl transition-all duration-200
                        {{ request()->routeIs('communication.deadlines.*') 
                            ? 'bg-indigo-50 text-indigo-600' 
                            : 'text-gray-500 hover:bg-gray-50 hover:text-gray-700' }}">

                    Deadlines
                    <i data-lucide="more-vertical" class="w-4 h-4"></i>
                </button>

                {{-- Dropdown Menu --}}
                <div id="deadlineMenu"
                     class="hidden absolute left-0 mt-2 w-[280px] bg-white border border-gray-200 rounded-2xl shadow-xl py-2 z-50 transition-all duration-200">

                    <a href="{{ route('communication.deadlines.index') }}"
                       class="group flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-700 rounded-xl mx-2 transition-all duration-200 hover:bg-indigo-50 hover:text-indigo-600">
                        <div class="p-2 rounded-lg bg-gray-100 group-hover:bg-indigo-100 transition">
                            <i data-lucide="clipboard-list" class="w-4 h-4"></i>
                        </div>
                        <div>All Deadlines</div>
                    </a>

                    <a href="{{ route('communication.deadlines.create') }}"
                       class="group flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-700 rounded-xl mx-2 transition-all duration-200 hover:bg-indigo-50 hover:text-indigo-600">
                        <div class="p-2 rounded-lg bg-gray-100 group-hover:bg-indigo-100 transition">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                        </div>
                        <div>Create New Deadline</div>
                    </a>

                   @isset($deadline)
                        <a href="{{ route('communication.deadlines.manage', $deadline) }}"
                        class="group flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-700 rounded-xl mx-2 transition-all duration-200 hover:bg-indigo-50 hover:text-indigo-600">

                            <div class="p-2 rounded-lg bg-gray-100 group-hover:bg-indigo-100 transition">
                                <i data-lucide="settings" class="w-4 h-4"></i>
                            </div>

                            <div>Manage Deadlines</div>
                        </a>
                    @endisset
                    </a>
                </div>
            </div>

            {{-- Chat --}}
            <a href="{{ route('communication.chat.index') }}"
               class="flex items-center px-4 py-2 text-sm font-semibold rounded-xl transition-all duration-200
               {{ request()->routeIs('communication.chat.*') 
                    ? 'bg-indigo-50 text-indigo-600' 
                    : 'text-gray-500 hover:bg-gray-50 hover:text-gray-700' }}">

                Chat
            </a>
        </nav>
    </div>

    <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-8">
        {{-- 
            If you are looping through deadlines in a sub-view (yield), 
            ensure that sub-view uses the creator check:
            @if($deadlines->created_by === auth()->id())
                <a href="...">View/Edit</a>
            @endif
        --}}
        @yield('communication-content')
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggleBtn = document.getElementById('deadlineToggle');
    const menu = document.getElementById('deadlineMenu');

    if (!toggleBtn || !menu) return;

    // Toggle Dropdown
    toggleBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        menu.classList.toggle('hidden');
    });

    // Close when clicking outside
    document.addEventListener('click', function (e) {
        if (!menu.contains(e.target) && !toggleBtn.contains(e.target)) {
            menu.classList.add('hidden');
        }
    });
});
</script>
@endsection