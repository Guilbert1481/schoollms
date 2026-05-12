function makeDraggable(modalId, headerId) {
    const modal = document.getElementById(modalId);
    const header = document.getElementById(headerId);

    if (!modal || !header) return;

    let isDragging = false;
    let offsetX = 0;
    let offsetY = 0;

    header.addEventListener("mousedown", function(e) {
        isDragging = true;
        offsetX = e.clientX - modal.offsetLeft;
        offsetY = e.clientY - modal.offsetTop;
    });

    document.addEventListener("mousemove", function(e) {
        if (!isDragging) return;
        modal.style.left = (e.clientX - offsetX) + "px";
        modal.style.top = (e.clientY - offsetY) + "px";
    });

    document.addEventListener("mouseup", function() {
        isDragging = false;
    });
}