@extends('layouts.app')
@section('content')

<div x-data="subjectBuilder" class="space-y-4">

    <div class="flex items-center justify-between mb-4">
        <div class="relative">
            <input 
                    type="text" 
                    x-model.debounce.100ms="filter" 
                    placeholder="Search subjects..." 
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                >
        </div>

        <button @click="openAddModal()" 
                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg font-semibold">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"></path></svg>
            Add New Subject
        </button>
    </div>

    <div class="overflow-x-auto rounded shadow mt-6">
        <table class="min-w-full bg-white border border-gray-300">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-3 text-left">Subject Name</th>
                    <th class="px-4 py-3 text-left">Code</th>
                    <th class="px-4 py-3 text-left">Topics</th>
                    <th class="px-4 py-3 text-left">Lessons</th>
                    <th class="px-4 py-3 text-left">Competencies</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($subjects as $subject)
               <tr x-show="filter === '' || $el.innerText.toLowerCase().includes(filter.toLowerCase())"
                    class="subject-row"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 transform scale-95"
                    x-transition:enter-end="opacity-100 transform scale-100">
                    
                    <td class="px-4 py-3">{{ $subject->name }}</td>

                    <td class="px-4 py-3">
                        <button type="button" class="text-blue-600 hover:underline font-medium"
                                @click="openTopicModal({{ $subject->id }}, '{{ $subject->code }}')">
                            {{ $subject->code }}
                        </button>
                    </td>

                    <td class="px-6 py-4">
                        <button type="button" 
                                class="text-indigo-600 hover:underline font-medium"
                                @click="openLessonModal({{ $subject->id }}, '{{ addslashes($subject->code) }}')">
                            {{ $subject->topics_count ?? 0 }} Topics
                        </button>
                    </td>

                    <td class="px-4 py-3">
                        <button type="button" 
                                class="text-blue-600 hover:underline font-medium"
                                @click="openCompetencyModal({{ $subject->id }}, '{{ addslashes($subject->code) }}')">
                            {{ $subject->lessons_count ?? 0 }} Lessons
                        </button>
                    </td>

                    <td class="px-4 py-3">
                        <span class="text-sm text-gray-600">{{ $subject->competencies_count ?? 0 }} Competencies</span>
                    </td>

                    <td class="px-4 py-3">
                        <button type="button" 
                                @click="openEditStatusModal({{ $subject->id }}, {{ $subject->active }}, '{{ $subject->code }}')"
                                class="hover:opacity-80 transition-opacity focus:outline-none">
                            @if($subject->active)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>                    
                                    Inactive
                                </span>
                            @endif
                        </button>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex justify-start gap-2">
                            <button class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-md transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" 
                                    width="15" 
                                    height="15" 
                                    viewBox="0 0 24 24" 
                                    fill="none" 
                                    stroke="currentColor" 
                                    stroke-width="2.5" 
                                    stroke-linecap="round" 
                                    stroke-linejoin="round" 
                                    class="lucide lucide-eye">
                                    <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                            <button type="button" 
                                @click="openDeleteModal({{ $subject->id }}, '{{ $subject->code }}')"
                                class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-md transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" 
                                    width="15" 
                                    height="15" 
                                    viewBox="0 0 24 24" 
                                    fill="none" 
                                    stroke="currentColor" 
                                    stroke-width="2.5" 
                                    stroke-linecap="round" 
                                    stroke-linejoin="round" 
                                    class="lucide lucide-trash-2">
                                    <path d="M3 6h18"/>
                                    <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                                    <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                    <line x1="10" x2="10" y1="11" y2="17"/>
                                    <line x1="14" x2="14" y1="11" y2="17"/>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
                
                @endforeach

                <tr x-show="filter !== '' && Array.from($el.closest('tbody').querySelectorAll('.subject-row')).every(row => row.style.display === 'none')" 
                    x-cloak>
                    <td colspan="7" class="px-4 py-8 text-center text-gray-500 italic">
                        No subjects matching "<span x-text="filter"></span>" found.
                    </td>
                </tr>

            </tbody>
        </table>
    </div>

    {{ $subjects->links() }}

    @include('program_head.subjects.partials.modals.subjects')
    @include('program_head.subjects.partials.modals.topics')
    @include('program_head.subjects.partials.modals.lessons')
    @include('program_head.subjects.partials.modals.competency')
    @include('program_head.subjects.partials.modals.edit-status')
    @include('program_head.subjects.partials.modals.delete-confirm')

    

    <div class="flex items-center justify-between mt-6 px-4 py-3 bg-white border-t border-gray-100 rounded-b-xl">
        <div class="flex items-center space-x-4">
            <button type="button"
                    @click="openAssignPreviewModal()"
                    class="inline-flex items-center px-5 py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow-sm transition-all active:scale-95">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                </svg>
                Assign to Program
            </button>
            
            <div class="h-6 w-px bg-gray-200"></div> <span class="text-sm text-gray-500 font-medium italic" 
                x-show="selectedSubjects.length > 0"
                x-transition>
                <span x-text="selectedSubjects.length" class="text-indigo-600 font-bold"></span> subjects selected for assignment
            </span>
        </div>

        <!-- Pagination Controls -->
        <nav class="flex items-center space-x-1" aria-label="Pagination">
            <button class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors disabled:opacity-30">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </button>
            
            <button class="w-9 h-9 flex items-center justify-center rounded-lg bg-indigo-600 text-white font-bold text-sm shadow-sm">1</button>
            <button class="w-9 h-9 flex items-center justify-center rounded-lg text-gray-600 hover:bg-gray-100 font-medium text-sm transition-colors">2</button>
            <button class="w-9 h-9 flex items-center justify-center rounded-lg text-gray-600 hover:bg-gray-100 font-medium text-sm transition-colors">3</button>
            <span class="px-2 text-gray-400">...</span>
            <button class="w-9 h-9 flex items-center justify-center rounded-lg text-gray-600 hover:bg-gray-100 font-medium text-sm transition-colors">12</button>

            <button class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </button>
        </nav>
    </div>

