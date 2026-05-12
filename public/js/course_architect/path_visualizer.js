// Course Architect — Path Visualizer
window.caPathVisualizer = function () {
    return {
        nodes: [],
        edges: [],
        init() {
            if (window.lucide?.createIcons) window.lucide.createIcons();
        },
    };
};
