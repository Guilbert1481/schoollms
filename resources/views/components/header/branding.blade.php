<div class="flex flex-col min-w-0">
    <span class="text-[10px] uppercase font-bold tracking-widest opacity-60 truncate">
        {{ $school->school_name ?? 'Sophentis SMS' }}
    </span>
    <h1 class="text-base md:text-lg font-bold truncate">
        @yield('page-title', ucwords(str_replace('_', ' ', $user->role)) . ' Dashboard')
    </h1>
</div>