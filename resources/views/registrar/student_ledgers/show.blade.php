@extends('layouts.app')

@section('content')
<div class="w-full space-y-4">

    {{-- Back link --}}
    <a href="{{ route('registrar.student-registry.index') }}"
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
    <style>
        /* Mobile (< 768px): the tab bar collapses into a hamburger dropdown.
           Real @media queries so it works independent of the compiled build. */
        .ptabs-toggle { display: none; }
        @media (max-width: 767.98px) {
            .ptabs-toggle {
                display: flex; align-items: center; justify-content: space-between;
                gap: .5rem; width: 100%;
                padding: .5rem .75rem; border: 1px solid #e2e8f0; border-radius: .5rem;
                background: #fff; font-size: .875rem; font-weight: 700; color: #334155;
            }
            .ptabs-nav {
                display: none; position: absolute; left: 0; right: 0; top: calc(100% + 4px);
                z-index: 40; flex-direction: column; gap: .25rem;
                background: #fff; border: 1px solid #e2e8f0; border-radius: .5rem;
                box-shadow: 0 12px 28px -8px rgba(15, 23, 42, .25); padding: .375rem;
            }
            .ptabs-nav.is-open { display: flex; }
        }
        @media (min-width: 768px) {
            .ptabs-nav { display: flex !important; }
        }
    </style>

    <div class="relative" x-data="{ tab: 'profile', menu: false, labels: {{ \Illuminate\Support\Js::from($tabs) }} }"
         @click.outside="menu = false">
        {{-- Mobile-only hamburger trigger (shows the active tab). --}}
        <button type="button" class="ptabs-toggle" @click="menu = ! menu">
            <span class="inline-flex items-center gap-2 truncate">
                <i data-lucide="menu" class="h-4 w-4 shrink-0 text-indigo-600"></i>
                <span class="truncate" x-text="labels[tab]"></span>
            </span>
            <i data-lucide="chevron-down" class="h-4 w-4 shrink-0 text-slate-400"></i>
        </button>

        <nav class="ptabs-nav flex flex-wrap gap-1 border-b border-slate-200" :class="{ 'is-open': menu }">
            @foreach($tabs as $key => $label)
                <button type="button"
                        @click="tab = '{{ $key }}'; menu = false"
                        :class="tab === '{{ $key }}'
                            ? 'border-indigo-600 text-indigo-600'
                            : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="border-b-2 px-4 py-3 text-sm font-semibold transition text-left">
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
