<div x-data="{ open: false }" class="relative flex items-center gap-3">

    <div class="text-right hidden sm:block">
        <div class="text-sm font-bold leading-none mb-1">
            {{ $profile->first_name ?? $user->first_name }}
            {{ $profile->middle_name ?? '' }}
            {{ $profile->last_name ?? $user->last_name }}
        </div>
        <div class="text-[10px] uppercase opacity-60 font-bold tracking-tighter">
            {{ $roleName ?? $user->role }}
        </div>
    </div>

    <button @click="open = !open" class="flex items-center gap-2 focus:outline-none">
        <img src="{{ $user->profile_photo ? asset('storage/' . $user->profile_photo) : 'https://ui-avatars.com/api/?name=' . urlencode($profile->first_name ?? $user->first_name) }}"
             class="w-10 h-10 rounded-full object-cover border {{ $borderClass }}">
    </button>

    <div x-show="open"
         @click.away="open = false"
         x-transition
         class="absolute right-0 top-full mt-2 w-56 bg-white rounded-xl shadow-xl border border-slate-100 overflow-hidden z-50 text-slate-700">

        {{-- ACCOUNT HEADER --}}
        <div class="p-4 bg-slate-50/50 border-b border-slate-100">
            <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mb-1">Account</p>
            <p class="text-sm font-semibold truncate">{{ $user->email }}</p>
        </div>

        {{-- MENU --}}
        <div class="p-2">

            {{-- 🔍 GLOBAL SEARCH (FIXED) --}}
            <button 
    onclick="openGlobalSearch(); open = false"
    class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm hover:bg-slate-50 transition text-left">
    <i data-lucide="search" class="w-4 h-4 text-slate-400"></i>
    Search
</button>

            {{-- PROFILE --}}
            <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm hover:bg-slate-50 transition">
                <i data-lucide="user" class="w-4 h-4 text-slate-400"></i>
                My Profile
            </a>

            {{-- THEME --}}
            <button 
                @click="$dispatch('open-theme-modal'); open = false"
                class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm hover:bg-slate-50 transition text-left">
                <i data-lucide="palette" class="w-4 h-4 text-slate-400"></i>
                Theme Settings
            </button>

            <div class="my-1 border-t border-slate-100"></div>

            {{-- LOGOUT --}}
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                    class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-red-600 hover:bg-red-50 transition text-left">
                    <i data-lucide="log-out" class="w-4 h-4"></i>
                    Sign Out
                </button>
            </form>

        </div>
    </div>
</div>