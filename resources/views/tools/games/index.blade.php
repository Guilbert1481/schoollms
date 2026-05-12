@extends('layouts.app')

{{--
    Tailwind safelist (gradients are composed in PHP so the JIT cannot
    discover them via content scanning). Keep this comment block in sync
    with App\Http\Controllers\Tools\Games\GamesController::GAMES.
    from-amber-700 via-orange-800 to-red-900
    from-cyan-700 via-sky-800 to-blue-900
    from-emerald-700 via-teal-800 to-cyan-900
    from-slate-700 via-zinc-700 to-stone-800
    from-fuchsia-700 via-purple-800 to-indigo-900
    from-blue-700 via-sky-800 to-indigo-900
    from-lime-700 via-emerald-800 to-teal-900
    from-violet-700 via-purple-800 to-fuchsia-900
    from-rose-700 via-pink-800 to-fuchsia-900
    from-pink-700 via-rose-800 to-red-900
    from-stone-700 via-zinc-800 to-slate-900
    from-orange-700 via-amber-800 to-yellow-900
    from-indigo-700 via-blue-800 to-sky-900
    from-teal-700 via-cyan-800 to-sky-900
    from-emerald-700 via-green-800 to-lime-900
    from-purple-700 via-violet-800 to-indigo-900
    from-red-700 via-rose-800 to-pink-900
    from-green-700 via-emerald-800 to-teal-900
--}}

@section('content')
<div class="mx-auto w-full max-w-7xl space-y-6 p-4 md:p-6">

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

    {{-- Catalog --}}
    <div id="gamesGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-5">
        @foreach($games as $game)
            <article
                data-name="{{ \Illuminate\Support\Str::lower($game['title']) }}"
                class="game-card rounded-2xl border border-slate-200 bg-white p-3 shadow-sm hover:shadow-lg transition-all">
                <div class="relative mb-3 overflow-hidden rounded-xl bg-gradient-to-br {{ $game['color'] }} p-3 h-28">
                    <div class="absolute -top-6 -right-6 h-24 w-24 rounded-full bg-white/20 blur-xl"></div>
                    <div class="absolute -bottom-8 -left-6 h-24 w-24 rounded-full bg-black/20 blur-xl"></div>

                    <div class="relative flex h-full items-end">
                        <div class="inline-flex items-center gap-2 rounded-lg border border-white/30 bg-white/20 px-2 py-1 text-white backdrop-blur-sm">
                            <i data-lucide="{{ $game['icon'] }}" class="w-4 h-4"></i>
                            <span class="text-xs font-medium">{{ $game['title'] }}</span>
                        </div>
                    </div>
                </div>

                <div class="px-1 pb-1">
                    <h2 class="text-base font-semibold text-slate-800">{{ $game['title'] }}</h2>
                    <p class="mt-2 text-sm text-slate-600 min-h-[60px]">{{ $game['description'] }}</p>

                    <a href="{{ route('tools.games.play', $game['slug']) }}"
                       class="mt-4 block w-full rounded-lg border border-fuchsia-200 bg-fuchsia-50 px-3 py-2 text-center text-sm font-medium text-fuchsia-700 hover:bg-fuchsia-100">
                        Open Game
                    </a>
                </div>
            </article>
        @endforeach
    </div>
</div>

@push('scripts')
    <script src="{{ asset('js/tools/games/index.js') }}" defer></script>
@endpush
@endsection
