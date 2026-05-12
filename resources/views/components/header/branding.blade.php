<div class="flex flex-col">
    <span class="text-[10px] uppercase font-bold tracking-widest opacity-60">
        {{ $school->school_name ?? 'Sophentis SMS' }}
    </span>
    <h1 class="text-lg font-bold">
        @yield('page-title', ucwords(str_replace('_', ' ', $user->role)) . ' Dashboard')
    </h1>
</div>