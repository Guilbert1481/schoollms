{{-- views/user/partials/theme-content.blade.php --}}

<div class="p-6 lg:p-8 w-full mx-auto">

    {{-- Header Section --}}
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">
            System Aesthetics
        </h2>
        <p class="text-slate-500 text-sm">
            Global interface configuration and brand identity settings.
        </p>
    </div>

    @if(session('success'))
        <div class="mb-6 p-3 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-xl text-sm font-medium">
            {{ session('success') }}
        </div>
    @endif

    @php
    $user = auth()->user();
    $identity = $user->dashboard_identity ?? [];
    $sidebar = $identity['sidebar'] ?? [];
    $header = $identity['header'] ?? [];
    $kpi = $identity['kpi'] ?? [];
    @endphp

    <form method="POST" action="{{ route('theme.update') }}" id="theme-form">
        @csrf
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-stretch">
            
            {{-- ===================== --}}
            {{-- COLUMN 1: SIDEBAR --}}
            {{-- ===================== --}}
            <div class="bg-white rounded-[2rem] p-6 shadow-sm border border-slate-100 flex flex-col">
                
                <div class="flex items-center gap-3 mb-6 border-b border-slate-50 pb-4">
                    <div class="w-8 h-8 bg-slate-900 rounded-lg flex items-center justify-center text-white text-xs">
                        SB
                    </div>
                    <h3 class="font-bold text-slate-800">Sidebar Engine</h3>
                </div>

                <div class="space-y-6">

                    {{-- Sidebar Mode --}}
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                            Text Contrast
                        </label>
                        <select name="sidebar_mode"
                            class="w-full bg-slate-50 ring-1 ring-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                            <option value="dark" {{ ($sidebar['mode'] ?? '') == 'dark' ? 'selected' : '' }}>Light Text (On Dark BG)</option>
                            <option value="light" {{ ($sidebar['mode'] ?? '') == 'light' ? 'selected' : '' }}>Dark Text (On Light BG)</option>
                        </select>
                    </div>

                    {{-- Sidebar Style --}}
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                            Style
                        </label>
                        <select name="sidebar_style"
                            class="w-full bg-slate-50 ring-1 ring-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                            <option value="solid" {{ ($sidebar['style'] ?? '') == 'solid' ? 'selected' : '' }}>Solid Color</option>
                            <option value="gradient" {{ ($sidebar['style'] ?? '') == 'gradient' ? 'selected' : '' }}>Vertical Gradient</option>
                        </select>
                    </div>

                    {{-- Sidebar Color --}}
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                            Accent Palette
                        </label>
                        <select name="sidebar_color"
                            class="w-full bg-slate-50 ring-1 ring-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                            <optgroup label="Standard Colors">
                                @foreach(['slate', 'blue', 'emerald', 'purple', 'rose', 'amber', 'cyan', 'indigo', 'teal', 'fuchsia'] as $color)
                                    <option value="{{ $color }}" {{ ($sidebar['color'] ?? '') == $color ? 'selected' : '' }}>
                                        {{ ucfirst($color) }}
                                    </option>
                                @endforeach
                            </optgroup>
                            <optgroup label="Dark Interface">
                                <option value="black" {{ ($sidebar['color'] ?? '') == 'black' ? 'selected' : '' }}>Black</option>
                                <option value="navy" {{ ($sidebar['color'] ?? '') == 'navy' ? 'selected' : '' }}>Navy Blue</option>
                            </optgroup>
                            <optgroup label="Light Interface">
                                <option value="white" {{ ($sidebar['color'] ?? '') == 'white' ? 'selected' : '' }}>Pure White</option>
                                <option value="light" {{ ($sidebar['color'] ?? '') == 'light' ? 'selected' : '' }}>Soft Gray</option>
                                <option value="silver" {{ ($sidebar['color'] ?? '') == 'silver' ? 'selected' : '' }}>Metallic Silver</option>
                                <option value="sky" {{ ($sidebar['color'] ?? '') == 'sky' ? 'selected' : '' }}>Pale Sky Blue</option>
                            </optgroup>
                        </select>
                    </div>

                </div>
            </div>


            {{-- ===================== --}}
            {{-- COLUMN 2: HEADER --}}
            {{-- ===================== --}}
            <div class="bg-white rounded-[2rem] p-6 shadow-sm border border-slate-100 flex flex-col">
                <div class="flex items-center gap-3 mb-6 border-b border-slate-50 pb-4">
                    <div class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center text-indigo-600 text-xs">
                        HD
                    </div>
                    <h3 class="font-bold text-slate-800">Header Display</h3>
                </div>

                <div class="space-y-6">
                    {{-- Header Mode --}}
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                            Text Contrast
                        </label>
                        <select name="header_mode"
                            class="w-full bg-slate-50 ring-1 ring-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                            <option value="dark" {{ ($header['mode'] ?? '') == 'dark' ? 'selected' : '' }}>Light Text (On Dark BG)</option>
                            <option value="light" {{ ($header['mode'] ?? '') == 'light' ? 'selected' : '' }}>Dark Text (On Light BG)</option>
                        </select>
                    </div>

                    {{-- Header Style --}}
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                            Texture
                        </label>
                        <select name="header_style"
                            class="w-full bg-slate-50 ring-1 ring-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                            <option value="solid" {{ ($header['style'] ?? '') == 'solid' ? 'selected' : '' }}>Solid Color</option>
                            <option value="gradient" {{ ($header['style'] ?? '') == 'gradient' ? 'selected' : '' }}>Horizontal Gradient</option>
                        </select>
                    </div>

                    {{-- Header Color --}}
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                            Background Color
                        </label>
                        <select name="header_color"
                            class="w-full bg-slate-50 ring-1 ring-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                            <optgroup label="Standard Colors">
                                @foreach(['slate', 'blue', 'emerald', 'purple', 'rose', 'amber', 'cyan', 'indigo', 'teal', 'fuchsia'] as $color)
                                    <option value="{{ $color }}" {{ ($header['color'] ?? '') == $color ? 'selected' : '' }}>
                                        {{ ucfirst($color) }}
                                    </option>
                                @endforeach
                            </optgroup>
                            <optgroup label="Dark Interface">
                                <option value="black" {{ ($header['color'] ?? '') == 'black' ? 'selected' : '' }}>Black</option>
                                <option value="navy" {{ ($header['color'] ?? '') == 'navy' ? 'selected' : '' }}>Navy Blue</option>
                            </optgroup>
                            <optgroup label="Light Interface">
                                <option value="white" {{ ($header['color'] ?? '') == 'white' ? 'selected' : '' }}>Pure White</option>
                                <option value="light" {{ ($header['color'] ?? '') == 'light' ? 'selected' : '' }}>Soft Gray</option>
                                <option value="silver" {{ ($header['color'] ?? '') == 'silver' ? 'selected' : '' }}>Metallic Silver</option>
                                <option value="sky" {{ ($header['color'] ?? '') == 'sky' ? 'selected' : '' }}>Pale Sky Blue</option>
                            </optgroup>
                        </select>
                    </div>
                </div>
            </div>

            {{-- ===================== --}}
            {{-- COLUMN 3: KPI ENGINE --}}
            {{-- ===================== --}}
            <div class="bg-white rounded-[2rem] p-6 shadow-sm border border-slate-100 flex flex-col">

                <div class="flex items-center gap-3 mb-6 border-b border-slate-50 pb-4">
                    <div class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center text-indigo-600 text-xs">
                        KPI
                    </div>
                    <h3 class="font-bold text-slate-800">KPI Engine</h3>
                </div>

                <div class="space-y-6 flex-1">

                    {{-- Card Style --}}
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                            Card Style
                        </label>
                        <select name="kpi_card_style"
                            class="w-full bg-slate-50 ring-1 ring-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                            <option value="soft" {{ ($kpi['card_style'] ?? '') == 'soft' ? 'selected' : '' }}>Soft</option>
                            <option value="glass" {{ ($kpi['card_style'] ?? '') == 'glass' ? 'selected' : '' }}>Glass</option>
                            <option value="flat" {{ ($kpi['card_style'] ?? '') == 'flat' ? 'selected' : '' }}>Flat</option>
                        </select>
                    </div>

                    {{-- Border Style --}}
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                            Border Style
                        </label>
                        <select name="kpi_border_style"
                            class="w-full bg-slate-50 ring-1 ring-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                            <option value="subtle" {{ ($kpi['border_style'] ?? '') == 'subtle' ? 'selected' : '' }}>Subtle</option>
                            <option value="bold" {{ ($kpi['border_style'] ?? '') == 'bold' ? 'selected' : '' }}>Bold</option>
                            <option value="none" {{ ($kpi['border_style'] ?? '') == 'none' ? 'selected' : '' }}>None</option>
                        </select>
                    </div>

                    {{-- Background Tint --}}
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                            Background Tint
                        </label>
                        <select name="kpi_background_tint"
                            class="w-full bg-slate-50 ring-1 ring-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                            <option value="neutral" {{ ($kpi['background_tint'] ?? '') == 'neutral' ? 'selected' : '' }}>Neutral</option>
                            <option value="brand" {{ ($kpi['background_tint'] ?? '') == 'brand' ? 'selected' : '' }}>Brand</option>
                            <option value="dark" {{ ($kpi['background_tint'] ?? '') == 'dark' ? 'selected' : '' }}>Dark</option>
                        </select>
                    </div>

                    {{-- Accent Color --}}
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                            Accent Color
                        </label>
                        <select name="kpi_accent_color"
                            class="w-full bg-slate-50 ring-1 ring-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">

                            <option value="default" {{ ($kpi['accent_color'] ?? '') == 'default' ? 'selected' : '' }}>System Default</option>

                            @foreach(['slate','blue','emerald','purple','rose','amber','cyan','indigo','teal','fuchsia'] as $color)
                                <option value="{{ $color }}" {{ ($kpi['accent_color'] ?? '') == $color ? 'selected' : '' }}>
                                    {{ ucfirst($color) }}
                                </option>
                            @endforeach
                        </select>

                    </div>


                </div>

                {{-- Submit --}}
                <div class="mt-8">
                    <button type="submit"
                        class="w-full py-4 bg-slate-900 text-white rounded-2xl font-black text-sm uppercase tracking-widest hover:bg-indigo-600 transition-all shadow-xl active:scale-95">
                        Update Identity
                    </button>
                </div>

            </div>


        </div>
    </form>

</div>