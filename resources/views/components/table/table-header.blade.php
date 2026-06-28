<thead class="bg-gray-200 text-gray-700">
<tr>
    @if(!empty($reorderable))
        <th class="px-2 py-3 w-9"></th>
    @endif
    @if(!empty($rowNumbers))
        <th class="px-3 py-3 text-left w-14">#</th>
    @endif
    @foreach($columns as $col)
    @php
        $filter        = $col['filter'] ?? null;
        $filterParam   = $filter['param']   ?? null;
        $filterOptions = $filter['options'] ?? [];
        $currentValue  = $filterParam ? request()->query($filterParam, $filter['default'] ?? 'all') : null;
        $currentLabel  = $filterParam ? ($filterOptions[$currentValue] ?? reset($filterOptions)) : null;
        $colWidth      = $col['width'] ?? null;
    @endphp
    <th class="px-4 py-3 text-left group relative"
        @if($colWidth) style="width: {{ $colWidth }};" @endif
        data-table="{{ $tableKey }}"
        data-column="{{ $col['key'] }}">
        @if($filter && ($filter['type'] ?? null) === 'dropdown' && !empty($filterOptions))
            <div class="relative inline-block" x-data="{ open: false }" @click.outside="open = false">
                <button type="button"
                        @click="open = !open"
                        class="inline-flex items-center gap-1 hover:text-indigo-600 focus:outline-none">
                    <span>{{ $col['label'] }}</span>
                    <span class="normal-case text-[10px] text-indigo-600 font-bold">({{ $currentLabel }})</span>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open" x-cloak x-transition
                     class="absolute left-0 mt-1 z-20 w-36 bg-white border border-gray-200 rounded-lg shadow-lg py-1">
                    @foreach($filterOptions as $val => $lbl)
                        <a href="{{ request()->fullUrlWithQuery([$filterParam => $val, 'page' => 1]) }}"
                           class="block px-3 py-1.5 text-xs normal-case {{ (string) $currentValue === (string) $val ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'text-gray-700 hover:bg-gray-50' }}">
                            {{ $lbl }}
                        </a>
                    @endforeach
                </div>
            </div>
        @else
            {{ $col['label'] }}
        @endif

        {{-- Visible grip — drag it left/right to resize this column. --}}
        <div class="col-resizer"
             data-table="{{ $tableKey }}"
             data-col-key="{{ $col['key'] }}"
             title="Drag to resize · double-click to fit">
            <span class="col-resizer-line"></span>
        </div>
    </th>
@endforeach

    @if(empty($hideActions))
        <th class="px-4 py-3 text-left action-column" style="width:150px;">
            Action
        </th>
    @endif
</tr>
</thead>