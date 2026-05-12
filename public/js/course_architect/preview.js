// Course Architect — Preview Mode
window.caPreview = function () {
    return {
        running: false,
        init() {
            if (window.lucide?.createIcons) window.lucide.createIcons();
        },
        launch() {
            this.running = true;
        },
    };
};
