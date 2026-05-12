<x-modal.base :id="$id" :title="$title" width="md">

    <p>{{ $message }}</p>

    <x-slot name="footer">
        <div class="flex justify-end gap-2">
            <button onclick="closeModal('{{ $id }}')"
                    class="px-4 py-2 border rounded">
                Cancel
            </button>

            <button class="px-4 py-2 bg-red-600 text-white rounded">
                Confirm
            </button>
        </div>
    </x-slot>

</x-modal.base>