{{-- resources/views/components/kpi-row/kpi-shell.blade.php --}}


@php

    $identity = app(\App\Services\DashboardIdentityService::class)
        ->forUser(auth()->user());

    $kpi = $identity['kpi'] ?? [];

    $cardStyle = $kpi['card_style'] ?? 'soft';
    $borderStyle = $kpi['border_style'] ?? 'subtle';
    $backgroundTint = $kpi['background_tint'] ?? 'neutral';
    $accentColor = $kpi['accent_color'] ?? 'indigo';


    // Card Style
    $cardClasses = match ($cardStyle) {
        'glass' => 'bg-white/10 backdrop-blur-xl',
        'flat'  => 'bg-white dark:bg-gray-800',
        default => "bg-{$accentColor}-50 dark:bg-{$accentColor}-900/10 shadow-sm",
    };

    // Border Style
    $borderClasses = match ($borderStyle) {
        'bold'   => 'border-2 border-gray-300 dark:border-gray-600',
        'none'   => 'border-0',
        default  => 'border border-gray-100 dark:border-gray-700',
    };

    // Background Tint
    $tintClasses = match ($backgroundTint) {
        'brand' => 'ring-1 ring-blue-200 dark:ring-blue-800',
        'dark'  => 'bg-gray-900 text-white',
        default => '',
    };
@endphp


<div class="{{ $cardClasses }} {{ $borderClasses }} {{ $tintClasses }} p-6 rounded-2xl transition hover:shadow-lg">


    <div class="flex justify-between items-start">

        <div>
            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">
                {{ $title }}
            </p>
            <div class="text-3xl font-black mt-2 text-gray-900 dark:text-gray">
                {{ $slot }}
            </div>
        </div>

        <div class="p-3 rounded-xl bg-{{ $accentColor }}-100 text-{{ $accentColor }}-600 dark:bg-{{ $accentColor }}-900/30 dark:text-{{ $accentColor }}-400">
            <i data-lucide="{{ $icon }}" class="w-6 h-6"></i>
        </div>

    </div>
</div>
