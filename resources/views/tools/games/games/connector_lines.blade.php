{{-- Relational Connector Lines — game stub --}}
<div data-game="connector-lines" class="space-y-4">
    <div class="grid grid-cols-2 gap-8">
        <div class="space-y-3">
            @foreach (['Heart', 'Lungs', 'Stomach', 'Brain'] as $left)
                <div class="rounded-lg bg-purple-50 ring-1 ring-purple-200 px-3 py-2 text-sm font-medium text-purple-800">{{ $left }}</div>
            @endforeach
        </div>
        <div class="space-y-3">
            @foreach (['Digests food', 'Pumps blood', 'Controls thought', 'Exchanges gases'] as $right)
                <div class="rounded-lg bg-indigo-50 ring-1 ring-indigo-200 px-3 py-2 text-sm font-medium text-indigo-800">{{ $right }}</div>
            @endforeach
        </div>
    </div>
    <p class="text-xs text-slate-500 italic">Stub UI — draw lines connecting matching prompt + answer pairs.</p>
</div>
