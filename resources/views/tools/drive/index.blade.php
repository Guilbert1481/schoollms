@extends('layouts.app')

@section('content')
<div class="p-4 md:p-6" x-data="drivePage()" x-init="init()">

    {{-- Header --}}
    <div class="mb-4">
        <h1 class="text-2xl md:text-3xl font-bold text-slate-800 flex items-center gap-2">
            <i data-lucide="hard-drive" class="w-7 h-7 text-indigo-600"></i>
            Sophentis Drive
        </h1>
        <p class="text-sm text-slate-600 mt-1">Store documents, share them with colleagues, and manage view/edit access.</p>
    </div>

    {{-- Tabs : My Drive | Shared with me --}}
    @include('partials.tabs', [
        'tabs' => config('tabs.tabs.drive'),
    ])

    {{-- Breadcrumbs (only when drilled into a folder) --}}
    @if(!empty($breadcrumbs))
        <div class="flex items-center gap-1 text-sm mb-3 overflow-x-auto">
            <a href="{{ route('tools.drive.index', ['scope' => $scope]) }}" class="text-indigo-600 hover:underline">
                {{ $scope === 'shared' ? 'Shared with me' : 'My Drive' }}
            </a>
            @foreach($breadcrumbs as $bc)
                <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-400"></i>
                @if($loop->last)
                    <span class="text-slate-700 font-medium">{{ $bc['name'] }}</span>
                @else
                    <a href="{{ route('tools.drive.index', ['scope' => $scope, 'parent_id' => $bc['id']]) }}"
                       class="text-indigo-600 hover:underline">{{ $bc['name'] }}</a>
                @endif
            @endforeach
        </div>
    @endif

    {{-- Global reusable table --}}
    <x-table.table
        tableKey="driveFiles"
        :columns="$columns"
        :data="$data"
        :actions="$actions"
    >
        @if($scope === 'my' && !empty($canUploadHere))
            <button type="button"
                    @click="newFolder()"
                    class="px-3 py-2 border border-gray-300 rounded text-sm bg-white inline-flex items-center gap-1.5">
                <i data-lucide="folder-plus" class="w-4 h-4"></i>
                New folder
            </button>
        @else
            <button type="button"
                    disabled
                    title="Creating folders is only available in My Drive."
                    class="px-3 py-2 border border-gray-200 rounded text-sm bg-slate-50 text-slate-400 cursor-not-allowed inline-flex items-center gap-1.5">
                <i data-lucide="folder-plus" class="w-4 h-4"></i>
                New folder
            </button>
        @endif

        @if(!empty($canUploadHere))
            <button type="button"
                    @click="$refs.uploader.click()"
                    class="px-3 py-2 rounded text-sm text-white bg-indigo-600 hover:bg-indigo-700 inline-flex items-center gap-1.5">
                <i data-lucide="upload" class="w-4 h-4"></i>
                Upload
            </button>
            <input type="file" x-ref="uploader" class="hidden" multiple @change="uploadFiles($event)">
        @else
            <button type="button"
                    disabled
                    title="You don't have permission to upload here."
                    class="px-3 py-2 rounded text-sm bg-indigo-200 text-white cursor-not-allowed inline-flex items-center gap-1.5">
                <i data-lucide="upload" class="w-4 h-4"></i>
                Upload
            </button>
        @endif
    </x-table.table>

    {{-- =============== PREVIEW MODAL =============== --}}
    <div x-show="showPreview"
         @keydown.escape.window="showPreview = false"
         class="fixed inset-0 bg-black/70 z-50 flex items-center justify-center p-6"
         style="display:none;">
        <div class="bg-white rounded-2xl shadow-lg w-full max-w-4xl max-h-[90vh] flex flex-col drive-draggable"
             @click.outside="showPreview = false">
            <div class="flex justify-between items-center px-4 py-3 border-b cursor-move drive-drag-handle"
                 @mousedown="startDrag($event)">
                <p class="font-semibold text-sm truncate" x-text="previewItem?.name"></p>
                <div class="flex items-center gap-2">
                    <a x-show="previewItem?.download"
                       :href="previewItem?.download"
                       class="px-3 py-1.5 bg-indigo-600 text-white rounded-lg text-xs hover:bg-indigo-700">
                        Download
                    </a>
                    <button type="button" @click="showPreview = false"
                            class="text-slate-500 hover:text-slate-800">✕</button>
                </div>
            </div>
            <div class="flex-1 overflow-auto p-4 flex items-center justify-center bg-slate-50 relative">
                <div x-show="previewLoading"
                     class="absolute inset-0 flex flex-col items-center justify-center bg-white/80 z-10"
                     style="display:none;">
                    <svg class="animate-spin w-8 h-8 text-indigo-600 mb-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor"
                              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                    </svg>
                    <p class="text-xs text-slate-600">Preparing preview…</p>
                </div>

                <template x-if="previewItem?.is_image">
                    <img :src="previewItem.preview_url"
                         :alt="previewItem.name"
                         class="max-w-full max-h-[75vh] object-contain"
                         @load="previewLoading = false"
                         x-on:error="previewLoading = false">
                </template>
                <template x-if="previewItem?.is_video">
                    <video :src="previewItem.preview_url" controls class="max-w-full max-h-[75vh]"
                           @loadeddata="previewLoading = false"
                           x-on:error="previewLoading = false"></video>
                </template>
                <template x-if="previewItem && !previewItem.is_image && !previewItem.is_video">
                    <iframe :src="previewItem.preview_url"
                            class="w-full h-[75vh] border-0"
                            :title="previewItem.name"
                            @load="previewLoading = false"></iframe>
                </template>
            </div>
        </div>
    </div>

    {{-- =============== SHARE MODAL =============== --}}
    <div x-show="showShare"
         @keydown.escape.window="showShare = false"
         class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
         style="display:none;">
        <div class="bg-white rounded-2xl shadow-lg w-full max-w-lg drive-draggable"
             @click.outside="showShare = false">
            <div class="px-5 py-3 border-b flex items-center justify-between cursor-move drive-drag-handle"
                 @mousedown="startDrag($event)">
                <h3 class="font-semibold text-slate-800">Share "<span x-text="shareItem?.name"></span>"</h3>
                <button type="button" @click="showShare = false"
                        class="text-slate-500 hover:text-slate-800">✕</button>
            </div>

            <div class="px-5 py-4 space-y-3">
                <div class="relative">
                    <input type="text"
                           x-model="shareSearch"
                           @input.debounce.300ms="searchShareUsers()"
                           placeholder="Search users by name or email…"
                           class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">

                    <div x-show="shareSearchResults.length > 0"
                         class="absolute z-10 left-0 right-0 mt-1 bg-white border border-slate-200 rounded-lg shadow max-h-52 overflow-y-auto"
                         style="display:none;">
                        <template x-for="u in shareSearchResults" :key="u.id">
                            <button type="button"
                                    @click="addShareUser(u)"
                                    class="w-full flex items-center justify-between text-left px-3 py-2 text-sm hover:bg-slate-50">
                                <div>
                                    <p class="font-medium text-slate-800" x-text="u.name"></p>
                                    <p class="text-xs text-slate-500" x-text="u.email"></p>
                                </div>
                                <i data-lucide="plus" class="w-4 h-4 text-indigo-600"></i>
                            </button>
                        </template>
                    </div>
                </div>

                <div class="border rounded-lg divide-y max-h-64 overflow-y-auto">
                    <template x-if="shareList.length === 0">
                        <p class="px-3 py-3 text-xs text-slate-500">Not shared with anyone yet.</p>
                    </template>
                    <template x-for="row in shareList" :key="row.user_id">
                        <div class="px-3 py-2 flex items-center gap-2">
                            <p class="flex-1 text-sm text-slate-800 truncate" x-text="row.name"></p>
                            <select x-model="row.permission"
                                    class="text-xs border border-slate-200 rounded px-2 py-1 bg-white">
                                <option value="view">Can view</option>
                                <option value="edit">Can edit</option>
                            </select>
                            <button type="button" @click="removeShareUser(row.user_id)"
                                    class="text-rose-500 hover:text-rose-700 text-xs">Remove</button>
                        </div>
                    </template>
                </div>
            </div>

            <div class="px-5 py-3 border-t flex justify-end gap-2">
                <button type="button" @click="showShare = false"
                        class="px-3 py-2 border rounded-lg text-sm">Cancel</button>
                <button type="button" @click="saveShares()"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700">Save</button>
            </div>
        </div>
    </div>

    {{-- =============== DELETE CONFIRM =============== --}}
    <div x-show="showDelete"
         @keydown.escape.window="showDelete = false"
         class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
         style="display:none;">
        <div class="bg-white rounded-2xl shadow-lg w-full max-w-sm p-5 drive-draggable"
             @click.outside="showDelete = false">
            <div class="flex items-center justify-between mb-2 cursor-move drive-drag-handle"
                 @mousedown="startDrag($event)">
                <h3 class="font-semibold">Delete "<span x-text="deleteItem?.name"></span>"?</h3>
                <button type="button" @click="showDelete = false" class="text-slate-400 hover:text-slate-700">✕</button>
            </div>
            <p class="text-sm text-slate-600 mb-4">
                <template x-if="deleteItem?.type === 'folder'">
                    <span>This folder and <strong>everything inside it</strong> will be permanently deleted.</span>
                </template>
                <template x-if="deleteItem?.type === 'file'">
                    <span>This file will be permanently deleted.</span>
                </template>
            </p>
            <div class="flex justify-end gap-2">
                <button type="button" @click="showDelete = false"
                        class="px-3 py-2 border rounded-lg text-sm">Cancel</button>
                <button type="button" @click="doDelete()"
                        class="px-4 py-2 bg-rose-600 text-white rounded-lg text-sm hover:bg-rose-700">Delete</button>
            </div>
        </div>
    </div>

    {{-- =============== EDIT MODAL =============== --}}
    <div x-show="showEdit"
         @keydown.escape.window="showEdit = false"
         class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
         style="display:none;">
        <div class="bg-white rounded-2xl shadow-lg w-full max-w-4xl max-h-[90vh] flex flex-col drive-draggable"
             @click.outside="showEdit = false">
            <div class="px-4 py-3 border-b flex items-center justify-between cursor-move drive-drag-handle"
                 @mousedown="startDrag($event)">
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-slate-800 truncate" x-text="editItem?.name"></p>
                    <p class="text-xs text-slate-500 mt-0.5">
                        <span x-show="editSaving">Saving…</span>
                        <span x-show="!editSaving && editSavedAt">Saved <span x-text="editSavedAt"></span></span>
                        <span x-show="!editSaving && !editSavedAt && editEditable === true">All changes auto-save while you type</span>
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="showEdit = false"
                            class="text-slate-500 hover:text-slate-800">✕</button>
                </div>
            </div>

            <div class="flex-1 overflow-auto p-4 bg-slate-50 relative">
                <div x-show="editLoading"
                     class="absolute inset-0 flex items-center justify-center bg-white/80 z-10"
                     style="display:none;">
                    <p class="text-sm text-slate-600">Loading document…</p>
                </div>

                <template x-if="!editLoading && editEditable === true">
                    <textarea x-model="editContent"
                              @input.debounce.800ms="autoSaveContent()"
                              class="w-full h-[65vh] font-mono text-sm border border-slate-200 rounded-lg p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                              :readonly="!editCanEdit"
                              :placeholder="editCanEdit ? 'Start typing…' : 'Read-only — you do not have edit permission.'"></textarea>
                </template>

                <template x-if="!editLoading && editEditable === false">
                    <div class="bg-white border border-slate-200 rounded-lg p-6 text-center">
                        <p class="text-sm text-slate-700" x-text="editMessage"></p>
                        <p class="text-xs text-slate-500 mt-2">Tip: download the file, edit it locally, then use <strong>Replace file</strong> to upload the new version.</p>
                        <div class="mt-4 flex justify-center gap-2">
                            <a :href="editItem?.download"
                               class="px-3 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">Download</a>
                            <button type="button"
                                    @click="$refs.editReplace.click()"
                                    x-show="editCanEdit"
                                    class="px-3 py-2 rounded-lg bg-emerald-600 text-white text-sm hover:bg-emerald-700">Replace file…</button>
                            <input type="file" x-ref="editReplace" class="hidden" @change="replaceFromEdit($event)">
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- =============== EDITORS / HISTORY MODAL =============== --}}
    <div x-show="showHistory"
         @keydown.escape.window="showHistory = false"
         class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
         style="display:none;">
        <div class="bg-white rounded-2xl shadow-lg w-full max-w-md max-h-[80vh] flex flex-col drive-draggable"
             @click.outside="showHistory = false">
            <div class="px-4 py-3 border-b flex items-center justify-between cursor-move drive-drag-handle"
                 @mousedown="startDrag($event)">
                <h3 class="font-semibold text-slate-800">Edit history</h3>
                <button type="button" @click="showHistory = false" class="text-slate-400 hover:text-slate-700">✕</button>
            </div>
            <div class="flex-1 overflow-auto p-4 space-y-2">
                <p x-show="historyLoading" class="text-sm text-slate-500">Loading…</p>
                <p x-show="!historyLoading && historyRows.length === 0" class="text-sm text-slate-500">
                    No edits recorded yet.
                </p>
                <template x-for="row in historyRows" :key="row.id">
                    <div class="border border-slate-200 rounded-lg p-3 bg-white">
                        <div class="flex items-center justify-between">
                            <p class="font-medium text-slate-800 text-sm" x-text="row.user"></p>
                            <span class="text-[10px] uppercase tracking-wider text-slate-500 bg-slate-100 rounded px-1.5 py-0.5"
                                  x-text="row.action"></span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5" x-text="row.summary || 'No summary'"></p>
                        <p class="text-xs text-slate-400 mt-1" x-text="row.at"></p>
                    </div>
                </template>
            </div>
        </div>
    </div>

