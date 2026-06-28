@extends('layouts.app')

@section('content')
<div class="mx-auto w-full max-w-7xl space-y-6 p-4 md:p-6">

    {{-- Header --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('registrar.student-ledgers.index') }}"
           class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-sm font-semibold text-slate-500 hover:bg-slate-100 hover:text-slate-800">
            <i data-lucide="arrow-left" class="h-4 w-4"></i> Back
        </a>
        <div>
            <h1 class="text-xl font-extrabold text-slate-800">
                {{ trim(implode(' ', array_filter([$student->first_name, $student->middle_name, $student->last_name]))) ?: 'Student' }}
            </h1>
            <p class="text-xs font-semibold text-indigo-600">{{ $student->student_number ?? '—' }}</p>
        </div>
    </div>

    {{-- Tabbed panel (content intentionally blank for now) --}}
    @php
        $tabs = [
            'profile'   => 'Profile',
            'history'   => 'Enrollment History',
            'documents' => 'Documents',
            'fees'      => 'Fees & Payments',
        ];
    @endphp
    <div x-data="{ tab: 'profile' }" class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <nav class="flex flex-wrap gap-1 border-b border-slate-200 px-2">
            @foreach($tabs as $key => $label)
                <button type="button"
                        @click="tab = '{{ $key }}'"
                        :class="tab === '{{ $key }}'
                            ? 'border-indigo-600 text-indigo-600'
                            : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="border-b-2 px-4 py-3 text-sm font-semibold transition">
                    {{ $label }}
                </button>
            @endforeach
        </nav>

        <div class="p-6">
            @foreach($tabs as $key => $label)
                <div x-show="tab === '{{ $key }}'" x-cloak>
                    <div class="flex flex-col items-center justify-center py-20 text-center text-slate-400">
                        <i data-lucide="layout-dashboard" class="mb-3 h-8 w-8"></i>
                        <p class="text-sm font-semibold text-slate-500">{{ $label }}</p>
                        <p class="text-xs">Nothing here yet.</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide?.createIcons) window.lucide.createIcons();
    });
</script>
@endsection
