<nav class="fixed w-full z-50 glass border-b border-slate-200/80 py-4">
    <div class="w-full px-[10px] flex items-center gap-4 justify-between">
        <div class="flex items-center gap-3 min-w-0 flex-1">
            @if(!empty($schoolLogo))
                {{-- Logo stays inside the bar, keeps its aspect ratio, and is capped
                     so tall/wide uploads can't blow the header out. --}}
                <img src="{{ $schoolLogo }}" alt="{{ $schoolName }} logo"
                     class="h-10 lg:h-12 w-auto max-w-[150px] object-contain shrink-0">
            @else
                <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center font-bold text-white shadow-lg shadow-blue-500/20">
                    {{ strtoupper(substr($schoolName, 0, 1)) }}
                </div>
            @endif
            <span class="text-base lg:text-xl font-bold tracking-tight text-slate-900 truncate">{{ $schoolName }}</span>
        </div>

        <div class="ml-2 lg:ml-4 flex items-center gap-3 lg:gap-6 justify-end shrink-0">
            <div class="hidden lg:flex items-center justify-end gap-8 text-sm font-medium uppercase tracking-widest text-[11px]">
                <a href="{{ route('website.home', ['schoolSlug' => $schoolSlug]) }}" class="{{ $activePage === 'home' ? 'text-blue-600' : 'text-slate-700 hover:text-slate-900 transition-colors' }}">HOME</a>
                <a href="{{ route('website.about', ['schoolSlug' => $schoolSlug]) }}" class="{{ $activePage === 'about' ? 'text-blue-600' : 'text-slate-700 hover:text-slate-900 transition-colors' }}">ABOUT US</a>
                <a href="{{ route('website.programs', ['schoolSlug' => $schoolSlug]) }}" class="{{ $activePage === 'programs' ? 'text-blue-600' : 'text-slate-700 hover:text-slate-900 transition-colors' }}">PROGRAMS</a>
                <a href="{{ route('website.courses', ['schoolSlug' => $schoolSlug]) }}" class="{{ $activePage === 'courses' ? 'text-blue-600' : 'text-slate-700 hover:text-slate-900 transition-colors' }}">COURSES</a>
                <a href="{{ route('website.admissions', ['schoolSlug' => $schoolSlug]) }}" class="{{ $activePage === 'admissions' ? 'text-blue-600' : 'text-slate-700 hover:text-slate-900 transition-colors' }}">ADMISSIONS</a>
                <a href="{{ route('website.blog', ['schoolSlug' => $schoolSlug]) }}" class="{{ $activePage === 'blog' ? 'text-blue-600' : 'text-slate-700 hover:text-slate-900 transition-colors' }}">BLOG</a>
            </div>

            <a href="{{ $loginUrl }}" class="px-4 lg:px-6 py-2 rounded-full bg-blue-600 text-white font-bold text-xs hover:bg-blue-500 transition-all shadow-lg shadow-blue-600/20">
                LOGIN
            </a>

            {{-- Mobile menu toggle (website pages have no Alpine, so plain JS) --}}
            <button type="button" id="wsNavToggle" aria-label="Open menu" aria-expanded="false"
                    class="lg:hidden p-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-100 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="18" y2="18"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Mobile menu panel --}}
    <div id="wsNavMenu" class="hidden lg:hidden mt-4 border-t border-slate-200/80 px-4 pt-3 pb-4">
        <a href="{{ route('website.home', ['schoolSlug' => $schoolSlug]) }}" class="block px-3 py-2 rounded-lg text-sm font-semibold uppercase tracking-widest {{ $activePage === 'home' ? 'text-blue-600 bg-blue-50' : 'text-slate-700 hover:bg-slate-100' }}">HOME</a>
        <a href="{{ route('website.about', ['schoolSlug' => $schoolSlug]) }}" class="block px-3 py-2 rounded-lg text-sm font-semibold uppercase tracking-widest {{ $activePage === 'about' ? 'text-blue-600 bg-blue-50' : 'text-slate-700 hover:bg-slate-100' }}">ABOUT US</a>
        <a href="{{ route('website.programs', ['schoolSlug' => $schoolSlug]) }}" class="block px-3 py-2 rounded-lg text-sm font-semibold uppercase tracking-widest {{ $activePage === 'programs' ? 'text-blue-600 bg-blue-50' : 'text-slate-700 hover:bg-slate-100' }}">PROGRAMS</a>
        <a href="{{ route('website.courses', ['schoolSlug' => $schoolSlug]) }}" class="block px-3 py-2 rounded-lg text-sm font-semibold uppercase tracking-widest {{ $activePage === 'courses' ? 'text-blue-600 bg-blue-50' : 'text-slate-700 hover:bg-slate-100' }}">COURSES</a>
        <a href="{{ route('website.admissions', ['schoolSlug' => $schoolSlug]) }}" class="block px-3 py-2 rounded-lg text-sm font-semibold uppercase tracking-widest {{ $activePage === 'admissions' ? 'text-blue-600 bg-blue-50' : 'text-slate-700 hover:bg-slate-100' }}">ADMISSIONS</a>
        <a href="{{ route('website.blog', ['schoolSlug' => $schoolSlug]) }}" class="block px-3 py-2 rounded-lg text-sm font-semibold uppercase tracking-widest {{ $activePage === 'blog' ? 'text-blue-600 bg-blue-50' : 'text-slate-700 hover:bg-slate-100' }}">BLOG</a>
    </div>

    <script>
        (function () {
            var toggle = document.getElementById('wsNavToggle');
            var menu = document.getElementById('wsNavMenu');
            if (!toggle || !menu) return;
            toggle.addEventListener('click', function () {
                var open = menu.classList.toggle('hidden') === false;
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
        })();
    </script>
</nav>
