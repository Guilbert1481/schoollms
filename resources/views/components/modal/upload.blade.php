<x-modal.base :id="$id" :title="$title" width="lg">

    <input type="file" class="border p-2 w-full">

    <x-slot name="footer">
        <div class="flex justify-end gap-2">
            <button onclick="closeModal('{{ $id }}')" class="px-4 py-2 border rounded">
                Cancel
            </button>

            <button class="px-4 py-2 bg-indigo-600 text-white rounded">
                Upload
            </button>
        </div>
    </x-slot>

</x-modal.base>