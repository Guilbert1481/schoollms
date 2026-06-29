@extends('layouts.app')

@section('content')
<style>
    /* Header field row — responsive without relying on Tailwind's lg:grid-cols-7. */
    .sl-id-fields { display: grid; gap: 0.75rem 1.5rem; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    @media (min-width: 640px)  { .sl-id-fields { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    @media (min-width: 1024px) { .sl-id-fields { grid-template-columns: repeat(7, minmax(0, 1fr)); } }
</style>
<div class="mx-auto w-full max-w-7xl space-y-4 p-4 md:p-6">

    {{-- Back link --}}
    <a href="{{ route('registrar.student-ledgers.index') }}"
       class="inline-flex items-center gap-1 text-sm font-semibold text-indigo-600 hover:text-indigo-800">
        <i data-lucide="arrow-left" class="h-4 w-4"></i> Back to Student List
    </a>

    {{-- Student header card --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-start gap-5">
            {{-- Avatar --}}
            <div class="h-16 w-16 flex-none overflow-hidden rounded-full bg-slate-100 ring-1 ring-slate-200">
                @if($header['photo'])
                    <img src="{{ asset('storage/'.$header['photo']) }}" alt="{{ $header['name'] }}" class="h-full w-full object-cover">
                @else
                    <div class="flex h-full w-full items-center justify-center text-slate-400">
                        <i data-lucide="user" class="h-7 w-7"></i>
                    </div>
                @endif
            </div>

            <div class="min-w-0 flex-1">
                {{-- Name + status --}}
                <div class="mb-4 flex flex-wrap items-center gap-3">
                    <h2 class="text-lg font-extrabold text-slate-900">{{ $header['name'] }}</h2>
                    @if($header['status_key'])
                        {!! \App\Support\EnrollmentStatuses::pill($header['status_key']) !!}
                    @endif
                </div>

                {{-- Fields (single row on wide screens) --}}
                @php
                    $fields = [
                        ['Student ID',          $header['student_id'],    true],
                        ['LRN',                 $header['lrn'],           false],
                        ['Date of Birth',       $header['date_of_birth'], false],
                        ['Gender',              $header['gender'],        false],
                        ['Current Grade Level', $header['grade_level'],   false],
                        ['Section',             $header['section'],       false],
                        ['Academic Year',       $header['academic_year'], false],
                    ];
                @endphp
                <div class="sl-id-fields">
                    @foreach($fields as [$label, $value, $accent])
                        <div class="min-w-0">
                            <div class="text-[11px] font-semibold text-indigo-500">{{ $label }}</div>
                            <div class="truncate text-sm font-semibold {{ $accent ? 'text-indigo-600' : 'text-slate-800' }}"
                                 title="{{ $value }}">{{ $value }}</div>
                            @if($label === 'Academic Year' && $header['term'])
                                <div class="text-xs text-slate-500">{{ $header['term'] }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
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
