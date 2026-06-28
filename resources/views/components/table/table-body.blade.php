<tbody @if(!empty($reorderable)) id="{{ $tableKey }}SortBody" @endif>
@forelse($data as $rowIndex => $row)
<tr class="border-t @if(!empty($reorderable)) shareable-row @endif"
    @if(!empty($reorderable)) data-id="{{ $row->id ?? '' }}" @endif>

    @if(!empty($reorderable))
        <td class="px-2 py-2 text-slate-300 drag-handle cursor-grab active:cursor-grabbing align-middle"
            title="Drag to reorder">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 pointer-events-none inline" viewBox="0 0 20 20" fill="currentColor">
                <path d="M7 4a1 1 0 110 2 1 1 0 010-2zM7 9a1 1 0 110 2 1 1 0 010-2zM7 14a1 1 0 110 2 1 1 0 010-2zM13 4a1 1 0 110 2 1 1 0 010-2zM13 9a1 1 0 110 2 1 1 0 010-2zM13 14a1 1 0 110 2 1 1 0 010-2z"/>
            </svg>
        </td>
    @endif
    @if(!empty($rowNumbers))
        <td class="px-3 py-2 text-slate-500 row-seq">{{ $rowIndex + 1 }}</td>
    @endif

    @foreach($columns as $col)
        <td class="px-3 py-2"
            data-table="{{ $tableKey }}"
            data-column="{{ $col['key'] }}">
            @if(! empty($col['raw']))
                {!! data_get($row, $col['key']) !!}
            @else
                {{ data_get($row, $col['key'].'_label') ?? data_get($row, $col['key']) }}
            @endif
        </td>
    @endforeach

    {{-- Actions (icon buttons + hover labels; kebab when more than 3) --}}
    @if(empty($hideActions))
    <td class="px-3 py-2 action-column"
        data-table="{{ $tableKey }}"
        data-column="actions">
        <x-table.actions
            :actions="$actions ?? []"
            :row="$row"
            :tableKey="$tableKey"
            :deleteRoute="$deleteRoute ?? null" />
    </td>
    @endif

</tr>
@empty
<tr class="js-empty-row border-t">
    <td colspan="99" class="px-4 py-10 text-center text-sm text-slate-500">
        {{ $emptyMessage ?? 'No records found.' }}
    </td>
</tr>
@endforelse
</tbody>
