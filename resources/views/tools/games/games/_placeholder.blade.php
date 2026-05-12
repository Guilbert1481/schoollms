{{-- Generic placeholder used when a game-specific partial isn't authored yet. --}}
<div class="text-center py-10">
    <div class="mx-auto mb-3 inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-fuchsia-50 ring-1 ring-fuchsia-200">
        <i data-lucide="gamepad-2" class="w-6 h-6 text-fuchsia-600"></i>
    </div>
    <h3 class="text-lg font-semibold text-slate-800">Game template ready for content</h3>
    <p class="mt-1 text-sm text-slate-600 max-w-md mx-auto">
        This game shell is wired up. Drop your interactive logic into
        <code class="px-1.5 py-0.5 rounded bg-slate-100 text-xs">resources/views/tools/games/games/{{ str_replace('-', '_', $game['slug']) }}.blade.php</code>
        to replace this placeholder.
    </p>
</div>
