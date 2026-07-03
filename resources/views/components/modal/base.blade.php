@php
    // The `w-{{ $width }}` class emitted "w-md / w-2xl / w-4xl" — none of which are
    // valid Tailwind utilities (the named scale is max-w-*), so the panel had no
    // width rule. Resolve the size token to an inline max-width instead, so every
    // sibling modal (confirm/filter/info/table/upload/view) is sized to its content
    // regardless of the compiled build. width:100% keeps it responsive on mobile.
    $modalScale = [
        'xs' => '20rem', 'sm' => '24rem', 'md' => '28rem', 'lg' => '32rem',
        'xl' => '36rem', '2xl' => '42rem', '3xl' => '48rem', '4xl' => '56rem',
        '5xl' => '64rem', '6xl' => '72rem', '7xl' => '80rem',
    ];
    $modalMaxW = $modalScale[(string) ($width ?? '2xl')] ?? '42rem';
@endphp

<div id="{{ $id }}"
     class="hidden fixed inset-0 bg-black bg-opacity-30 z-50 flex items-center justify-center">

    <div class="bg-white rounded-xl shadow-xl modal-draggable"
         style="width:100%; max-width:min({{ $modalMaxW }}, 95vw);">

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