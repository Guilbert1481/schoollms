<x-modal.base :id="$id" :title="$title" width="md">

    <p>{{ $message }}</p>

    <x-slot name="footer">
        <div class="flex justify-end">
            <button onclick="closeModal('{{ $id }}')"
                    class="px-4 py-2 bg-blue-600 text-white rounded">
                OK
            </button>
        </div>
    </x-slot>

</x-modal.base>