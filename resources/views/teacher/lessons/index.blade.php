@extends('layouts.app')

@section('content')
<div class="w-full p-6 bg-slate-50 min-h-screen flex flex-col gap-6"
     x-data="lessonStudio()">

    {{-- Page Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="notebook-pen" class="w-6 h-6 text-indigo-600"></i>
                Lessons
            </h1>
            <p class="text-sm text-slate-500 mt-1">Plan, organize, and publish lesson content for your classes.</p>
        </div>
        <button type="button"
                @click="openCreate = true"
                class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
            <i data-lucide="plus" class="w-4 h-4"></i>
            New Lesson
        </button>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 flex flex-wrap gap-3">
        <input type="text"
               x-model="search"
               placeholder="Search lessons..."
               class="flex-1 min-w-[220px] rounded-lg border-slate-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" />
        <select x-model="filterStatus"
                class="rounded-lg border-slate-300 text-sm focus:ring-indigo-500 focus:border-indigo-500">
            <option value="">All Statuses</option>
            <option value="draft">Draft</option>
            <option value="published">Published</option>
            <option value="archived">Archived</option>
        </select>
    </div>

    {{-- Lesson List --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200">
        <template x-if="filteredLessons().length === 0">
            <div class="p-10 text-center text-slate-400">
                <i data-lucide="inbox" class="w-10 h-10 mx-auto mb-2"></i>
                <p class="text-sm">No lessons yet. Click "New Lesson" to start.</p>
            </div>
        </template>

        <template x-if="filteredLessons().length > 0">
            <ul class="divide-y divide-slate-200">
                <template x-for="lesson in filteredLessons()" :key="lesson.id">
                    <li class="p-4 flex items-center justify-between hover:bg-slate-50">
                        <div>
                            <p class="font-semibold text-slate-800" x-text="lesson.title"></p>
                            <p class="text-xs text-slate-500" x-text="lesson.subject + ' · ' + lesson.updatedAt"></p>
                        </div>
                        <span class="text-xs px-2 py-1 rounded-full"
                              :class="{
                                  'bg-amber-100 text-amber-700': lesson.status === 'draft',
                                  'bg-emerald-100 text-emerald-700': lesson.status === 'published',
                                  'bg-slate-200 text-slate-600': lesson.status === 'archived',
                              }"
                              x-text="lesson.status"></span>
                    </li>
                </template>
            </ul>
        </template>
    </div>

    {{-- Create Modal --}}
    <div x-show="openCreate"
         x-transition.opacity
         class="fixed inset-0 bg-slate-900/50 z-50 flex items-center justify-center p-4"
         style="display: none;">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6"
             @click.outside="openCreate = false">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-slate-800">New Lesson</h3>
                <button @click="openCreate = false" class="text-slate-400 hover:text-slate-700">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Title</label>
                    <input type="text" x-model="draft.title"
                           class="w-full rounded-lg border-slate-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Subject</label>
                    <input type="text" x-model="draft.subject"
                           class="w-full rounded-lg border-slate-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" />
                </div>
            </div>
            <div class="mt-5 flex justify-end gap-2">
                <button @click="openCreate = false"
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm">Cancel</button>
                <button @click="addLesson()"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Save</button>
            </div>
        </div>
    </div>

</div>

<script src="{{ asset('js/lesson/lessons.js') }}" defer></script>
@endsection
