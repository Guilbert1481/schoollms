@extends('layouts.app')
@section('content')

@php
    $subjectsConfig  = config('tables.tables.subjects');
    $subjectsColumns = $subjectsConfig['columns'] ?? [];
    $subjectsLabels  = $subjectsConfig['labels']  ?? [];
    $subjectsActions = config('tables.table-actions.subjects', []);
    $statusFilter    = $status ?? request('status', 'all');
    $statusLabel     = ['all' => 'All', 'active' => 'Active', 'inactive' => 'Inactive'][$statusFilter] ?? 'All';
@endphp

<div x-data="subjectBuilder" class="space-y-4 p-6">

    {{-- TOP BAR --}}
    <div class="flex items-center justify-between mb-4">
        <div class="relative">
            <input
                type="text"
                id="subjectsFilter"
                placeholder="Filter..."
                class="w-72 px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
        </div>

        <button type="button"
                onclick="openModal('subjectCreateModal')"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold text-sm shadow-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add New Subject
        </button>
    </div>

    {{-- TABLE --}}
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table id="subjectsTable" class="min-w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        @foreach($subjectsColumns as $col)
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">
                                @if($col['key'] === 'is_active')
                                    <div class="relative inline-block" x-data="{ open: false }" @click.outside="open = false">
                                        <button type="button"
                                                @click="open = !open"
                                                class="inline-flex items-center gap-1 hover:text-indigo-600 focus:outline-none">
                                            <span>{{ $col['label'] ?? 'Status' }}</span>
                                            <span class="normal-case text-[10px] text-indigo-600 font-bold">({{ $statusLabel }})</span>
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </button>
                                        <div x-show="open" x-cloak x-transition
                                             class="absolute left-0 mt-1 z-20 w-36 bg-white border border-gray-200 rounded-lg shadow-lg py-1">
                                            @foreach(['all' => 'All', 'active' => 'Active', 'inactive' => 'Inactive'] as $val => $lbl)
                                                <a href="{{ request()->fullUrlWithQuery(['status' => $val, 'page' => 1]) }}"
                                                   class="block px-3 py-1.5 text-xs normal-case {{ $statusFilter === $val ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'text-gray-700 hover:bg-gray-50' }}">
                                                    {{ $lbl }}
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    {{ $col['label'] ?? ($subjectsLabels[$col['key']] ?? $col['key']) }}
                                @endif
                            </th>
                        @endforeach
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($subjects as $subject)
                        <tr class="subject-row border-t border-gray-100 hover:bg-slate-50">

                            @foreach($subjectsColumns as $col)
                                @php $key = $col['key']; @endphp
                                <td class="px-4 py-3 text-sm" data-table="subjects" data-column="{{ $key }}">
                                    @switch($key)
                                        @case('name')
                                            <span class="font-medium text-gray-800">{{ $subject->name }}</span>
                                            @break

                                        @case('code')
                                            <button type="button"
                                                    class="text-blue-600 hover:underline font-medium"
                                                    @click="openTopicModal({{ $subject->id }}, '{{ addslashes($subject->code) }}')">
                                                {{ $subject->code }}
                                            </button>
                                            @break

                                        @case('topics_count')
                                            <button type="button"
                                                    class="text-indigo-600 hover:underline font-medium"
                                                    @click="openLessonModal({{ $subject->id }}, '{{ addslashes($subject->code) }}')">
                                                {{ $subject->topics_count ?? 0 }} Topics
                                            </button>
                                            @break

                                        @case('lessons_count')
                                            <button type="button"
                                                    class="text-blue-600 hover:underline font-medium"
                                                    @click="openCompetencyModal({{ $subject->id }}, '{{ addslashes($subject->code) }}')">
                                                {{ $subject->lessons_count ?? 0 }} Lessons
                                            </button>
                                            @break

                                        @case('competencies_count')
                                            <span class="text-sm text-gray-600">
                                                {{ $subject->competencies_count ?? 0 }} Competencies
                                            </span>
                                            @break

                                        @case('category')
                                            @php
                                                $catColors = [
                                                    'gen_ed'     => 'bg-blue-100 text-blue-700',
                                                    'prof_ed'    => 'bg-purple-100 text-purple-700',
                                                    'major'      => 'bg-amber-100 text-amber-700',
                                                    'pe'         => 'bg-pink-100 text-pink-700',
                                                    'nstp'       => 'bg-red-100 text-red-700',
                                                    'internship' => 'bg-emerald-100 text-emerald-700',
                                                ];
                                                $catLabels = [
                                                    'gen_ed'     => 'Gen Ed',
                                                    'prof_ed'    => 'Prof Ed',
                                                    'major'      => 'Major',
                                                    'pe'         => 'PE',
                                                    'nstp'       => 'NSTP',
                                                    'internship' => 'Internship',
                                                ];
                                                $catKey = $subject->category ?? 'major';
                                            @endphp
                                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium {{ $catColors[$catKey] ?? 'bg-slate-100 text-slate-600' }}">
                                                {{ $catLabels[$catKey] ?? $catKey }}
                                            </span>
                                            @break

                                        @case('is_active')
                                            <button type="button"
                                                    @click="openEditStatusModal({{ $subject->id }}, {{ (int) $subject->is_active }}, '{{ addslashes($subject->code) }}')"
                                                    class="hover:opacity-80 transition-opacity focus:outline-none">
                                                @if($subject->is_active)
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
                                            @break

                                        @default
                                            {{ data_get($subject, $key) }}
                                    @endswitch
                                </td>
                            @endforeach

                            {{-- ACTIONS (config-driven) --}}
                            <td class="px-4 py-3 text-sm" data-table="subjects" data-column="actions">
                                <div class="flex gap-1">
                                    @foreach($subjectsActions as $action)
                                        @if($action['type'] === 'modal')
                                            @php
                                                $onclick = isset($action['handler'])
                                                    ? strtr($action['handler'], ['{id}' => $subject->id])
                                                    : "openEditModal('{$action['modal']}', {$subject->id}, 'subjects')";
                                            @endphp
                                            <button type="button"
                                                    onclick="{{ $onclick }}"
                                                    class="inline-flex items-center justify-center h-7 px-2 rounded text-xs {{ $action['class'] }}">
                                                {{ $action['label'] }}
                                            </button>

                                        @elseif($action['type'] === 'js')
                                            <button type="button"
                                                    onclick="{{ $action['handler'] }}({{ $subject->id }}, '{{ addslashes($subject->code) }}')"
                                                    class="inline-flex items-center justify-center h-7 px-2 rounded text-xs {{ $action['class'] }}">
                                                {{ $action['label'] }}
                                            </button>

                                        @elseif($action['type'] === 'delete')
                                            <form method="POST"
                                                  class="inline-flex m-0"
                                                  action="{{ route('program_head.subjects.destroy', $subject->id) }}"
                                                  onsubmit="return confirm('Delete this subject?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="inline-flex items-center justify-center h-7 px-2 rounded text-xs {{ $action['class'] }}">
                                                    {{ $action['label'] }}
                                                </button>
                                            </form>
                                        @endif
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($subjectsColumns) + 1 }}" class="px-4 py-8 text-center text-gray-500 italic">
                                No subjects yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination is rendered automatically by table-pagination.js below this table --}}
    </div>

    {{-- Create / Edit modals (draggable) --}}
    @include('program_head.subjects.partials.modals.subjects')
    @include('program_head.subjects.partials.modals.edit-subject')

    {{-- Existing rich sub-modals --}}
    @include('program_head.subjects.partials.modals.topics')
    @include('program_head.subjects.partials.modals.lessons')
    @include('program_head.subjects.partials.modals.competency')
    @include('program_head.subjects.partials.modals.edit-status')
    @include('program_head.subjects.partials.modals.delete-confirm')

</div>

{{-- Bridge: globals used by config-driven action buttons --}}
<script>
    window.__SUBJECTS__ = @json($subjects);

    function findSubject(id) {
        return (window.__SUBJECTS__ || []).find(s => s.id == id);
    }

    function openSubjectEditModal(id) {
        const s = findSubject(id);
        if (!s) return;
        const form = document.getElementById('subjectEditForm');
        form.action = "{{ url('staff/program-head/subjects') }}/" + id;
        document.getElementById('subjectEdit_name').value         = s.name ?? '';
        document.getElementById('subjectEdit_code').value         = s.code ?? '';
        document.getElementById('subjectEdit_description').value  = s.description ?? '';
        document.getElementById('subjectEdit_active').value       = s.is_active ? '1' : '0';
        const catSel = document.getElementById('subjectEdit_category');
        if (catSel) catSel.value = s.category ?? 'major';
        openModal('subjectEditModal');
    }

    function confirmDeleteSubject(id, code) {
        const root = document.querySelector('[x-data="subjectBuilder"]');
        if (!root) return;
        const data = Alpine.$data(root);
        if (data && typeof data.openDeleteModal === 'function') {
            data.openDeleteModal(id, code);
        }
    }
</script>
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