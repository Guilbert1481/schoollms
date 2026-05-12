// Course Architect — Lesson Studio
window.caLessonStudio = function () {
    return {
        lessons: [],
        search: '',
        init() {
            if (window.lucide?.createIcons) window.lucide.createIcons();
        },
    };
};
