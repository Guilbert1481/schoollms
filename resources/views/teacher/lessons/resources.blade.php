@extends('layouts.app')

@section('content')
<div class="w-full p-6 bg-slate-50 min-h-screen flex flex-col gap-6"
     x-data="lessonResources()">

    {{-- Page Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="folder-open" class="w-6 h-6 text-indigo-600"></i>
                Resources
            </h1>
            <p class="text-sm text-slate-500 mt-1">Upload and organize teaching materials, slides, and references.</p>
        </div>
        <button type="button"
                @click="openUpload = true"
                class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
            <i data-lucide="upload" class="w-4 h-4"></i>
            Upload
        </button>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 flex flex-wrap gap-3">
        <input type="text"
               x-model="search"
               placeholder="Search resources..."
               class="flex-1 min-w-[220px] rounded-lg border-slate-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" />
        <select x-model="filterType"
                class="rounded-lg border-slate-300 text-sm focus:ring-indigo-500 focus:border-indigo-500">
            <option value="">All Types</option>
            <option value="document">Document</option>
            <option value="slide">Slide</option>
            <option value="video">Video</option>
            <option value="link">Link</option>
        </select>
    </div>

    {{-- Resource Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <template x-if="filteredResources().length === 0">
            <div class="col-span-full bg-white rounded-2xl shadow-sm border border-slate-200 p-10 text-center text-slate-400">
                <i data-lucide="folder" class="w-10 h-10 mx-auto mb-2"></i>
                <p class="text-sm">No resources yet. Click "Upload" to add one.</p>
            </div>
        </template>

        <template x-for="res in filteredResources()" :key="res.id">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 hover:shadow-md transition">
                <div class="flex items-center gap-3 mb-3">
                    <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                        <i :data-lucide="iconForType(res.type)" class="w-5 h-5"></i>
                    </div>
                    <span class="text-xs px-2 py-1 rounded-full bg-slate-100 text-slate-600 capitalize" x-text="res.type"></span>
                </div>
                <p class="font-semibold text-slate-800 truncate" x-text="res.title"></p>
                <p class="text-xs text-slate-500 mt-1" x-text="res.uploadedAt"></p>
            </div>
        </template>
    </div>

    {{-- Upload Modal --}}
    <div x-show="openUpload"
         x-transition.opacity
         class="fixed inset-0 bg-slate-900/50 z-50 flex items-center justify-center p-4"
         style="display: none;">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6"
             @click.outside="openUpload = false">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-slate-800">Upload Resource</h3>
                <button @click="openUpload = false" class="text-slate-400 hover:text-slate-700">
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
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Type</label>
                    <select x-model="draft.type"
                            class="w-full rounded-lg border-slate-300 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="document">Document</option>
                        <option value="slide">Slide</option>
                        <option value="video">Video</option>
                        <option value="link">Link</option>
                    </select>
                </div>
            </div>
            <div class="mt-5 flex justify-end gap-2">
                <button @click="openUpload = false"
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm">Cancel</button>
                <button @click="addResource()"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Save</button>
            </div>
        </div>
    </div>

</div>

<script src="{{ asset('js/lesson/resources.js') }}" defer></script>
@endsection
