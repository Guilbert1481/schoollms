function createShapeElement(type) {

    let shape;

    if (type === 'line-horizontal') {
        shape = document.createElement('div');
        Object.assign(shape.style, { width:'200px', height:'2px', background:'black' });
    }

    if (type === 'line-vertical') {
        shape = document.createElement('div');
        Object.assign(shape.style, { width:'2px', height:'200px', background:'black' });
    }

    if (type === 'line-diagonal') {
        shape = document.createElement('div');
        Object.assign(shape.style, {
            width:'200px',
            height:'2px',
            background:'black',
            transform:'rotate(-25deg)',
            transformOrigin:'center'
        });
    }

    if (type === 'square') {
        shape = document.createElement('div');
        Object.assign(shape.style, {
            width:'120px',
            height:'120px',
            border:'2px solid black',
            background:'transparent',
            boxSizing:'border-box'
        });
    }

    if (type === 'box') {
        shape = document.createElement('div');
        Object.assign(shape.style, {
            width:'160px',
            height:'100px',
            border:'2px solid black',
            background:'transparent',
            boxSizing:'border-box'
        });
    }

    if (type === 'circle') {
        shape = document.createElement('div');
        Object.assign(shape.style, {
            width:'120px', height:'120px',
            borderRadius:'50%', border:'2px solid black'
        });
    }

    if (type === 'star') {
        shape = document.createElement('div');
        shape.innerHTML = '<svg viewBox="0 0 100 100" width="100%" height="100%" aria-hidden="true" focusable="false"><polygon points="50,6 61,38 95,38 68,58 78,91 50,72 22,91 32,58 5,38 39,38" fill="none" stroke="black" stroke-width="2" stroke-linejoin="round"/></svg>';
        Object.assign(shape.style, {
            width:'110px',
            height:'110px',
            background:'transparent'
        });
    }

    if (!shape) return;

    shape.classList.add('shape');
    shape.style.display = 'block';

    let wrapper = createWrapper(shape);

    document.getElementById('canvas').appendChild(wrapper);

    if (typeof selectCanvasItem === 'function') {
        selectCanvasItem(wrapper);
    } else {
        window.selectedElement = wrapper;
    }

    autoSaveToLocal();
}

function getSelectedShapeElement() {
    if (!window.selectedElement) return null;

    return window.selectedElement.querySelector('.shape');
}

function applyShapeFillColor(shape, color) {
    const polygon = shape.querySelector('polygon');

    if (polygon) {
        polygon.setAttribute('fill', color);
        return;
    }

    shape.style.backgroundColor = color;
}

function applyShapeEdgeColor(shape, color) {
    const polygon = shape.querySelector('polygon');

    if (polygon) {
        polygon.setAttribute('stroke', color);
        return;
    }

    if (shape.style.border || shape.style.borderWidth) {
        shape.style.borderColor = color;
        return;
    }

    // Line-based shapes use the background as the visible edge.
    shape.style.backgroundColor = color;
}

window.setSelectedShapeColor = function(color) {
    const shape = getSelectedShapeElement();
    if (!shape || !color) return;

    const modeControl = document.getElementById('shape-color-mode');
    const mode = modeControl ? modeControl.value : 'fill';

    if (mode === 'edge') {
        applyShapeEdgeColor(shape, color);
    } else {
        applyShapeFillColor(shape, color);
    }

    autoSaveToLocal();
};

window.addLineHorizontal = () => createShapeElement('line-horizontal');
window.addLineVertical = () => createShapeElement('line-vertical');
window.addLineDiagonal = () => createShapeElement('line-diagonal');
window.addSquare = () => createShapeElement('square');
window.addBox = () => createShapeElement('box');
window.addCircle = () => createShapeElement('circle');
window.addStar = () => createShapeElement('star');