</div>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('subjectBuilder', () => ({

        selectedSubjects: [],

        // --- 1. State Variables ---
        filter: '',
        currentSubjectId: null,
        currentSubjectCode: '',
        
        addModal: false,

        topicModal: false,
        topicsBuffer: [],
        newTopicName: '',

        lessonModal: false,
        selectedTopicId: '',
        availableTopics: [],
        lessonsBuffer: [],
        newLessonName: '',

        competencyModal: false,
        selectedLessonId: '',
        availableLessons: [],
        competenciesBuffer: [],
        newCompetencyName: '',

        // Status Modal State
        editStatusModal: false,
        statusSubjectId: null,
        statusValue: 1,

        // --- 2. Subject Methods ---
        openAddModal() { this.addModal = true; },
        closeModal() { this.addModal = false; },

        // --- 3. Status Methods ---
        openEditStatusModal(id, currentStatus, code) {
            this.statusSubjectId = id;
            this.statusValue = currentStatus;
            this.currentSubjectCode = code; 
            this.editStatusModal = true;
        },

        // --- 4. Topic Methods ---
        openTopicModal(id, code) {
            this.currentSubjectId = id;
            this.currentSubjectCode = code;
            this.topicsBuffer = []; 
            this.topicModal = true;
        },
        closeTopicModal() { this.topicModal = false; },
        addTopic() {
            if (!this.newTopicName.trim()) return;
            this.topicsBuffer.push(this.newTopicName.trim());
            this.newTopicName = '';
        },
        async saveTopics() {
            const token = document.querySelector('meta[name="csrf-token"]').content;
            const res = await fetch(`/staff/program-head/subjects/${this.currentSubjectId}/topics`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
                body: JSON.stringify({ topics: this.topicsBuffer })
            });
            if (res.ok) location.reload();
        },

        // --- 5. Lesson Methods ---
        async openLessonModal(id, code) {
            this.currentSubjectId = id;
            this.currentSubjectCode = code;
            this.lessonsBuffer = []; 
            this.selectedTopicId = ''; 
            
            try {
                const res = await fetch(`/staff/program-head/subjects/${id}/get-topics`);
                if (!res.ok) throw new Error();
                
                this.availableTopics = await res.json();
                this.lessonModal = true; 
            } catch (e) {
                console.error("Failed to load topics:", e);
                alert("Could not load topics for this subject.");
            }
        },
        addLesson() {
            if(!this.newLessonName.trim()) return;
            this.lessonsBuffer.push({ name: this.newLessonName.trim(), checked: true });
            this.newLessonName = '';
        },
        async saveLessons() {
            if(!this.selectedTopicId) return alert("Select a topic");
            const token = document.querySelector('meta[name="csrf-token"]').content;
            const res = await fetch(`/staff/program-head/topics/${this.selectedTopicId}/lessons`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                body: JSON.stringify({
                    topic_id: this.selectedTopicId,
                    lessons: this.lessonsBuffer.filter(l => l.checked).map(l => l.name)
                })
            });
            if (res.ok) location.reload();
        },

        // --- 6. Competency Methods ---
        async openCompetencyModal(id, code) {
            this.currentSubjectId = id;
            this.currentSubjectCode = code;
            this.competenciesBuffer = [];
            this.selectedLessonId = '';
            try {
                const res = await fetch(`/staff/program-head/subjects/${id}/get-lessons`);
                this.availableLessons = await res.json();
                this.competencyModal = true;
            } catch (e) { console.error("Load lessons failed", e); }
        },
        addCompetency() {
            if(!this.newCompetencyName.trim()) return;
            this.competenciesBuffer.push({ name: this.newCompetencyName.trim(), checked: true });
            this.newCompetencyName = '';
        },
        async saveCompetencies() {
            if(!this.selectedLessonId) return alert("Please select a lesson first.");
            const token = document.querySelector('meta[name="csrf-token"]').content;
            const url = `/staff/program-head/lessons/${this.selectedLessonId}/competencies`;

            const res = await fetch(url, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json', 
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json' 
                },
                body: JSON.stringify({
                    competencies: this.competenciesBuffer.filter(c => c.checked).map(c => c.name)
                })
            });
            
            if(res.ok) location.reload();
        },

        deleteModal: false,

        openDeleteModal(id, code) {
            this.currentSubjectId = id;
            this.currentSubjectCode = code;
            this.deleteModal = true;
        },


        // Search Helper'
        isTableEmpty() {
            if (this.filter === '') return false;
            
            // Grab all rows with the class 'subject-row'
            const rows = Array.from(document.querySelectorAll('.subject-row'));
            
            // If there are rows, check if every single one is hidden
            return rows.length > 0 && rows.every(row => row.style.display === 'none');
        },


        // This is the most reliable way: check the data directly
        get hasNoResults() {
            if (this.filter === '') return false;
            
            const rows = Array.from(document.querySelectorAll('.subject-row'));
            
            // Check if every row's text fails to match the filter
            const anyVisible = rows.some(row => 
                row.innerText.toLowerCase().includes(this.filter.toLowerCase())
            );
            
            return !anyVisible;
        }

    }));
});
</script>



<style>
    [x-cloak] { display: none !important; }
</style>
@endsection