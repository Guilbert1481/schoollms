<input type="hidden" id="searchRoute" value="{{ route('admission.search') }}">

<div class="w-full flex items-center justify-end h-16 px-6">

    <div x-data="{ open: false }" class="relative">

        <!-- Arrow Trigger -->
        <button 
            @click="open = !open"
            class="flex items-center focus:outline-none"
        >
            <svg 
                class="w-5 h-5 text-gray-500 transition-transform duration-200"
                :class="{ 'rotate-180': open }"
                fill="none" stroke="currentColor" viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <!-- Dropdown -->
        <div 
            x-show="open"
            @click.away="open = false"
            x-transition
            class="absolute right-0 mt-3 w-64 bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden z-50"
        >

            <!-- User Info -->
            <div class="p-4 border-b bg-gray-50">
                <div class="text-sm font-semibold text-gray-800">
                    {{ auth()->user()->name }}
                </div>
                <div class="text-xs text-gray-500">
                    {{ auth()->user()->email }}
                </div>
            </div>

            <!-- Menu Items -->
            <div class="py-2">

                <a href="#"
                   class="flex items-center gap-3 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 transition">
                    <i data-lucide="user" class="w-4 h-4"></i>
                    Profile & Account
                </a>

                <a href="#"
                   class="flex items-center gap-3 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 transition">
                    <i data-lucide="settings" class="w-4 h-4"></i>
                    System Preferences
                </a>

                <!-- Theme Settings -->
                <a href="{{ route('theme.edit') }}"
                class="flex items-center gap-3 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 transition">
                    <i data-lucide="palette" class="w-4 h-4"></i>
                    Theme Settings
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="w-full flex items-center gap-3 px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition text-left">
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                        Log Out
                    </button>
                </form>

            </div>

        </div>

    </div>

</div>
