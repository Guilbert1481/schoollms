@php
    /** @var \App\Models\EducationNode $node */
    $depth = $depth ?? 0;
    $kids = $node->relationLoaded('childrenRecursive') ? $node->childrenRecursive : $node->children;

    // The tree shows STRUCTURE only (levels/stages/tracks/strands/program_types).
    // Programs live in the `programs` table and are managed on the Programs page —
    // the tree creates them (via "+ Add Program") but never displays them.
    $hasChildren = $kids && $kids->count() > 0;
    $typeColors = [
        'level'        => 'bg-blue-100 text-blue-700',
        'stage'        => 'bg-emerald-100 text-emerald-700',
        'track'        => 'bg-amber-100 text-amber-700',
        'strand'       => 'bg-violet-100 text-violet-700',
        'program_type' => 'bg-teal-100 text-teal-700',
    ];
    $typeClass = $typeColors[$node->node_type] ?? 'bg-slate-100 text-slate-700';
@endphp

<li>
    <div class="flex items-center gap-2 py-1.5 hover:bg-slate-50 rounded px-1"
         style="padding-left: {{ $depth * 20 }}px;">

        {{-- Expand/Collapse --}}
        @if ($hasChildren)
            <button type="button"
                    data-tree-toggle="{{ $node->id }}"
                    data-open="0"
                    class="w-5 h-5 inline-flex items-center justify-center text-slate-500 hover:text-slate-800 text-xs">▸</button>
        @else
            <span class="w-5 h-5 inline-block"></span>
        @endif

        {{-- Offered checkbox --}}
        <input type="checkbox"
               class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
               @checked($node->is_offered)
               data-action="toggle-offered"
               data-id="{{ $node->id }}">

        {{-- Node icon (clickable when has children) --}}
        @if ($hasChildren)
            <span class="text-slate-400 text-sm cursor-pointer select-none"
                  data-tree-toggle="{{ $node->id }}">📁</span>

            {{-- Name (clickable when has children) --}}
            <span class="text-sm text-slate-800 flex-1 cursor-pointer select-none"
                  data-tree-toggle="{{ $node->id }}">{{ $node->name }}</span>
        @else
            <span class="text-slate-400 text-sm">📄</span>
            <span class="text-sm text-slate-800 flex-1">{{ $node->name }}</span>
        @endif

        {{-- Type pill --}}
        <span class="text-[10px] px-1.5 py-0.5 rounded font-mono {{ $typeClass }}">{{ $node->node_type }}</span>

        {{-- Actions --}}
        <button type="button"
                data-action="add-child"
                data-parent-id="{{ $node->id }}"
                data-parent-name="{{ $node->name }}"
                class="text-xs px-2 py-1 rounded border border-indigo-300 text-indigo-600 hover:bg-indigo-50">
            + Add Child
        </button>
        <button type="button"
                data-action="edit-node"
                data-id="{{ $node->id }}"
                data-name="{{ $node->name }}"
                data-node-type="{{ $node->node_type }}"
                data-order-index="{{ (int) $node->order_index }}"
                class="text-xs px-2 py-1 rounded border border-slate-300 text-slate-600 hover:bg-slate-50">
            ✎
        </button>
        <button type="button"
                data-action="delete-node"
                data-id="{{ $node->id }}"
                class="text-xs px-2 py-1 rounded border border-red-300 text-red-600 hover:bg-red-50">
            🗑
        </button>
    </div>

    @if ($hasChildren)
        <ul data-tree-children="{{ $node->id }}" class="space-y-1 hidden">
            @foreach ($kids as $child)
                @include('admin.education_nodes.partials.node', ['node' => $child, 'depth' => $depth + 1])
            @endforeach
        </ul>
    @endif
</li>
