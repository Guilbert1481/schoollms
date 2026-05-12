<div id="{{ $id }}"
     class="hidden fixed inset-0 bg-black bg-opacity-30 z-50 flex items-center justify-center">

    <div class="bg-white rounded-xl shadow-xl w-{{ $width ?? '2xl' }} modal-draggable">

        {{-- Header --}}
        <div class="modal-header cursor-move bg-gray-100 px-4 py-3 rounded-t-xl flex justify-between items-center">
            <h2 class="font-semibold text-lg">{{ $title }}</h2>

            <button onclick="closeModal('{{ $id }}')" class="text-gray-500 hover:text-red-500">
                ✕
            </button>
        </div>

        {{-- Body --}}
        <div class="p-4">
            {{ $slot }}
        </div>

        {{-- Footer --}}
        @isset($footer)
        <div class="px-4 py-3 border-t bg-gray-50 rounded-b-xl">
            {{ $footer }}
        </div>
        @endisset

    </div>
</div>