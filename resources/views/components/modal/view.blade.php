<x-modal.base :id="$id" :title="$title" width="2xl">

    {{ $slot }}

    <x-slot name="footer">
        <div class="flex justify-end">
            <button onclick="closeModal('{{ $id }}')"
                    class="px-4 py-2 border rounded">
                Close
            </button>
        </div>
    </x-slot>

</x-modal.base>