</div>

<style>
    .drive-drag-handle { user-select: none; }
</style>

<script>
    window.__driveItems = @json($itemsMap);
    window.__driveScope = @json($scope);
    window.__driveRowIds = @json($data->pluck('id')->values());
</script>

<script>
function drivePage(){
    return {
        scope: window.__driveScope || 'my',
        items: window.__driveItems || {},

        showPreview: false,
        previewItem: null,
        previewLoading: false,

        showShare: false,
        shareItem: null,
        shareSearch: '',
        shareSearchResults: [],
        shareList: [],

        showDelete: false,
        deleteItem: null,

        // Edit
        showEdit: false,
        editItem: null,
        editLoading: false,
        editEditable: null,   // true | false | null while loading
        editCanEdit: false,
        editMessage: '',
        editContent: '',
        editSaving: false,
        editSavedAt: '',

        // History
        showHistory: false,
        historyLoading: false,
        historyRows: [],

        drag: { active: false, dx: 0, dy: 0, el: null },

        init(){
            if(window.lucide) window.lucide.createIcons();

            // Expose action handlers for server-rendered x-table.table action buttons.
            window.driveView    = (id) => this.actView(id);
            window.driveEdit    = (id) => this.openEdit(this.items[id]);
            window.driveShare   = (id) => this.openShare(this.items[id]);
            window.driveDelete  = (id) => this.confirmDelete(this.items[id]);
            window.driveEditors = (id) => this.openHistory(this.items[id]);

            // The server emitted an ordered list of file ids matching the row order
            // of the rendered table. We use this to associate <tr>s with file ids,
            // which is more robust than sniffing action-button onclick (action
            // buttons can be empty, e.g. in shared scope).
            const rowIds = Array.isArray(window.__driveRowIds) ? window.__driveRowIds : [];
            const tbodyRows = document.querySelectorAll('#driveFilesTable tbody tr');
            tbodyRows.forEach((tr, idx) => {
                const id = rowIds[idx];
                if(id == null) return;
                tr.dataset.driveId = id;
            });

            // Upgrade "Updated by" cells into clickable buttons that open the history modal.
            document.querySelectorAll('td[data-table="driveFiles"][data-column="last_editor_name"]').forEach((td) => {
                const tr = td.closest('tr');
                const id = tr && parseInt(tr.dataset.driveId, 10);
                if(!id) return;
                const text = td.textContent.trim();
                td.innerHTML = '';
                const item = this.items[id];
                // Only file-owner / ancestor-folder-owner may see the history.
                if(item && item.can_open){
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'text-indigo-600 hover:underline';
                    btn.textContent = text;
                    btn.addEventListener('click', () => this.openHistory(item));
                    td.appendChild(btn);
                } else {
                    const span = document.createElement('span');
                    span.className = 'text-slate-500';
                    span.textContent = text;
                    td.appendChild(span);
                }
            });

            // Upgrade Name cells so folders navigate in, files open preview — Google-Drive-like.
            document.querySelectorAll('td[data-table="driveFiles"][data-column="name"]').forEach((td) => {
                const tr = td.closest('tr');
                const id = tr && parseInt(tr.dataset.driveId, 10);
                if(!id) return;
                const item = this.items[id];
                if(!item) return;
                const text = td.textContent.trim();
                const isFolder = item.type === 'folder';
                const openable = isFolder || !!item.can_open;
                td.innerHTML = '';
                const icon = document.createElement('i');
                icon.setAttribute('data-lucide', isFolder ? 'folder' : 'file-text');
                icon.className = isFolder
                    ? 'w-4 h-4 text-amber-500'
                    : (openable ? 'w-4 h-4 text-slate-500' : 'w-4 h-4 text-slate-300');
                const label = document.createElement('span');
                label.className = 'truncate';
                label.textContent = text;

                if(openable){
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'inline-flex items-center gap-2 text-left text-slate-800 hover:text-indigo-600 hover:underline';
                    btn.appendChild(icon);
                    btn.appendChild(label);
                    btn.addEventListener('click', () => this.actView(id));
                    td.appendChild(btn);
                } else {
                    const wrap = document.createElement('span');
                    wrap.className = 'inline-flex items-center gap-2 text-slate-400 cursor-not-allowed';
                    wrap.title = 'Only the file owner can open this file.';
                    wrap.appendChild(icon);
                    wrap.appendChild(label);
                    td.appendChild(wrap);
                }

                // Folder ZIP download link (owner / ancestor-folder-owner only).
                if(isFolder && item.zip_url){
                    const zipBtn = document.createElement('a');
                    zipBtn.href = item.zip_url;
                    zipBtn.title = 'Download folder as ZIP';
                    zipBtn.className = 'ml-2 inline-flex items-center text-slate-400 hover:text-indigo-600';
                    zipBtn.innerHTML = '<i data-lucide="download" class="w-4 h-4"></i>';
                    zipBtn.addEventListener('click', (e) => e.stopPropagation());
                    td.appendChild(zipBtn);
                }
            });

            // Make the whole row double-clickable too (classic file-manager behavior).
            tbodyRows.forEach((tr) => {
                const id = parseInt(tr.dataset.driveId || '', 10);
                if(!id) return;
                const item = this.items[id];
                const openable = item && (item.type === 'folder' || item.can_open);
                if(openable){
                    tr.classList.add('cursor-pointer');
                    tr.addEventListener('dblclick', (e) => {
                        if(e.target.closest('button,a,input')) return;
                        this.actView(id);
                    });
                }

                // In shared scope, disable row action buttons for files the
                // viewer did NOT upload themselves. Their own uploads stay active.
                if(this.scope === 'shared'){
                    const manageable = item && item.is_mine;
                    if(!manageable){
                        tr.querySelectorAll('td.action-column button').forEach((b) => {
                            b.setAttribute('disabled', 'disabled');
                            b.classList.add('opacity-50', 'cursor-not-allowed');
                            b.classList.remove('hover:bg-indigo-700','hover:bg-emerald-700','hover:bg-slate-300','hover:bg-rose-700');
                            b.title = 'Only the file owner can manage this file.';
                            // Neutralize the inline onclick handler.
                            b.onclick = (ev) => { ev.preventDefault(); ev.stopPropagation(); };
                        });
                    }
                }
            });

            // Re-render lucide icons after DOM mutations.
            if(window.lucide) window.lucide.createIcons();
        },

        csrf(){ return document.querySelector('meta[name="csrf-token"]').content; },

        actView(id){
            const item = this.items[id];
            if(!item) return;
            if(item.type === 'folder'){
                const url = new URL(window.location.href);
                url.searchParams.set('scope', this.scope);
                url.searchParams.set('parent_id', item.id);
                window.location.href = url.toString();
            } else {
                // Only the file owner (or an ancestor-folder owner) may open the file.
                if(!item.can_open){ return; }
                this.previewItem = null;
                // Only show the "Preparing preview" spinner for Office/PDF files
                // that may take time to convert. Images & videos render instantly.
                this.previewLoading = !!(item.is_office || item.is_pdf);
                this.showPreview = true;
                this.$nextTick(() => {
                    this.previewItem = item;
                    // Safety net: clear spinner after 15s no matter what.
                    setTimeout(() => { this.previewLoading = false; }, 15000);
                });
            }
        },

        // ==== EDIT ====
        async openEdit(item){
            if(!item) return;
            // Clicking Edit on a folder simply navigates into it (same as clicking the name).
            if(item.type === 'folder'){ this.actView(item.id); return; }
            this.editItem = item;
            this.editLoading = true;
            this.editEditable = null;
            this.editContent = '';
            this.editMessage = '';
            this.editSavedAt = '';
            this.editSaving = false;
            this.showEdit = true;
            try {
                const res = await fetch(item.content_url, { headers: { 'Accept':'application/json' }});
                const data = await res.json();
                this.editEditable = !!data.editable;
                this.editContent  = data.content || '';
                this.editCanEdit  = !!data.can_edit;
                this.editMessage  = data.message || '';
            } catch(e){ console.error(e); this.editEditable = false; this.editMessage = 'Could not load this file.'; }
            finally { this.editLoading = false; }
        },
        async autoSaveContent(){
            if(!this.editItem || !this.editCanEdit) return;
            this.editSaving = true;
            try {
                const res = await fetch(`/tools/drive/${this.editItem.id}/content`, {
                    method: 'PUT',
                    headers: { 'Accept':'application/json', 'Content-Type':'application/json', 'X-CSRF-TOKEN': this.csrf() },
                    body: JSON.stringify({ content: this.editContent }),
                });
                const data = await res.json();
                if(res.ok){
                    this.editSavedAt = data.saved_at || '';
                }
            } catch(e){ console.error(e); }
            finally { this.editSaving = false; }
        },
        async replaceFromEdit(e){
            const file = e.target.files && e.target.files[0];
            e.target.value = '';
            if(!file || !this.editItem) return;
            const fd = new FormData();
            fd.append('file', file);
            try {
                const res = await fetch(`/tools/drive/${this.editItem.id}/replace`, {
                    method: 'POST',
                    headers: { 'Accept':'application/json', 'X-CSRF-TOKEN': this.csrf() },
                    body: fd,
                });
                if(res.ok) window.location.reload();
            } catch(err){ console.error(err); }
        },

        // ==== HISTORY ====
        async openHistory(item){
            if(!item) return;
            this.historyLoading = true;
            this.historyRows = [];
            this.showHistory = true;
            try {
                const res = await fetch(item.history_url, { headers: { 'Accept':'application/json' }});
                const data = await res.json();
                this.historyRows = data.edits || [];
            } catch(e){ console.error(e); }
            finally { this.historyLoading = false; }
        },

        async newFolder(){
            const name = prompt('Folder name:');
            if(!name || !name.trim()) return;
            const fd = new FormData();
            fd.append('name', name.trim());
            const params = new URL(window.location.href).searchParams;
            if(params.get('parent_id')) fd.append('parent_id', params.get('parent_id'));
            try {
                const res = await fetch(`{{ route('tools.drive.folders.store') }}`, {
                    method: 'POST',
                    headers: { 'Accept':'application/json', 'X-CSRF-TOKEN': this.csrf() },
                    body: fd,
                });
                if(res.ok) window.location.reload();
                else { const d = await res.json().catch(() => ({})); alert(Object.values(d.errors || {message:['Error']}).flat().join('\n')); }
            } catch(e){ console.error(e); }
        },

        async uploadFiles(e){
            const files = Array.from(e.target.files || []);
            if(files.length === 0) return;
            const fd = new FormData();
            files.forEach(f => fd.append('files[]', f));
            const params = new URL(window.location.href).searchParams;
            if(params.get('parent_id')) fd.append('parent_id', params.get('parent_id'));

            try {
                const res = await fetch(`{{ route('tools.drive.files.store') }}`, {
                    method: 'POST',
                    headers: { 'Accept':'application/json', 'X-CSRF-TOKEN': this.csrf() },
                    body: fd,
                });
                if(res.ok) window.location.reload();
                else { const d = await res.json().catch(() => ({})); alert(Object.values(d.errors || {message:['Upload failed']}).flat().join('\n')); }
            } catch(err){ console.error(err); }
            finally { e.target.value = ''; }
        },

        confirmDelete(item){
            if(!item) return;
            this.deleteItem = item;
            this.showDelete = true;
        },
        async doDelete(){
            if(!this.deleteItem) return;
            const id = this.deleteItem.id;
            try {
                const res = await fetch(`/tools/drive/${id}`, {
                    method: 'DELETE',
                    headers: { 'Accept':'application/json', 'X-CSRF-TOKEN': this.csrf() },
                });
                if(res.ok) window.location.reload();
            } catch(e){ console.error(e); }
            finally { this.showDelete = false; }
        },

        async openShare(item){
            if(!item) return;
            this.shareItem = item;
            this.shareSearch = '';
            this.shareSearchResults = [];
            this.shareList = [];
            this.showShare = true;
            try {
                const res = await fetch(`/tools/drive/${item.id}/shares`, { headers: { 'Accept':'application/json' }});
                const data = await res.json();
                this.shareList = (data.shares || []).map(s => ({ user_id: s.user_id, name: s.name, permission: s.permission }));
            } catch(e){ console.error(e); }
        },
        async searchShareUsers(){
            const q = this.shareSearch.trim();
            if(q === ''){ this.shareSearchResults = []; return; }
            try {
                const res = await fetch(`{{ route('tools.drive.users.search') }}?q=` + encodeURIComponent(q), {
                    headers: { 'Accept':'application/json' },
                });
                const data = await res.json();
                const taken = new Set(this.shareList.map(r => r.user_id));
                this.shareSearchResults = (data || []).filter(u => !taken.has(u.id));
            } catch(e){ console.error(e); }
        },
        addShareUser(u){
            this.shareList.push({ user_id: u.id, name: u.name, permission: 'view' });
            this.shareSearch = '';
            this.shareSearchResults = [];
        },
        removeShareUser(userId){
            this.shareList = this.shareList.filter(r => r.user_id !== userId);
        },
        async saveShares(){
            if(!this.shareItem) return;
            try {
                const res = await fetch(`/tools/drive/${this.shareItem.id}/share`, {
                    method: 'POST',
                    headers: { 'Accept':'application/json', 'Content-Type':'application/json', 'X-CSRF-TOKEN': this.csrf() },
                    body: JSON.stringify({
                        shares: this.shareList.map(r => ({ user_id: r.user_id, permission: r.permission })),
                    }),
                });
                if(res.ok){ this.showShare = false; }
            } catch(e){ console.error(e); }
        },

        // ==== Draggable modal ====
        startDrag(e){
            const modal = e.currentTarget.closest('.drive-draggable');
            if(!modal) return;
            const rect = modal.getBoundingClientRect();
            // Fix modal to position:fixed so we can freely move it.
            modal.style.position = 'fixed';
            modal.style.left = rect.left + 'px';
            modal.style.top  = rect.top  + 'px';
            modal.style.margin = '0';

            this.drag.active = true;
            this.drag.el = modal;
            this.drag.dx = e.clientX - rect.left;
            this.drag.dy = e.clientY - rect.top;

            const onMove = (ev) => {
                if(!this.drag.active || !this.drag.el) return;
                this.drag.el.style.left = (ev.clientX - this.drag.dx) + 'px';
                this.drag.el.style.top  = (ev.clientY - this.drag.dy) + 'px';
            };
            const onUp = () => {
                this.drag.active = false;
                this.drag.el = null;
                document.removeEventListener('mousemove', onMove);
                document.removeEventListener('mouseup', onUp);
            };
            document.addEventListener('mousemove', onMove);
            document.addEventListener('mouseup', onUp);
            e.preventDefault();
        },
    };
}
</script>
@endsection
