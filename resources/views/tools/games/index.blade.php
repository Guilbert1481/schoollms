@extends('layouts.app')

@section('content')
@php
    // Pastel tile palette (inline hex — purge-safe): [tile background, icon color].
    $tilePalette = [
        ['#fdf3d8', '#a16207'], // amber
        ['#fbdde2', '#be123c'], // rose
        ['#d3f4e0', '#059669'], // green
        ['#d5f0f2', '#0f766e'], // teal
        ['#fce7d8', '#9a3412'], // orange
        ['#e3f9d3', '#4d7c0f'], // lime
        ['#f7d9f7', '#a21caf'], // fuchsia
        ['#dbe7fb', '#1d4ed8'], // blue
        ['#d7ecfb', '#0369a1'], // sky
        ['#e5ddfb', '#6d28d9'], // violet
    ];

    // Tile view-model handed to the detail modal via Alpine (@js).
    $gameTiles = collect($games)->values()->map(function ($g, $i) use ($tilePalette) {
        [$bg, $fg] = $tilePalette[$i % count($tilePalette)];

        return [
            'slug'        => $g['slug'],
            'title'       => $g['title'],
            'description' => $g['description'],
            'icon'        => $g['icon'],
            'url'         => route('tools.games.play', $g['slug']),
            'bg'          => $bg,
            'fg'          => $fg,
        ];
    })->all();
@endphp

<div x-data="{ detail: null, playing: null }"
     x-init="window.addEventListener('message', e => { if (e.data && e.data.type === 'schoollms:game-exit') playing = null })"
     class="mx-auto w-full max-w-7xl space-y-6 p-4 md:p-6">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="gamepad-2" class="w-7 h-7 text-fuchsia-600"></i>
                Gamified Quiz
            </h1>
            <p class="text-sm md:text-base text-slate-600 mt-1">
                Pick a game template to turn quiz content into an interactive challenge.
            </p>
        </div>
        <a href="{{ route('tools.index') }}"
           class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            Back to Tools Hub
        </a>
    </div>

    {{-- Filter --}}
    <div class="flex items-center gap-2">
        <input type="text" id="gamesFilter"
               placeholder="Search games…"
               class="w-full sm:w-72 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-fuchsia-400">
        <span id="gamesCount" class="text-xs text-slate-500"></span>
    </div>

    {{-- Catalog: icon tiles + labels only (6 per row on desktop); details open in the modal.
         .game-card + data-name are kept so js/tools/games/index.js search keeps working. --}}
    <div id="gamesGrid" class="grid grid-cols-3 md:grid-cols-4 xl:grid-cols-6 gap-x-4 gap-y-8">
        @foreach($gameTiles as $game)
            {{-- NOTE: the handler attribute MUST be double-quoted — @js() emits
                 JSON.parse('…') whose single quotes would terminate a
                 single-quoted attribute and leak the rest as page text. --}}
            <button type="button"
                    data-name="{{ \Illuminate\Support\Str::lower($game['title']) }}"
                    x-on:click="detail = @js($game); $nextTick(() => window.lucide && lucide.createIcons())"
                    class="game-card group flex flex-col items-center focus:outline-none">
                <span class="flex h-20 w-20 items-center justify-center rounded-[1.5rem] shadow-sm transition duration-150 group-hover:scale-105 group-hover:shadow-md"
                      style="background-color: {{ $game['bg'] }};">
                    <i data-lucide="{{ $game['icon'] }}" class="h-8 w-8" style="color: {{ $game['fg'] }};"></i>
                </span>
                <span class="mt-3 text-sm font-semibold text-slate-700 text-center leading-tight">
                    {{ $game['title'] }}
                </span>
            </button>
        @endforeach
    </div>

    {{-- Game detail modal (description + actions) --}}
    <div x-show="detail !== null" x-cloak x-transition.opacity
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4"
         x-on:click.self="detail = null"
         x-on:keydown.escape.window="detail = null">
        <template x-if="detail">
            <div class="w-full max-w-sm rounded-2xl bg-white shadow-xl border border-slate-200 p-6">
                <div class="flex items-start justify-between">
                    <span class="flex h-14 w-14 items-center justify-center rounded-2xl"
                          :style="`background-color: ${detail.bg}`">
                        <i :data-lucide="detail.icon" class="h-7 w-7" :style="`color: ${detail.fg}`"></i>
                    </span>
                    <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100" x-on:click="detail = null">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <h3 class="mt-4 text-lg font-bold text-slate-800" x-text="detail.title"></h3>
                <p class="mt-2 text-sm text-slate-600 leading-relaxed" x-text="detail.description"></p>

                <div class="mt-5 flex items-center justify-end gap-2">
                    <button type="button"
                            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                            x-on:click="detail = null">
                        Close
                    </button>
                    <button type="button"
                            class="rounded-lg border border-fuchsia-200 bg-fuchsia-50 px-4 py-2 text-sm font-medium text-fuchsia-700 hover:bg-fuchsia-100"
                            x-on:click="playing = detail; detail = null; $nextTick(() => window.lucide && lucide.createIcons())">
                        Open Game
                    </button>
                </div>
            </div>
        </template>
    </div>

    {{-- Fullscreen game overlay: covers the whole screen (sidebar/header hidden
         behind it); the game loads distraction-free via ?embed=1 in an iframe.
         Exit lives in the game's ☰ menu, which postMessages 'schoollms:game-exit'
         back here (see x-init on the page root) to unload the game. --}}
    <template x-if="playing">
        <div class="fixed inset-0 z-[100] bg-slate-900">
            <iframe :src="playing.url + '?embed=1'"
                    :title="playing.title"
                    class="absolute inset-0 h-full w-full border-0 bg-white"
                    allowfullscreen></iframe>
        </div>
    </template>
</div>

@push('scripts')
    <script src="{{ asset('js/tools/games/index.js') }}" defer></script>
@endpush
@endsection
