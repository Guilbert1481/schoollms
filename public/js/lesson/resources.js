/* SchoolLMS Lesson Studio – Resources (Alpine component) */
(function () {
    'use strict';

    function lessonResources() {
        return {
            search: '',
            filterType: '',
            openUpload: false,
            draft: { title: '', type: 'document' },
            resources: [
                { id: 1, title: 'Sample: Course Outline.pdf', type: 'document', uploadedAt: 'Today' },
            ],
            filteredResources() {
                const q = this.search.trim().toLowerCase();
                return this.resources.filter(r => {
                    if (this.filterType && r.type !== this.filterType) return false;
                    if (!q) return true;
                    return r.title.toLowerCase().includes(q);
                });
            },
            iconForType(type) {
                const map = { document: 'file-text', slide: 'presentation', video: 'video', link: 'link' };
                return map[type] || 'file';
            },
            addResource() {
                if (!this.draft.title.trim()) return;
                this.resources.unshift({
                    id: Date.now(),
                    title: this.draft.title.trim(),
                    type: this.draft.type,
                    uploadedAt: 'Just now',
                });
                this.draft = { title: '', type: 'document' };
                this.openUpload = false;
                this.$nextTick(() => window.lucide && window.lucide.createIcons());
            },
        };
    }

    window.lessonResources = lessonResources;
})();
