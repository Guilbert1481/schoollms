{{-- views/layouts/sidebar.blade.php --}}

@php
    $user = auth()->user();
    
    // 1. Fetch School Relationship (Fixes the "Undefined variable $school" error)
    $school = $user->school ?? null;

    // 2. Get the JSON blob
    $identity = $user->dashboard_identity ?? [];
    
    // 3. Get Sidebar specific settings from the JSON
    $sidebarSettings = $identity['sidebar'] ?? [];
    
    $mode = $sidebarSettings['mode'] ?? 'dark';
    $style = $sidebarSettings['style'] ?? 'solid'; // Added for gradient support
    $colorKey = $sidebarSettings['color'] ?? 'slate';

    // 4. Get the Hex and Classes from your config/theme.php
    $theme = config('theme.colors');
    $config = $theme[$colorKey] ?? $theme['slate'];
    
    // THE MASTER COLOR (Used for inline background-color)
    $manualBg = $config['hex'] ?? '#1e293b'; 

    // 5. Determine UI Classes
    $bgClass = ($style === 'gradient')
        ? ($config['gradient_vertical'] ?? $config['solid'])
        : $config['solid'];

    $textClass = ($mode === 'light') 
        ? ($config['text_light_mode'] ?? 'text-slate-900') 
        : ($config['text_dark_mode'] ?? 'text-white');

    $borderClass = ($mode === 'light') 
        ? ($config['border_light_mode'] ?? 'border-black/10') 
        : ($config['border_dark_mode'] ?? 'border-white/10');

    // Added hoverClass so partials.sidebar doesn't break
    $hoverClass = ($mode === 'light')
        ? ($config['hover_light_mode'] ?? 'hover:bg-black/5')
        : ($config['hover_dark_mode'] ?? 'hover:bg-white/10');
@endphp

<div class="fixed left-0 top-0 h-screen w-64 flex flex-col shadow-2xl z-40 {{ $bgClass }} {{ $textClass }}"
     style="background-color: {{ $manualBg }};">
    
    {{-- Branding --}}
    <div class="p-6 border-b flex items-center gap-3 {{ $borderClass }}">
        @if($school && $school->school_logo)
            <img src="{{ asset('storage/' . $school->school_logo) }}" 
                 class="w-10 h-10 rounded-xl object-cover shadow-lg"
                 onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($school->school_name) }}&color=7F9CF5&background=EBF4FF'">
        @endif
        <div>
            <h2 class="font-bold text-xs uppercase tracking-wider opacity-80">School Portal</h2>
            <h2 class="font-bold text-sm leading-tight">{{ $school->school_name ?? 'Memory Ridge International' }}</h2>
        </div>
    </div>

    {{-- Menu --}}
    <div class="flex-1 overflow-y-auto custom-scrollbar">
        @include('partials.sidebar', ['hoverClass' => $hoverClass, 'textClass' => $textClass])
    </div>
</div>

<style>
    /* Ensure the sidebar scrollbar is subtle */
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }
</style>