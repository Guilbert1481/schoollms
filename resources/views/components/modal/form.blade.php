{{-- views/components/modal/form.blade.php --}}

@props([
    'id',
    'title',
    'widthClass' => 'w-96'
])

<div id="{{ $id }}" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">

        <div id="{{ $id }}Draggable"
            class="modal-draggable bg-white {{ $widthClass }} rounded-lg shadow-lg absolute"
         style="top:120px; left:50%; transform:translateX(-50%);">

        {{-- Header --}}
        <div id="{{ $id }}Header" class="modal-header flex justify-between items-center border-b px-4 py-2 cursor-move">
            <h3 class="font-semibold">{{ $title }}</h3>
            <button type="button" onclick="closeModal('{{ $id }}')">✕</button>
        </div>

        {{-- Body --}}
        <div class="p-4">
            {{ $slot }}
        </div>

        {{-- Footer --}}
        <div class="flex justify-end gap-2 border-t px-4 py-3">
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

    </div>
</div>

<script>
// OPEN MODAL
function openModal(id){
    document.getElementById(id).classList.remove('hidden');
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

// DRAGGABLE MODAL
document.addEventListener("DOMContentLoaded", function(){

    let modal = document.getElementById("{{ $id }}Draggable");
    let header = document.getElementById("{{ $id }}Header");

    if(!modal || !header) return;

    let isDragging = false;
    let offsetX = 0;
    let offsetY = 0;

    header.addEventListener("mousedown", function(e){
        isDragging = true;
        offsetX = e.clientX - modal.offsetLeft;
        offsetY = e.clientY - modal.offsetTop;
    });

    document.addEventListener("mousemove", function(e){
        if(isDragging){
            modal.style.left = (e.clientX - offsetX) + "px";
            modal.style.top = (e.clientY - offsetY) + "px";
        }
    });

    document.addEventListener("mouseup", function(){
        isDragging = false;
    });

});
</script>