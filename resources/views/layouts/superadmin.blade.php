<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Superadmin | LMS</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/superadmin/layout.css') }}">
    @vite(['resources/css/app.css'])
    
    <style>
        [x-cloak] { display: none !important; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #E2E8F0; border-radius: 10px; }
        
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            width: 100%; /* Force full width */
        }
    </style>
</head>
<body class="bg-[#f4f7fe] h-screen w-full overflow-hidden font-['Plus_Jakarta_Sans']">

    {{-- 1. Sidebar: Fixed to the left --}}
    <div class="fixed inset-y-0 left-0 w-72 h-screen z-50 bg-[#1E293B] overflow-y-auto custom-scrollbar">
        <div class="h-full w-full flex flex-col text-white">

            {{-- Branding --}}
            <div class="p-6 border-b border-white/10">
                <h2 class="font-bold text-indigo-300 text-lg tracking-wider">LMS ENGINE v1.0</h2>
                <p class="text-[10px] text-slate-400 uppercase tracking-widest mt-1">Super Admin Portal</p>
            </div>

            {{-- Shared, config-driven sidebar --}}
            <div class="flex-1 overflow-y-auto">
                @include('partials.sidebar', ['hoverClass' => 'hover:bg-white/10', 'textClass' => 'text-slate-200'])
            </div>

            {{-- Footer --}}
            <div class="p-6 text-xs text-slate-500 border-t border-white/10">
                Logged in as: <span class="font-bold text-slate-300">Superadmin</span>
            </div>
        </div>
    </div>

    {{-- 2. Main Container: Added w-full to ensure it occupies all space after ml-72 --}}
    <div class="flex flex-col h-screen ml-72 w-[calc(100%-18rem)] min-w-0 overflow-hidden">
        
        {{-- 3. Header: Ensure the container of the include is full width --}}
        <div class="flex-shrink-0 z-40 bg-white shadow-sm w-full">
            @include('superadmin.partials.header')
        </div>

        {{-- 4. Content: Set to w-full without max-width --}}
        <main class="flex-grow overflow-y-auto p-8 lg:p-12 custom-scrollbar">
            <div class="w-full"> 
                @yield('content')
            </div>
        </main>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>lucide.createIcons();</script>
    <script src="{{ mix('js/app.js') }}" defer></script>
    <script defer src="{{ asset('js/alpine.min.js') }}"></script>
</body>
</html>