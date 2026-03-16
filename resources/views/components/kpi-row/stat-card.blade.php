{{-- resources/views/components/kpi-row/stat-card.blade.php --}}

@props([
    'label',
    'value' => 0,
    'icon' => 'bar-chart',
    'color' => 'blue'
])

<div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm transition hover:shadow-md">
    <div class="flex justify-between items-center">
        <div>
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">
                {{ $label }}
            </p>

            <h3 class="text-2xl font-bold mt-2 text-gray-900 dark:text-white">
                {{ $value }}
            </h3>
        </div>

        <div class="p-3 rounded-xl bg-{{ $color }}-50 dark:bg-{{ $color }}-900/20 text-{{ $color }}-600">
            <i data-lucide="{{ $icon }}" class="w-5 h-5"></i>
        </div>
    </div>
</div>
