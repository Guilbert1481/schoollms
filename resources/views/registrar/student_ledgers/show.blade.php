@extends('layouts.app')

@section('content')
<div class="mx-auto w-full max-w-7xl space-y-4 p-4 md:p-6">

    {{-- Back link --}}
    <a href="{{ route('registrar.student-ledgers.index') }}"
       class="inline-flex items-center gap-1 text-sm font-semibold text-indigo-600 hover:text-indigo-800">
        <i data-lucide="arrow-left" class="h-4 w-4"></i> Back to Student List
    </a>

    {{-- Tabbed panel --}}
    @php
        $tabs = [
            'profile'   => 'Profile',
            'history'   => 'Enrollment History',
            'documents' => 'Documents',
            'fees'      => 'Fees & Payments',
        ];
    @endphp
    <div x-data="{ tab: 'profile' }">
        <nav class="flex flex-wrap gap-1 border-b border-slate-200">
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

        <div class="pt-6">
            {{-- Profile tab (wired) --}}
            <div x-show="tab === 'profile'" x-cloak>
                @include('registrar.student_ledgers.partials.profile')
            </div>

            {{-- Remaining tabs — blank for now --}}
            @foreach(['history' => 'Enrollment History', 'documents' => 'Documents', 'fees' => 'Fees & Payments'] as $key => $label)
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
