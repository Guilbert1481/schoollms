<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="mb-4 flex items-center gap-2">
        <i data-lucide="info" class="h-4 w-4 text-indigo-600"></i>
        <h3 class="text-sm font-bold text-slate-800">Quick Information</h3>
    </div>
    @php
        $items = [
            ['calendar',     'Age',               $quick['age']],
            ['activity',     'Current Status',    $quick['current_status']],
            ['layers',       'Total Enrollments', $quick['total_enrollments']],
            ['trending-up',  'Promotions',        $quick['promotions']],
            ['clock',        'Last Updated',      $quick['last_updated']],
        ];
    @endphp
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        @foreach($items as [$icon, $label, $value])
            <div class="flex items-start gap-2">
                <i data-lucide="{{ $icon }}" class="mt-0.5 h-4 w-4 flex-none text-slate-400"></i>
                <div class="min-w-0">
                    <div class="text-[11px] font-semibold text-indigo-500">{{ $label }}</div>
                    <div class="text-sm font-semibold text-slate-800">{{ $value }}</div>
                </div>
            </div>
        @endforeach
    </div>
</div>
