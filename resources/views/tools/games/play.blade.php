@php
    $embed = $embed ?? false;
@endphp

{{-- Embedded (?embed=1): bare fullscreen layout — no sidebar/header — for the
     catalog's distraction-free game overlay. Direct visits keep the app chrome. --}}
@extends($embed ? 'layouts.game' : 'layouts.app')

@section('title', $game['title'])

@section('content')
@php
    // Resolve the partial path for the active game. Each game has its own
    // partial at resources/views/tools/games/games/{slug}.blade.php so they
    // can be developed independently. If the partial is missing we fall back
    // to a generic placeholder.
    $partial = 'tools.games.games.' . str_replace('-', '_', $game['slug']);
    if (! view()->exists($partial)) {
        $partial = 'tools.games.games._placeholder';
    }
@endphp

@if($embed)
{{-- Distraction-free stage: the game sits vertically centered in the viewport
     and is width-capped (readable inputs) instead of stretching edge to edge. --}}
<div class="min-h-screen w-full flex items-center justify-center p-4 md:p-6">
    <div class="w-full max-w-3xl">
        @include($partial, ['game' => $game])
    </div>
</div>
@else
<div class="mx-auto w-full max-w-7xl space-y-6 p-4 md:p-6">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
            <div class="flex items-center gap-2 text-xs text-slate-500">
                <a href="{{ route('tools.games.index') }}" class="hover:text-fuchsia-700">Gamified Quiz</a>
                <i data-lucide="chevron-right" class="w-3 h-3"></i>
                <span class="truncate">{{ $game['title'] }}</span>
            </div>
            <h1 class="mt-1 text-2xl md:text-3xl font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="{{ $game['icon'] }}" class="w-7 h-7 text-fuchsia-600"></i>
                <span class="truncate">{{ $game['title'] }}</span>
            </h1>
            <p class="text-sm text-slate-600 mt-1">{{ $game['description'] }}</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('tools.games.index') }}"
               class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Back to Games
            </a>
        </div>
    </div>

    {{-- Hero banner --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br {{ $game['color'] }} p-6 md:p-8 text-white shadow-lg">
        <div class="absolute -top-10 -right-10 h-40 w-40 rounded-full bg-white/20 blur-2xl"></div>
        <div class="absolute -bottom-10 -left-10 h-40 w-40 rounded-full bg-black/20 blur-2xl"></div>
        <div class="relative flex flex-wrap items-end justify-between gap-3">
            <div>
                <div class="inline-flex items-center gap-2 rounded-lg border border-white/30 bg-white/15 px-2 py-1 text-xs font-medium backdrop-blur-sm">
                    <i data-lucide="sparkles" class="w-4 h-4"></i> Game Template
                </div>
                <h2 class="mt-3 text-2xl md:text-3xl font-extrabold drop-shadow">
                    {{ $game['title'] }}
                </h2>
            </div>
            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold border border-white/40 bg-white/15">
                Slug: {{ $game['slug'] }}
            </span>
        </div>
    </div>

    {{-- Game body (modular partial) --}}
    <section class="rounded-2xl border border-slate-200 bg-white p-4 md:p-6 shadow-sm">
        @include($partial, ['game' => $game])
    </section>
</div>
@endif

@push('scripts')
    <script src="{{ asset('js/tools/games/play.js') }}" defer></script>
@endpush
@endsection
