{{-- views/components/table/column-modal.blade.php --}}

<div id="{{ $tableKey }}ColumnModal"
     class="hidden fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50">

    <div class="bg-white rounded-lg shadow-lg w-96 p-6">
        <h2 class="text-lg font-semibold mb-4">Column Settings</h2>

        @foreach($columns as $col)
            <div class="flex items-center justify-between mb-2">
                <label class="flex items-center gap-2">
                    <input type="checkbox"
                           class="column-toggle"
                           value="{{ $col['key'] }}"
                           checked>
                    {{ $col['label'] }}
                </label>

                <input type="number"
                       class="column-width border rounded px-2 py-1 w-20 text-sm"
                       data-column="{{ $col['key'] }}"
                       placeholder="%">
            </div>
        @endforeach

        {{-- Action column toggle --}}
        <div class="flex items-center justify-between mb-2">
            <label class="flex items-center gap-2">
                <input type="checkbox"
                       class="column-toggle"
                       value="actions"
                       checked>
                Action
            </label>

            <input type="number"
                   class="column-width border rounded px-2 py-1 w-20 text-sm"
                   data-column="actions"
                   placeholder="%">
        </div>

        <div class="flex justify-end gap-2 mt-4">
            <button onclick="closeColumnModal('{{ $tableKey }}')"
                    class="px-3 py-2 border rounded">
                Cancel
            </button>

            <button onclick="saveColumnSettings('{{ $tableKey }}')"
                    class="px-3 py-2 bg-blue-600 text-white rounded">
                Save
            </button>
        </div>
    </div>
</div>