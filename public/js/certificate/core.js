/* =========================
   GLOBAL STATE
========================= */
window.selectedElement = null;
let isDragging = false;
let offsetX = 0;
let offsetY = 0;

function clearSelectedState() {
    document.querySelectorAll('.selected').forEach(x => {
        x.classList.remove('selected');
    });
}

function selectCanvasItem(el) {
    if (!el) return;

    clearSelectedState();
    if (typeof window.clearBackgroundSelection === 'function') {
        window.clearBackgroundSelection();
    }
    el.classList.add('selected');
    window.selectedElement = el;
}

/* =========================
   SELECTION SYSTEM
========================= */
function makeSelectable(el) {
    el.addEventListener('click', function (e) {
        e.stopPropagation();

        selectCanvasItem(el);
    });
}

// Click outside = deselect
// Deselect when clicking outside
document.addEventListener('click', function (e) {

    const canvas = document.getElementById('canvas');

    if (!canvas) return;

    const toolbar = document.getElementById('certificate-toolbar');
    const clickedInsideCanvas = canvas.contains(e.target);
    const clickedInsideToolbar = toolbar ? toolbar.contains(e.target) : false;

    if (!clickedInsideCanvas && !clickedInsideToolbar) {

        clearSelectedState();
        window.selectedElement = null;
        if (typeof window.clearBackgroundSelection === 'function') {
            window.clearBackgroundSelection();
        }
    }
});

/* =========================
   DRAG SYSTEM (FIXED)
========================= */
function makeDraggable(el) {

    el.addEventListener('mousedown', function (e) {

        // ❌ Ignore resize/delete
        if (
            e.target.classList.contains('resize-handle') ||
            e.target.classList.contains('delete-btn')
        ) return;

        // ✅ FORCE DRAG EVEN IF CLICKING CHILD (TEXT)
        e.preventDefault(); 

        selectCanvasItem(el);
        isDragging = true;

        const canvas = document.getElementById('canvas');
        if (!canvas) return;

        const rect = canvas.getBoundingClientRect();

        offsetX = e.clientX - el.offsetLeft - rect.left;
        offsetY = e.clientY - el.offsetTop - rect.top;

        document.body.style.userSelect = 'none';
    });
}

document.addEventListener('mousemove', function (e) {

    if (!isDragging || !window.selectedElement) return;

    const canvas = document.getElementById('canvas');
    if (!canvas) return;

    const rect = canvas.getBoundingClientRect();

    let x = e.clientX - rect.left - offsetX;
    let y = e.clientY - rect.top - offsetY;

    // ✅ Keep inside canvas (prevents “disappearing”)
    x = Math.max(0, Math.min(x, canvas.clientWidth - 20));
    y = Math.max(0, Math.min(y, canvas.clientHeight - 20));

    window.selectedElement.style.left = x + 'px';
    window.selectedElement.style.top = y + 'px';
});

document.addEventListener('mouseup', () => {
    isDragging = false;
    document.body.style.userSelect = 'auto';

    // ❌ DO NOT reset selectedElement here
    // (this was breaking your typography controls)

    if (typeof autoSaveToLocal === 'function') {
        autoSaveToLocal();
    }
});

/* =========================
   WRAPPER CREATOR (IMPROVED)
========================= */
function makeResizable(wrapper) {
    if (!wrapper || wrapper.querySelector('.resize-handle')) return;

    const handle = document.createElement('div');
    handle.classList.add('resize-handle');
    const resizableElement = wrapper.querySelector('.shape, img') || wrapper.firstElementChild;

    let isResizing = false;
    let startX = 0;
    let startY = 0;
    let startWidth = 0;
    let startHeight = 0;

    function onMouseMove(e) {
        if (!isResizing) return;

        const minSize = 16;
        let nextWidth = startWidth + (e.clientX - startX);
        let nextHeight = startHeight + (e.clientY - startY);

        nextWidth = Math.max(minSize, nextWidth);
        nextHeight = Math.max(minSize, nextHeight);

        wrapper.style.width = nextWidth + 'px';
        wrapper.style.height = nextHeight + 'px';

        if (resizableElement) {
            resizableElement.style.width = nextWidth + 'px';
            resizableElement.style.height = nextHeight + 'px';
        }
    }

    function onMouseUp() {
        if (!isResizing) return;

        isResizing = false;
        document.body.style.userSelect = 'auto';

        document.removeEventListener('mousemove', onMouseMove);
        document.removeEventListener('mouseup', onMouseUp);

        if (typeof autoSaveToLocal === 'function') {
            autoSaveToLocal();
        }
    }

    handle.addEventListener('mousedown', function (e) {
        e.stopPropagation();
        e.preventDefault();

        selectCanvasItem(wrapper);

        isResizing = true;
        startX = e.clientX;
        startY = e.clientY;
        startWidth = resizableElement ? resizableElement.offsetWidth : wrapper.offsetWidth;
        startHeight = resizableElement ? resizableElement.offsetHeight : wrapper.offsetHeight;

        document.body.style.userSelect = 'none';

        document.addEventListener('mousemove', onMouseMove);
        document.addEventListener('mouseup', onMouseUp);
    });

    wrapper.appendChild(handle);
}

window.createWrapper = function(innerEl) {

    let wrapper = document.createElement('div');

    wrapper.style.position = 'absolute';
    wrapper.style.top = '100px';
    wrapper.style.left = '100px';
    wrapper.style.display = 'inline-block';
    wrapper.style.cursor = 'move';

    wrapper.classList.add('canvas-item');

    wrapper.appendChild(innerEl);

    // ✅ IMPORTANT: selection logic
    wrapper.addEventListener('click', function (e) {
        e.stopPropagation();

        selectCanvasItem(wrapper);
    });

    // ✅ enable dragging
    if (typeof makeDraggable === 'function') {
        makeDraggable(wrapper);
    }

    const tagName = innerEl && innerEl.tagName ? innerEl.tagName.toLowerCase() : '';
    const isResizableType = tagName === 'img' || innerEl.classList.contains('shape');

    if (isResizableType) {
        makeResizable(wrapper);
    }

    return wrapper;
};

