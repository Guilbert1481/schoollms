<nav x-data="{ open: null }" class="px-4 py-6 space-y-2 text-sm">

    {{-- Dashboard --}}
    <a href="{{ route('dashboard') }}"
       class="flex items-center gap-3 p-3 rounded-xl transition-all duration-200
       {{ request()->routeIs('dashboard') 
            ? 'bg-indigo-600/20 text-indigo-400 font-bold' 
            : $hoverClass }}">
        <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
        Dashboard
    </a>


    {{-- ================= APPLICANTS ================= --}}
    <div>
        <button 
            @click="open === 'applicants' ? open = null : open = 'applicants'"
            class="w-full flex items-center justify-between p-3 rounded-xl transition-all duration-200 {{ $hoverClass }}">

            <div class="flex items-center gap-3">
                <i data-lucide="user-plus" class="w-5 h-5"></i>
                Applicants
            </div>

            <i data-lucide="chevron-down"
               :class="open === 'applicants' ? 'rotate-180' : ''"
               class="w-4 h-4 transition-transform duration-300"></i>
        </button>

        <div x-show="open === 'applicants'" 
             x-collapse
             class="ml-14 md:ml-16 mt-2 space-y-1 border-l border-slate-200 pl-5">

            <a href="#" class="flex items-center gap-2 py-2 text-sm text-slate-600 hover:text-indigo-500 transition">
                <i data-lucide="users" class="w-4 h-4"></i>
                Applicant Directory
            </a>

            <a href="#" class="flex items-center gap-2 py-2 text-sm text-slate-600 hover:text-indigo-500 transition">
                <i data-lucide="file-text" class="w-4 h-4"></i>
                Applications
            </a>

            <a href="#" class="flex items-center gap-2 py-2 text-sm text-slate-600 hover:text-indigo-500 transition">
                <i data-lucide="folder-check" class="w-4 h-4"></i>
                Requirements
            </a>

        </div>
    </div>


    {{-- ================= SCREENING ================= --}}
    <div>
        <button 
            @click="open === 'screening' ? open = null : open = 'screening'"
            class="w-full flex items-center justify-between p-3 rounded-xl transition-all duration-200 {{ $hoverClass }}">

            <div class="flex items-center gap-3">
                <i data-lucide="clipboard-check" class="w-5 h-5"></i>
                Screening
            </div>

            <i data-lucide="chevron-down"
               :class="open === 'screening' ? 'rotate-180' : ''"
               class="w-4 h-4 transition-transform duration-300"></i>
        </button>

        <div x-show="open === 'screening'" 
             x-collapse
             class="ml-14 md:ml-16 mt-2 space-y-1 border-l border-slate-200 pl-5">

            <a href="#" class="flex items-center gap-2 py-2 text-sm text-slate-600 hover:text-indigo-500 transition">
                <i data-lucide="edit-3" class="w-4 h-4"></i>
                Entrance Exams
            </a>

            <a href="#" class="flex items-center gap-2 py-2 text-sm text-slate-600 hover:text-indigo-500 transition">
                <i data-lucide="mic" class="w-4 h-4"></i>
                Interviews
            </a>

            <a href="#" class="flex items-center gap-2 py-2 text-sm text-slate-600 hover:text-indigo-500 transition">
                <i data-lucide="check-circle" class="w-4 h-4"></i>
                Approval Decisions
            </a>

        </div>
    </div>


    {{-- ================= ENROLLMENT ENDORSEMENT ================= --}}
    <div>
        <button 
            @click="open === 'endorsement' ? open = null : open = 'endorsement'"
            class="w-full flex items-center justify-between p-3 rounded-xl transition-all duration-200 {{ $hoverClass }}">

            <div class="flex items-center gap-3">
                <i data-lucide="send" class="w-5 h-5"></i>
                Endorsement
            </div>

            <i data-lucide="chevron-down"
               :class="open === 'endorsement' ? 'rotate-180' : ''"
               class="w-4 h-4 transition-transform duration-300"></i>
        </button>

        <div x-show="open === 'endorsement'" 
             x-collapse
             class="ml-14 md:ml-16 mt-2 space-y-1 border-l border-slate-200 pl-5">

            <a href="#" class="flex items-center gap-2 py-2 text-sm text-slate-600 hover:text-indigo-500 transition">
                <i data-lucide="arrow-right-circle" class="w-4 h-4"></i>
                For Enrollment
            </a>

            <a href="#" class="flex items-center gap-2 py-2 text-sm text-slate-600 hover:text-indigo-500 transition">
                <i data-lucide="x-circle" class="w-4 h-4"></i>
                Rejected Applications
            </a>

        </div>
    </div>


    {{-- ================= REPORTS ================= --}}
    <a href="#"
       class="flex items-center gap-3 p-3 rounded-xl transition-all duration-200 {{ $hoverClass }}">
        <i data-lucide="bar-chart-3" class="w-5 h-5"></i>
        Admission Reports
    </a>


    {{-- ================= COMMUNICATION (UNCHANGED) ================= --}}
    {{-- ================= COMMUNICATION ================= --}}
    <div>
        <button 
            @click="open === 'communication' ? open = null : open = 'communication'"
            class="w-full flex items-center justify-between p-3 rounded-xl transition-all duration-200
            {{ request()->routeIs('communication.*') 
                    ? 'bg-indigo-600/20 text-indigo-400 font-bold' 
                    : $hoverClass }}">

            <div class="flex items-center gap-3">
                <i data-lucide="mail" class="w-5 h-5"></i>
                <span>Communication</span>
            </div>

            <i data-lucide="chevron-down"
            :class="open === 'communication' ? 'rotate-180' : ''"
            class="w-4 h-4 transition-transform duration-300"></i>
        </button>

        <div x-show="open === 'communication'"
            x-collapse
            class="ml-14 md:ml-16 mt-2 space-y-1 border-l border-slate-200 pl-5">

            {{-- Announcements --}}
            <a href="{{ route('communication.announcements.index') }}"
            class="flex items-center justify-between py-2 text-sm text-slate-600 hover:text-indigo-500 transition
            {{ request()->routeIs('communication.announcements.*') ? 'text-indigo-500 font-semibold' : '' }}">

                <div class="flex items-center gap-2">
                    <i data-lucide="megaphone" class="w-4 h-4"></i>
                    Announcements
                </div>

                @if(isset($unreadAnnouncements) && $unreadAnnouncements > 0)
                    <span class="bg-red-500 text-white px-2 py-0.5 rounded-full text-[10px] font-semibold">
                        {{ $unreadAnnouncements }}
                    </span>
                @endif
            </a>


            {{-- Deadlines --}}
            <a href="{{ url('/communication/deadlines') }}"
            class="flex items-center gap-2 py-2 text-sm text-slate-600 hover:text-indigo-500 transition
            {{ request()->routeIs('communication.deadlines.*') ? 'text-indigo-500 font-semibold' : '' }}">

                <i data-lucide="calendar-clock" class="w-4 h-4"></i>
                Deadlines
            </a>


            {{-- Chat --}}
            <a href="{{ route('communication.chat.index') }}"
            class="flex items-center justify-between py-2 text-sm text-slate-600 hover:text-indigo-500 transition
            {{ request()->routeIs('communication.chat.*') ? 'text-indigo-500 font-semibold' : '' }}">

                <div class="flex items-center gap-2">
                    <i data-lucide="message-circle" class="w-4 h-4"></i>
                    Chat
                </div>

                @if(isset($unreadChats) && $unreadChats > 0)
                    <span class="bg-red-500 text-white px-2 py-0.5 rounded-full text-[10px] font-semibold">
                        {{ $unreadChats }}
                    </span>
                @endif
            </a>

        </div>
    </div>


    {{-- Settings --}}
    <a href="#"
       class="flex items-center gap-3 p-3 rounded-xl transition-all duration-200 {{ $hoverClass }}">
        <i data-lucide="settings" class="w-5 h-5"></i>
        Settings
    </a>

</nav>
