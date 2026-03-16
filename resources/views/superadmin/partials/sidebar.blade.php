{{-- Added 'flex flex-col h-full' to enable the mt-auto logic --}}
<nav class="sidebar flex flex-col h-full bg-[#1E293B]"> 
    <div class="sidebar-logo px-6 py-4 font-bold text-lg text-indigo-400">
        LMS ENGINE v1.0
    </div>
    
    <ul class="sidebar-nav space-y-1 px-2">
        {{-- Platform Home --}}
        <li>
            <a href="{{ route('superadmin.dashboard') }}" 
               class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all
               {{ request()->routeIs('superadmin.dashboard') ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Platform Home
            </a>
        </li>

        {{-- Manage Partners --}}
        <li>
            <a href="{{ route('superadmin.schools.index') }}" 
               class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all
               {{ request()->routeIs('superadmin.schools.*') && !request()->routeIs('superadmin.schools.modules.*') ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                Manage Partners
            </a>
        </li>

        {{-- Pricing --}}
        <li>
            <a href="{{ route('superadmin.pricing.index') }}"
               class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all
               {{ request()->routeIs('superadmin.pricing.*') ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V4m0 12v4m8-10a8 8 0 11-16 0 8 8 0 0116 0z" />
                </svg>
                Pricing
            </a>
        </li>

        {{-- System Settings --}}
        <li>
            <a href="#" 
               class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all text-slate-400 hover:bg-slate-800 hover:text-white">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                System Settings
            </a>
        </li>

        {{-- My Profile --}}
        <li>
            <a href="{{ route('superadmin.profile.edit') }}" 
               class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all
               {{ request()->routeIs('superadmin.profile.*') ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                My Profile
            </a>
        </li>
    </ul>

    {{-- mt-auto ensures this div is pushed to the bottom of the flex column --}}
    <div class="mt-auto p-6 text-xs text-slate-500 border-t border-slate-800">
        Logged in as: <span class="font-bold text-slate-300">Superadmin</span>
    </div>
</nav>