<tbody @if(!empty($reorderable)) id="{{ $tableKey }}SortBody" @endif>
@foreach($data as $rowIndex => $row)
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

    {{-- Actions --}}
    @if(empty($hideActions))
    <td class="px-3 py-2 action-column"
        data-table="{{ $tableKey }}"
        data-column="actions">
        <div class="flex gap-1">

            @foreach($actions ?? [] as $action)

                {{-- Modal Action --}}
                @if($action['type'] == 'modal')
                    @php
                        $onclickAttr = isset($action['handler'])
                            ? strtr($action['handler'], ['{id}' => $row->id, '{table}' => $tableKey, '{modal}' => $action['modal'] ?? ''])
                            : "openEditModal('{$action['modal']}', {$row->id}, '{$tableKey}')";
                    @endphp
                    <button type="button"
                            onclick="{{ $onclickAttr }}"
                            class="inline-flex items-center justify-center h-7 px-2 rounded text-xs {{ $action['class'] }}">
                        {{ $action['label'] }}
                    </button>
                @endif

                {{-- 🔥 LINK ACTION (ADDED) --}}
                @if($action['type'] == 'link')
                    <a href="{{ route($action['route'], $row->id) }}"
                       class="inline-flex items-center justify-center h-7 px-2 rounded text-xs {{ $action['class'] }}">
                        {{ $action['label'] }}
                    </a>
                @endif

                @if($action['type'] == 'js')
                    <button type="button"
                            onclick="{{ $action['handler'] }}({{ $row->id }})"
                            class="inline-flex items-center justify-center h-7 px-2 rounded text-xs {{ $action['class'] }}">
                        {{ $action['label'] }}
                    </button>
                @endif

                {{-- Delete Action --}}
                @if($action['type'] == 'delete')
                    <form method="POST"
                          class="inline-flex m-0"
                          onsubmit="return confirm(@js($action['confirm'] ?? 'Are you sure you want to delete this record? This action cannot be undone.'))"
                          action="{{ isset($deleteRoute) && $deleteRoute ? route($deleteRoute, $row->id) : route('school.system.dynamic.destroy', ['table' => $tableKey, 'id' => $row->id]) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center justify-center h-7 px-2 rounded text-xs {{ $action['class'] }}">
                            {{ $action['label'] }}
                        </button>
                    </form>
                @endif

            @endforeach

        </div>
    </td>
    @endif

</tr>
@endforeach
</tbody>
