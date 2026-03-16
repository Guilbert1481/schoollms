<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>
        @yield('page-title') 
        @if(Auth::check() && Auth::user()->school)
            | {{ Auth::user()->school->school_name }} 
        @endif
    </title>

    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <script src="{{ asset('js/assignable-dropdown.js') }}"></script>

    <style>[x-cloak] { display: none !important; }</style>

    @stack('styles')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
</head>





<body class="bg-gray-100 min-h-screen">

<div x-data="{ sidebarOpen: false }" class="flex min-h-screen relative">

    <!-- Sidebar toggle button (shows only on small screens/zoomed) -->
    <button
        @click="sidebarOpen = true"
        class="fixed top-4 left-4 z-40 rounded p-2 border transition-all duration-300
        {{ $superPriority ? 'bg-red-600 border-red-700 animate-flashRed shadow-lg' : 'bg-white border-slate-200' }}"
        type="button"
    >
        <i data-lucide="{{ $superPriority ? 'bell-ring' : 'menu' }}" 
        class="h-6 w-6 {{ $superPriority ? 'text-white' : 'text-gray-700' }}"></i>
    </button>

    <!-- Sidebar: Drawer for mobile, fixed for desktop -->
    <!-- Mobile drawer -->
    <aside
        x-show="sidebarOpen"
        @keydown.window.escape="sidebarOpen = false"
        @click.away="sidebarOpen = false"
        x-transition
        class="fixed inset-y-0 left-0 w-64 bg-slate-100 border-r border-slate-200 z-50 shadow-md md:hidden flex flex-col"
        style="display: none;"
    >
        <div class="flex-1 overflow-y-auto">
            @include('layouts.sidebar')
        </div>
        <button
            @click="sidebarOpen = false"
            class="m-4 bg-slate-300 text-slate-800 px-3 py-2 rounded w-fit self-end"
        >Close</button>
    </aside>

    <!-- Desktop sidebar -->
    <aside class="w-64 flex-shrink-0 bg-slate-100 border-r border-slate-200 min-h-screen hidden md:block">
        @include('layouts.sidebar')
    </aside>

    <!-- Main Content (unchanged) -->
    <div class="flex-1 flex flex-col min-w-0">
        <!-- HEADER -->
        <div class="sticky top-0 z-30">
            @include('layouts.header')
        </div>
        <!-- CONTENT -->
        <div class="flex-1 p-6 overflow-y-auto">
            @yield('content')
        </div>
    </div>
</div>

{{-- THEME SETTINGS MODAL (unchanged) --}}
<div 
    x-data="{ open: false }"
    x-on:open-theme-modal.window="open = true"
    x-show="open"
    x-transition
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
    style="display: none;"
>
    <div 
        @click.away="open = false"
        class="bg-white w-full max-w-5xl rounded-2xl shadow-2xl p-8 relative overflow-y-auto max-h-[90vh]"
    >
        <button 
            @click="open = false"
            class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-xl">
            ✕
        </button>
        @include('user.partials.theme-content')
    </div>
</div>

{{-- LUCIDE ICONS --}}
<script src="https://unpkg.com/lucide@latest"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        lucide.createIcons();
    });
</script>
@stack('scripts')
</body>
</html>