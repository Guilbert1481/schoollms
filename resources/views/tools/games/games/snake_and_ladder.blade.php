{{-- Snake and Ladder — game stub --}}
@php
    // Build a 10x10 board numbered like a real snake-and-ladder grid:
    // row 1 (bottom) goes 1→10 left→right, row 2 goes 20→11 right→left, etc.
    $size = 10;
    $rows = [];
    for ($r = $size - 1; $r >= 0; $r--) {
        $start = $r * $size + 1;
        $row = range($start, $start + $size - 1);
        if ($r % 2 === 1) {
            $row = array_reverse($row);
        }
        $rows[] = $row;
    }

    // Sample ladders (tail → head) and snakes (head → tail).
    $ladders = [3 => 22, 8 => 30, 28 => 84, 58 => 77, 75 => 94];
    $snakes  = [99 => 41, 89 => 53, 62 => 19, 47 => 26, 16 => 6];
@endphp

<div data-game="snake-and-ladder" class="space-y-4">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {{-- Board --}}
        <div class="lg:col-span-2 rounded-xl border border-slate-200 bg-white p-3 overflow-x-auto">
            <div class="mx-auto w-fit">
                <div class="grid grid-rows-10 gap-0.5">
                    @foreach ($rows as $row)
                        <div class="grid grid-cols-10 gap-0.5">
                            @foreach ($row as $cell)
                                @php
                                    $isLadder = array_key_exists($cell, $ladders);
                                    $isSnake  = array_key_exists($cell, $snakes);
                                    $tone     = $isLadder
                                        ? 'bg-emerald-100 ring-emerald-300 text-emerald-800'
                                        : ($isSnake
                                            ? 'bg-rose-100 ring-rose-300 text-rose-800'
                                            : 'bg-slate-50 ring-slate-200 text-slate-700');
                                @endphp
                                <div class="relative h-12 w-12 sm:h-14 sm:w-14 rounded ring-1 {{ $tone }} text-[11px] font-semibold flex items-start justify-start p-1"
                                     data-cell="{{ $cell }}">
                                    <span>{{ $cell }}</span>
                                    @if ($isLadder)
                                        <i data-lucide="trending-up" class="absolute bottom-1 right-1 w-3.5 h-3.5 text-emerald-600"></i>
                                    @elseif ($isSnake)
                                        <i data-lucide="trending-down" class="absolute bottom-1 right-1 w-3.5 h-3.5 text-rose-600"></i>
                                    @endif
                                    @if ($cell === 1)
                                        <span class="absolute bottom-1 right-1 inline-flex h-5 w-5 items-center justify-center rounded-full bg-indigo-600 text-[10px] font-bold text-white shadow"
                                              data-role="player">P</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Side panel --}}
        <aside class="rounded-xl border border-slate-200 bg-white p-4 space-y-4">
            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Position</div>
                <div class="text-3xl font-bold text-emerald-600" data-role="position">1</div>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Last Roll</div>
                <div class="text-2xl font-bold text-slate-800 flex items-center gap-2">
                    <i data-lucide="dice-5" class="w-6 h-6 text-emerald-600"></i>
                    <span data-role="roll">—</span>
                </div>
            </div>

            <button type="button"
                    class="w-full rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white hover:bg-emerald-500">
                Roll Dice
            </button>

            <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">
                <div class="font-semibold mb-1">How it works</div>
                <ol class="list-decimal list-inside space-y-0.5 text-xs">
                    <li>Roll the dice and answer a question.</li>
                    <li>Correct = advance the rolled spaces.</li>
                    <li>Land on a ladder to climb up.</li>
                    <li>Land on a snake to slide back down.</li>
                    <li>First to 100 wins.</li>
                </ol>
            </div>

            <div class="grid grid-cols-2 gap-2 text-xs">
                <div class="rounded-lg bg-emerald-50 ring-1 ring-emerald-200 p-2">
                    <div class="font-semibold text-emerald-700">Ladders</div>
                    <ul class="mt-1 space-y-0.5 text-emerald-800">
                        @foreach ($ladders as $from => $to)
                            <li>{{ $from }} → {{ $to }}</li>
                        @endforeach
                    </ul>
                </div>
                <div class="rounded-lg bg-rose-50 ring-1 ring-rose-200 p-2">
                    <div class="font-semibold text-rose-700">Snakes</div>
                    <ul class="mt-1 space-y-0.5 text-rose-800">
                        @foreach ($snakes as $from => $to)
                            <li>{{ $from }} → {{ $to }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </aside>
    </div>
    <p class="text-xs text-slate-500 italic">Stub UI — wire the dice + question bank to advance the player token.</p>
</div>
