{{-- views/layouts/sidebar.blade.php --}}

@php
$user = auth()->user();
$school = $user->school ?? null;

$identity = $user->dashboard_identity ?? [];
$sidebar = $identity['sidebar'] ?? [
    'mode'  => 'dark',
    'style' => 'solid',
    'color' => 'slate',
];

$mode = $sidebar['mode'];
$style = $sidebar['style'];
$colorKey = $sidebar['color'];

$theme = config('theme.colors');
$config = $theme[$colorKey] ?? $theme['slate'];

$bgClass = $style === 'gradient'
    ? $config['gradient_vertical']
    : $config['solid'];

$textClass = $mode === 'light'
    ? $config['text_light_mode']
    : $config['text_dark_mode'];

$borderClass = $mode === 'light'
    ? $config['border_light_mode']
    : $config['border_dark_mode'];

$hoverClass = $mode === 'light'
    ? $config['hover_light_mode']
    : $config['hover_dark_mode'];
@endphp

{{-- Sidebar Fixed Position --}}
<div class="fixed left-0 top-0 h-screen w-64 flex flex-col shadow-2xl {{ $bgClass }} {{ $textClass }} z-40">
    {{-- Branding --}}
    <div class="p-6 border-b {{ $borderClass }} flex items-center gap-3">
        @if($school && $school->school_logo)
            <img src="{{ asset('storage/' . $school->school_logo) }}" class="w-10 h-10 rounded-xl object-cover shadow-lg">
        @endif
        <div>

            <h2 class="font-bold text-sm">{{ $school->school_name ?? 'No Record Found or Not Fetching' }}</h2>
        </div>
    </div>

    {{-- Menu --}}
    <div class="flex-1 overflow-y-auto">
        @include('partials.sidebar', ['hoverClass' => $hoverClass, 'textClass' => $textClass])
    </div>
</div>