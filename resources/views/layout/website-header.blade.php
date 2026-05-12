<nav class="fixed w-full z-50 glass border-b border-slate-200/80 py-4">
    <div class="w-full px-[10px] flex items-center gap-4 justify-between">
        <div class="flex items-center gap-3 min-w-0 flex-1">
            @if(!empty($schoolLogo))
                <div class="h-10 w-40 flex items-center justify-center shrink-0 overflow-visible">
                    <img src="{{ $schoolLogo }}" alt="{{ $schoolName }} logo" class="h-40 w-40 object-contain max-w-none">
                </div>
            @else
                <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center font-bold text-white shadow-lg shadow-blue-500/20">
                    {{ strtoupper(substr($schoolName, 0, 1)) }}
                </div>
            @endif
            <span class="text-xl font-bold tracking-tight text-slate-900 truncate">{{ $schoolName }}</span>
        </div>

        <div class="ml-4 flex items-center gap-6 justify-end">
            <div class="hidden lg:flex items-center justify-end gap-8 text-sm font-medium uppercase tracking-widest text-[11px]">
                <a href="{{ route('website.home', ['schoolSlug' => $schoolSlug]) }}" class="{{ $activePage === 'home' ? 'text-blue-600' : 'text-slate-700 hover:text-slate-900 transition-colors' }}">HOME</a>
                <a href="{{ route('website.about', ['schoolSlug' => $schoolSlug]) }}" class="{{ $activePage === 'about' ? 'text-blue-600' : 'text-slate-700 hover:text-slate-900 transition-colors' }}">ABOUT US</a>
                <a href="{{ route('website.programs', ['schoolSlug' => $schoolSlug]) }}" class="{{ $activePage === 'programs' ? 'text-blue-600' : 'text-slate-700 hover:text-slate-900 transition-colors' }}">PROGRAMS</a>
                <a href="{{ route('website.courses', ['schoolSlug' => $schoolSlug]) }}" class="{{ $activePage === 'courses' ? 'text-blue-600' : 'text-slate-700 hover:text-slate-900 transition-colors' }}">COURSES</a>
                <a href="{{ route('website.admissions', ['schoolSlug' => $schoolSlug]) }}" class="{{ $activePage === 'admissions' ? 'text-blue-600' : 'text-slate-700 hover:text-slate-900 transition-colors' }}">ADMISSIONS</a>
                <a href="{{ route('website.blog', ['schoolSlug' => $schoolSlug]) }}" class="{{ $activePage === 'blog' ? 'text-blue-600' : 'text-slate-700 hover:text-slate-900 transition-colors' }}">BLOG</a>
            </div>

            <a href="{{ $loginUrl }}" class="hidden sm:block px-6 py-2 rounded-full bg-blue-600 text-white font-bold text-xs hover:bg-blue-500 transition-all shadow-lg shadow-blue-600/20">
                LOGIN
            </a>
        </div>
    </div>
</nav>
