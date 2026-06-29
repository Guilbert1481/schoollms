{{-- views/components/modal/form.blade.php --}}

@props([
    'id',
    'title',
    'widthClass' => 'w-96',
    'hideFooter' => false,   // hide the Cancel/Save footer (e.g. a read-only table)
])

<div id="{{ $id }}" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">

        <div id="{{ $id }}Draggable"
            class="modal-draggable bg-white {{ $widthClass }} rounded-lg shadow-lg absolute flex flex-col"
         style="top:80px; left:50%; transform:translateX(-50%); max-height:90vh; max-width:95vw;">

        {{-- Header --}}
        <div id="{{ $id }}Header" class="modal-header flex flex-none justify-between items-center border-b px-4 py-2 cursor-move">
            <h3 class="font-semibold">{{ $title }}</h3>
            <button type="button" onclick="closeModal('{{ $id }}')">✕</button>
        </div>

        {{-- Body --}}
        <div class="flex-1 overflow-auto p-4">
            {{ $slot }}
        </div>

        {{-- Footer --}}
        @unless($hideFooter)
        <div class="flex flex-none justify-end gap-2 border-t px-4 py-3">
            <button type="button"
                    onclick="closeModal('{{ $id }}')"
                    class="px-4 py-2 border rounded">
                Cancel
            </button>

            <button type="button"
                    onclick="submitModalForm('{{ $id }}')"
                    class="px-4 py-2 bg-indigo-600 text-white rounded">
                Save
            </button>
        </div>
        @endunless

        {{-- Resize grip (bottom-right) --}}
        <div id="{{ $id }}Resizer" class="modal-resizer" title="Drag to resize"></div>

    </div>
</div>

<style>
    .modal-resizer {
        position: absolute; right: 0; bottom: 0; width: 16px; height: 16px;
        cursor: nwse-resize; z-index: 20;
        background:
            linear-gradient(135deg, transparent 0 45%, #cbd5e1 45% 55%, transparent 55% 65%, #cbd5e1 65% 75%, transparent 75%);
    }
</style>

<script>
// OPEN MODAL
function openModal(id){
    document.getElementById(id).classList.remove('hidden');
    if (window.lucide && window.lucide.createIcons) window.lucide.createIcons();
}

// CLOSE MODAL
function closeModal(id){
    document.getElementById(id).classList.add('hidden');
}

// SUBMIT FORM
function submitModalForm(id){
    let modal = document.getElementById(id);
    let form = modal.querySelector('form');

    if(form){
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
            return;
        }

        // Fallback for older browsers: trigger a real submit click.
        let submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
        if (submitButton) {
            submitButton.click();
            return;
        }

        form.submit();
    } else {
        console.error('No form found inside modal:', id);
    }
}

// DRAGGABLE + RESIZABLE MODAL
(function(){
    function init(){
        const modal  = document.getElementById("{{ $id }}Draggable");
        const header = document.getElementById("{{ $id }}Header");
        const grip   = document.getElementById("{{ $id }}Resizer");
        if (!modal || modal.dataset.mdInit) return;
        modal.dataset.mdInit = '1';

        let dragging = false, dx = 0, dy = 0;
        let resizing = false, sx = 0, sy = 0, sw = 0, sh = 0;

        if (header) header.addEventListener("mousedown", function(e){
            if (e.target.closest('button')) return;
            // Pin the modal at its current visual position (clears the
            // translateX(-50%) centering so dragging doesn't jump).
            const r = modal.getBoundingClientRect();
            modal.style.transform = 'none';
            modal.style.left = r.left + 'px';
            modal.style.top  = r.top + 'px';
            dragging = true; dx = e.clientX - r.left; dy = e.clientY - r.top;
            e.preventDefault();
        });

        if (grip) grip.addEventListener("mousedown", function(e){
            const r = modal.getBoundingClientRect();
            resizing = true; sx = e.clientX; sy = e.clientY; sw = r.width; sh = r.height;
            e.preventDefault(); e.stopPropagation();
        });

        document.addEventListener("mousemove", function(e){
            if (dragging){
                modal.style.left = (e.clientX - dx) + "px";
                modal.style.top  = (e.clientY - dy) + "px";
            } else if (resizing){
                modal.style.maxWidth  = 'none';
                modal.style.maxHeight = 'none';
                modal.style.width  = Math.max(320, Math.min(window.innerWidth  - 20, sw + (e.clientX - sx))) + "px";
                modal.style.height = Math.max(200, Math.min(window.innerHeight - 20, sh + (e.clientY - sy))) + "px";
            }
        });

        document.addEventListener("mouseup", function(){ dragging = false; resizing = false; });
    }

    if (document.readyState === 'loading') document.addEventListener("DOMContentLoaded", init);
    else init();
})();
</script>
