{{-- views/layouts/header.blade.php --}}

@php
$user = auth()->user();
$school = $user->school ?? null;


$identity = $user->dashboard_identity ?? [];
$header = $identity['header'] ?? [
    'mode' => 'dark',
    'style' => 'solid',
    'color' => 'slate'
];

$mode = $header['mode'];
$style = $header['style'];
$colorKey = $header['color'];

$theme = config('theme.colors');
$activeColor = $theme[$colorKey] ?? $theme['slate'];
$bgClass = ($style === 'gradient')
    ? $activeColor['gradient_horizontal']
    : $activeColor['solid'];
$textClass = $mode === 'light'
    ? $activeColor['text_light_mode']
    : $activeColor['text_dark_mode'];
$borderClass = $mode === 'light'
    ? $activeColor['border_light_mode']
    : $activeColor['border_dark_mode'];
$hoverClass = $mode === 'light'
    ? $activeColor['hover_light_mode']
    : $activeColor['hover_dark_mode'];
@endphp

<header class="h-24 {{ $bgClass }} {{ $textClass }} border-b {{ $borderClass }} flex items-center justify-between px-6 shadow-sm">
    <div class="hidden md:flex flex-1 mx-6 justify-center">
        {{-- LEFT: Branding & Title --}}
        <div class="flex flex-col">
            <span class="text-xs uppercase tracking-wider opacity-70">
                {{ $school->school_name ?? 'Invicta' }}
            </span>
            <h1 class="text-lg font-semibold">
                @yield('page-title', ucwords(str_replace('_', ' ', $user->role)) . ' Dashboard')
            </h1>
        </div>
        {{-- Banner logic stays inside the header container --}}
        @include('components.header-banner')
        {{-- RIGHT: Tools & User Dropdown --}}
        <div class="flex items-center gap-4">
            <div class="flex items-center gap-2 mr-2">
                <div x-data="{ open: false }" class="relative">
                    @php
                        $notifications = auth()->user()->unreadNotifications;
                        $unreadCount = $notifications->count();
                    @endphp

                    <button @click="open = !open"
                        class="p-2 rounded-lg {{ $hoverClass }} transition relative">
                        <i data-lucide="bell" class="w-5 h-5"></i>
                        @if($unreadCount > 0)
                            <span class="absolute -top-1 -right-1 bg-red-600 animate-pulse text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full shadow">
                                {{ $unreadCount }}
                            </span>
                        @endif
                    </button>
                    {{-- Dropdown --}}
                    <div x-show="open"
                        @click.away="open = false"
                        x-transition
                        class="absolute right-0 mt-3 w-80 bg-white rounded-xl shadow-xl border border-slate-100 z-50">
                        <div class="p-4 border-b border-slate-100 font-semibold text-sm">
                            Notifications
                        </div>
                        <div class="max-h-80 overflow-y-auto">
                            @forelse($notifications as $notification)
                                <a href="{{ route('communication.chat.show', $notification->data['chat_thread_id']) }}"
                                   class="block px-4 py-3 hover:bg-slate-50 border-b border-slate-50">
                                    <div class="text-sm font-medium text-slate-800">
                                        🚨 Flagged Message
                                    </div>
                                    <div class="text-xs text-slate-500 mt-1">
                                        {{ $notification->data['sender'] }}
                                    </div>
                                    <div class="text-xs text-slate-400 mt-1 truncate">
                                        {{ $notification->data['preview'] }}
                                    </div>
                                </a>
                            @empty
                                <div class="px-4 py-6 text-sm text-slate-400 text-center">
                                    No new notifications
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
                <button class="p-2 rounded-lg {{ $hoverClass }} transition">
                    <i data-lucide="help-circle" class="w-5 h-5"></i>
                </button>
            </div>
            <div class="h-8 w-[1px] bg-current opacity-10 mx-2"></div>
            {{-- Alpine Dropdown --}}
            <div x-data="{ open: false }" class="relative flex items-center gap-3">
                <div class="text-right hidden sm:block">
                    <div class="text-sm font-medium leading-none mb-1">{{ $user->name }}</div>
                </div>
                <button @click="open = !open" class="flex items-center gap-2 focus:outline-none">
                    <img src="{{ $user->profile_photo ? asset('storage/' . $user->profile_photo) : 'https://ui-avatars.com/api/?name=' . urlencode($user->name) }}"
                         class="w-10 h-10 rounded-full object-cover border {{ $borderClass }}">
                    <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                {{-- Dropdown Menu --}}
                <div x-show="open"
                     @click.away="open = false"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     class="absolute right-0 top-full mt-2 w-56 bg-white rounded-xl shadow-xl border border-slate-100 overflow-hidden z-50 text-slate-700">
                    <div class="p-4 bg-slate-50/50 border-b border-slate-100">
                        <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mb-1">Account</p>
                        <p class="text-sm font-semibold truncate">{{ $user->email }}</p>
                    </div>
                    <div class="p-2">
                        <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm hover:bg-slate-50 transition">
                            <i data-lucide="user" class="w-4 h-4 text-slate-400"></i> My Profile
                        </a>
                        <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm hover:bg-slate-50 transition">
                            <i data-lucide="megaphone" class="w-4 h-4 text-slate-400"></i> Announcement
                        </a>
                        <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm hover:bg-slate-50 transition">
                            <i data-lucide="alert-triangle" class="w-4 h-4 text-slate-400"></i> Deadlines
                        </a>
                        <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm hover:bg-slate-50 transition">
                            <i data-lucide="messages-square" class="w-4 h-4 text-slate-400"></i> Messages
                        </a>
                        <button @click="$dispatch('open-theme-modal'); open = false"
                                class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm hover:bg-slate-50 transition text-left">
                            <i data-lucide="palette" class="w-4 h-4 text-slate-400"></i> Theme Settings
                        </button>
                        <div class="my-1 border-t border-slate-100"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-red-600 hover:bg-red-50 transition text-left">
                                <i data-lucide="log-out" class="w-4 h-4"></i> Sign Out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>