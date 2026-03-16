


<nav class="px-4 py-6 space-y-2 text-sm">

    
    <a href="{{ route('dashboard') }}"
       class="flex items-center gap-3 p-3 rounded-xl transition-all duration-200
       {{ request()->routeIs('dashboard') 
            ? 'bg-indigo-600/20 text-indigo-400 font-bold' 
            : $hoverClass }}">
        <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
        Dashboard
    </a>

   
    <a href="#"
       class="flex items-center gap-3 p-3 rounded-xl transition-all duration-200
       {{ request()->routeIs('admission.applications*') 
            ? 'bg-indigo-600/20 text-indigo-400 font-bold' 
            : $hoverClass }}">
        <i data-lucide="file-text" class="w-5 h-5"></i>
        Academic Calendar
    </a>

    
    <a href="#"
       class="flex items-center gap-3 p-3 rounded-xl transition-all duration-200
       {{ request()->routeIs('admission.students*') 
            ? 'bg-indigo-600/20 text-indigo-400 font-bold' 
            : $hoverClass }}">
        <i data-lucide="users" class="w-5 h-5"></i>
        Curriculum
    </a>


    <a href="#"
       class="flex items-center gap-3 p-3 rounded-xl transition-all duration-200
       {{ request()->routeIs('admission.interviews*') 
            ? 'bg-indigo-600/20 text-indigo-400 font-bold' 
            : $hoverClass }}">
        <i data-lucide="calendar-check" class="w-5 h-5"></i>
        Faculty
    </a>

  
    <a href="#"
       class="flex items-center gap-3 p-3 rounded-xl transition-all duration-200
       {{ request()->routeIs('admission.enrollment*') 
            ? 'bg-indigo-600/20 text-indigo-400 font-bold' 
            : $hoverClass }}">
        <i data-lucide="graduation-cap" class="w-5 h-5"></i>
        Students
    </a>

    
    <a href="#"
       class="flex items-center gap-3 p-3 rounded-xl transition-all duration-200
       {{ request()->routeIs('admission.reports*') 
            ? 'bg-indigo-600/20 text-indigo-400 font-bold' 
            : $hoverClass }}">
        <i data-lucide="bar-chart-3" class="w-5 h-5"></i>
        Academic Policy
    </a>

    
    <a href="#"
       class="flex items-center gap-3 p-3 rounded-xl transition-all duration-200
       {{ request()->routeIs('admission.communications*') 
            ? 'bg-indigo-600/20 text-indigo-400 font-bold' 
            : $hoverClass }}">
        <i data-lucide="mail" class="w-5 h-5"></i>
        Quality Assurance
    </a>
	
	{{-- Communications --}}
    <a href="{{ route('communication.index') }}"
    class="flex items-center justify-between p-3 rounded-xl transition-all duration-200
    {{ request()->routeIs('academics.communication*') 
            ? 'bg-indigo-600/20 text-indigo-400 font-bold' 
            : $hoverClass }}">

        <div class="flex items-center gap-3">
            <i data-lucide="mail" class="w-5 h-5"></i>
            <span>Communications</span>
        </div>

        @if($unreadChats > 0)
            <span style="background:red; color:white; padding:2px 6px; border-radius:9999px; font-size:11px; font-weight:600; line-height:1;">
                {{ $unreadChats }}
            </span>
        @endif
        
    </a>


    
    <a href="#"
       class="flex items-center gap-3 p-3 rounded-xl transition-all duration-200
       {{ request()->routeIs('admission.communications*') 
            ? 'bg-indigo-600/20 text-indigo-400 font-bold' 
            : $hoverClass }}">
        <i data-lucide="mail" class="w-5 h-5"></i>
        Reports
    </a>

    
    <a href="#"
       class="flex items-center gap-3 p-3 rounded-xl transition-all duration-200
       {{ request()->routeIs('admission.settings') 
            ? 'bg-indigo-600/20 text-indigo-400 font-bold' 
            : $hoverClass }}">
        <i data-lucide="settings" class="w-5 h-5"></i>
        Settings
    </a>

</nav>