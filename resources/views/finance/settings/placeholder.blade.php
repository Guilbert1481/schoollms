@extends('layouts.app')

@section('content')
<div class="w-full space-y-5">

    <div class="flex items-center gap-3">
        <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
            <i data-lucide="{{ $icon ?? 'settings' }}" class="h-5 w-5"></i>
        </span>
        <div>
            <h1 class="text-2xl font-bold text-slate-800">{{ $title }}</h1>
            <p class="text-sm text-slate-500">{{ $description ?? '' }}</p>
        </div>
    </div>

    @if(!empty($items))
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-3 text-sm font-bold text-slate-800">Currently in use</h2>
            <div class="flex flex-wrap gap-2">
                @foreach($items as $item)
                    <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-sm font-semibold text-slate-700">{{ $item }}</span>
                @endforeach
            </div>
            <p class="mt-4 text-xs text-slate-400">These are the values currently used by the system. A management UI for this section is coming soon.</p>
        </div>
    @else
        <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white py-16 text-center">
            <i data-lucide="{{ $icon ?? 'settings' }}" class="mb-3 h-8 w-8 text-slate-300"></i>
            <p class="text-sm font-semibold text-slate-500">{{ $title }}</p>
            <p class="mt-1 text-xs text-slate-400">This section is coming soon.</p>
        </div>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide?.createIcons) window.lucide.createIcons();
    });
</script>
@endsection
