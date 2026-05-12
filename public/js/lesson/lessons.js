/* SchoolLMS Lesson Studio – Lessons (Alpine component) */
(function () {
    'use strict';

    function lessonStudio() {
        return {
            search: '',
            filterStatus: '',
            openCreate: false,
            draft: { title: '', subject: '' },
            lessons: [
                { id: 1, title: 'Sample: Introduction to Algebra', subject: 'Mathematics', status: 'draft', updatedAt: 'Today' },
            ],
            filteredLessons() {
                const q = this.search.trim().toLowerCase();
                return this.lessons.filter(l => {
                    if (this.filterStatus && l.status !== this.filterStatus) return false;
                    if (!q) return true;
                    return l.title.toLowerCase().includes(q) || (l.subject || '').toLowerCase().includes(q);
                });
            },
            addLesson() {
                if (!this.draft.title.trim()) return;
                this.lessons.unshift({
                    id: Date.now(),
                    title: this.draft.title.trim(),
                    subject: this.draft.subject.trim() || '—',
                    status: 'draft',
                    updatedAt: 'Just now',
                });
                this.draft = { title: '', subject: '' };
                this.openCreate = false;
                this.$nextTick(() => window.lucide && window.lucide.createIcons());
            },
        };
    }

    window.lessonStudio = lessonStudio;
})();
