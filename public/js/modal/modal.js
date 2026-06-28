// public/js/modal/modal.js

// OPEN MODAL
function openModal(id){
    console.log("MODAL OPENED:", id); // 👈 ADD THIS
    document.getElementById(id).classList.remove('hidden');
    document.getElementById(id).classList.add('active');
}


// CLOSE MODAL
function closeModal(id){
    const modal = document.getElementById(id);
    if (!modal) return;

    modal.classList.remove('active');
    modal.classList.add('hidden');
}

// CONFIRM MODAL
function confirmAction(message, callback){
    document.getElementById('confirmMessage').innerText = message;

    let btn = document.getElementById('confirmBtn');

    btn.onclick = function(){
        callback();
        closeModal('globalConfirmModal');
    };

    openModal('globalConfirmModal');
}

// INFO MODAL
function showInfo(message){
    document.getElementById('infoMessage').innerText = message;
    openModal('globalInfoModal');
}



// OPEN EDIT MODAL + LOAD DATA
function openEditModal(modalId, id, table){
    openModal(modalId);

    let modal = document.getElementById(modalId);

    // set hidden id
    let idInput = modal.querySelector('input[name="id"]');
    if(idInput){
        idInput.value = id;
    }

    // set form action
    let form = modal.querySelector('form');
    if(form){
        form.action = '/school/system/dynamic/update';
    }

    // load existing record
    fetch('/school/system/dynamic/get/' + table + '/' + id)
        .then(response => response.json())
        .then(data => {
            for (let key in data){
                let input = modal.querySelector('[name="'+key+'"]');
                if(input){
                    input.value = data[key];
                }
            }
        });
}

// STYLED CONFIRMATION FOR ANY FORM WITH data-confirm
// Replaces the browser's native confirm() dialog with the global confirm modal.
// Usage: <form method="POST" data-confirm="Are you sure?"> ... </form>
document.addEventListener('submit', function (e) {
    const form = e.target;
    if (!(form instanceof HTMLFormElement)) return;
    if (!form.hasAttribute('data-confirm')) return;

    e.preventDefault();
    const message = form.getAttribute('data-confirm') || 'Are you sure?';

    // confirmAction runs the callback only when the user clicks Confirm.
    // form.submit() submits programmatically and does NOT re-fire this handler.
    confirmAction(message, function () {
        form.submit();
    });
});

// DRAGGABLE MODALS (GLOBAL)
document.addEventListener("DOMContentLoaded", function(){

    document.querySelectorAll('.modal-draggable').forEach(function(modal){

        let header = modal.querySelector('.modal-header');
        if(!header) return;

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

});
