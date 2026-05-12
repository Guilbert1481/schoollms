@extends('communication.layout')

@section('communication-content')

@php
    $threadsPayload = $threads->map(function ($t) {
        $displayTitle = $t->title
            ?? ($t->type === 'private' && $t->other_name ? $t->other_name : 'Group Conversation');
        return [
            'id'           => $t->id,
            'title'        => $displayTitle,
            'type'         => $t->type,
            'updated_at'   => optional($t->updated_at)->diffForHumans(),
            'unread_count' => (int) ($t->unread_count ?? 0),
            'initial'      => strtoupper(substr($displayTitle ?: '?', 0, 1)),
            'created_by'   => (int) ($t->created_by ?? 0),
            'is_creator'   => (int) ($t->created_by ?? 0) === (int) auth()->id(),
        ];
    })->values();
@endphp

<div x-data='chatPage(@json($threadsPayload))'
     x-init="init()"
     class="grid grid-cols-12 gap-4 h-[75vh]">

    {{-- ======================== LEFT PANE (25%) ======================== --}}
    <aside class="col-span-12 md:col-span-3 bg-white border border-gray-300 rounded-2xl shadow-sm flex flex-col overflow-hidden">

        {{-- Search --}}
        <div class="p-3 border-b border-gray-300 relative">
            <div class="relative">
                <input type="text"
                       x-model="userSearch"
                       @input.debounce.300ms="searchUsers()"
                       placeholder="Search users by name..."
                       class="w-full border border-gray-200 rounded-xl pl-9 pr-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <i data-lucide="search" class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
            </div>

            {{-- Search results dropdown --}}
            <div x-show="searchResults.length > 0"
                 x-transition
                 @click.outside="searchResults = []"
                 class="absolute left-3 right-3 top-full mt-1 bg-white border border-gray-200 rounded-xl shadow-lg z-20 max-h-64 overflow-y-auto"
                 style="display:none;">
                <template x-for="u in searchResults" :key="u.id">
                    <button type="button"
                            @click="startDirect(u)"
                            class="w-full text-left px-3 py-2 hover:bg-indigo-50 flex items-center gap-2 text-sm">
                        <span class="w-7 h-7 rounded-full bg-indigo-100 text-indigo-600 text-xs font-semibold flex items-center justify-center"
                              x-text="u.name.charAt(0).toUpperCase()"></span>
                        <span>
                            <span class="block font-medium text-gray-800" x-text="u.name"></span>
                            <span class="block text-xs text-gray-500" x-text="u.email"></span>
                        </span>
                    </button>
                </template>
            </div>
        </div>

        {{-- Threads list --}}
        <div class="flex-1 overflow-y-auto divide-y divide-gray-200">
            <template x-for="t in filteredThreads" :key="t.id">
                <div class="relative group">
                    <button type="button"
                            @click="selectThread(t)"
                            :class="{
                                'bg-indigo-50': activeThreadId === t.id,
                                'bg-indigo-50/40': t.unread_count > 0 && activeThreadId !== t.id
                            }"
                            class="w-full text-left px-4 py-3 hover:bg-gray-50 transition">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-sm font-semibold"
                                 x-text="t.initial"></div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-sm font-medium text-gray-900 truncate" x-text="t.title"></p>
                                    <span x-show="t.unread_count > 0"
                                          x-text="t.unread_count"
                                          class="text-[10px] font-semibold bg-red-500 text-white rounded-full px-1.5 py-0.5"></span>
                                </div>
                                <p class="text-xs text-gray-500 truncate">
                                    <span x-text="t.type.charAt(0).toUpperCase() + t.type.slice(1)"></span>
                                    · <span x-text="t.updated_at"></span>
                                </p>
                            </div>
                        </div>
                    </button>

                    {{-- Kebab menu --}}
                    <div class="absolute top-2 right-2" @click.outside="menuOpenId = null">
                        <button type="button"
                                @click.stop="menuOpenId = (menuOpenId === t.id ? null : t.id)"
                                class="p-1 rounded-full hover:bg-gray-200 text-gray-500 opacity-0 group-hover:opacity-100 transition"
                                :class="{ 'opacity-100': menuOpenId === t.id }"
                                title="More">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4z"/></svg>
                        </button>
                        <div x-show="menuOpenId === t.id"
                             x-transition
                             class="absolute right-0 mt-1 w-44 bg-white border border-gray-200 rounded-xl shadow-lg z-30 py-1 text-sm"
                             style="display:none;">
                            <template x-if="t.type === 'group'">
                                <div>
                                    <button type="button" @click.stop="openMembers(t); menuOpenId = null"
                                            class="w-full text-left px-3 py-2 hover:bg-gray-50">See members</button>
                                    <button type="button" @click.stop="openAddMembers(t); menuOpenId = null"
                                            class="w-full text-left px-3 py-2 hover:bg-gray-50">Add members</button>
                                    <template x-if="t.is_creator">
                                        <button type="button" @click.stop="openRemoveMembers(t); menuOpenId = null"
                                                class="w-full text-left px-3 py-2 hover:bg-gray-50">Remove members</button>
                                    </template>
                                    <div class="border-t my-1"></div>
                                </div>
                            </template>
                            <button type="button" @click.stop="confirmDeleteThread(t); menuOpenId = null"
                                    class="w-full text-left px-3 py-2 text-red-600 hover:bg-red-50">
                                <span x-text="t.type === 'group' ? 'Delete group' : 'Delete chat'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </template>

            <div x-show="filteredThreads.length === 0" class="p-6 text-center text-sm text-gray-500">
                No conversations.
            </div>
        </div>
    </aside>

    {{-- ======================== RIGHT PANE (75%) ======================== --}}
    <section class="col-span-12 md:col-span-9 bg-white border border-gray-300 rounded-2xl shadow-sm flex flex-col overflow-hidden">

        {{-- Top tabs --}}
        <div class="flex items-center gap-2 px-4 py-3 border-b border-gray-300">
            <button type="button"
                    @click="openCreateGroup()"
                    class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700 transition">
                + Create Group Chat
            </button>

            <div class="w-px h-6 bg-gray-200 mx-1"></div>

            <template x-for="tab in ['all','unread','folders','notes']" :key="tab">
                <button type="button"
                        @click="activeTab = tab"
                        :class="activeTab === tab
                            ? 'bg-gray-100 text-gray-900'
                            : 'text-gray-500 hover:bg-gray-50'"
                        class="px-3 py-1.5 rounded-lg text-sm capitalize"
                        x-text="tab"></button>
            </template>
        </div>

        {{-- Chat viewport --}}
        <div class="flex-1 flex flex-col overflow-hidden">

            {{-- Empty state --}}
            <div x-show="!activeThreadId" class="flex-1 flex items-center justify-center text-center p-8 text-gray-500" style="display:none;">
                <div>
                    <i data-lucide="message-circle" class="w-10 h-10 mx-auto mb-3 text-gray-300"></i>
                    <p class="text-sm">Select a conversation on the left, or start a new one by searching for a user.</p>
                </div>
            </div>

            {{-- Conversation header --}}
            <div x-show="activeThreadId" class="px-5 py-3 border-b border-gray-300 flex items-center justify-between" style="display:none;">
                <div>
                    <p class="font-semibold text-gray-900" x-text="activeThread?.title"></p>
                    <p class="text-xs text-gray-500 capitalize" x-text="(activeThread?.type || '') + ' conversation'"></p>
                </div>
            </div>

            {{-- Messages --}}
            <div x-show="activeThreadId"
                 x-ref="messageList"
                 class="flex-1 overflow-y-auto p-5 space-y-4 bg-gray-50"
                 style="display:none;">
                <template x-for="(m, idx) in messages" :key="m.id">
                    <div>
                        {{-- Row: avatar (left only) + bubble --}}
                        <div :class="m.is_mine ? 'justify-end' : 'justify-start'" class="flex items-end gap-2">
                            {{-- Other user's avatar, hidden if same sender as previous message --}}
                            <template x-if="!m.is_mine">
                                <img :src="m.user_avatar"
                                     :class="(idx > 0 && messages[idx-1].user_id === m.user_id) ? 'invisible' : ''"
                                     class="w-8 h-8 rounded-full object-cover border border-gray-300 shadow-sm"
                                     :alt="m.user_name">
                            </template>

                            <div :class="m.is_mine ? 'items-end' : 'items-start'" class="flex flex-col max-w-md">
                                {{-- Sender name above the first bubble in a run --}}
                                <p x-show="!m.is_mine && (idx === 0 || messages[idx-1].user_id !== m.user_id)"
                                   class="text-[11px] font-semibold text-gray-600 mb-0.5 ml-1"
                                   x-text="m.user_name"></p>

                                <div :class="m.is_mine
                                        ? 'bg-indigo-600 text-white rounded-br-sm'
                                        : 'bg-white border border-gray-300 text-gray-800 rounded-bl-sm'"
                                     class="px-3 py-2 rounded-2xl shadow-sm">
                                    <p x-show="m.message" class="text-sm whitespace-pre-wrap" x-text="m.message"></p>

                                    {{-- Attachment rendering --}}
                                    <template x-if="m.attachment">
                                        <div class="mt-1">
                                            {{-- Expired: show placeholder only --}}
                                            <template x-if="m.attachment.expired">
                                                <div :class="m.is_mine ? 'bg-indigo-700/40 text-indigo-100' : 'bg-gray-100 text-gray-500'"
                                                     class="flex items-center gap-2 px-3 py-2 rounded-lg text-xs italic">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                    <span>File expired — <span x-text="m.attachment.name"></span></span>
                                                </div>
                                            </template>

                                            {{-- Image --}}
                                            <template x-if="!m.attachment.expired && m.attachment.is_image">
                                                <img :src="m.attachment.url"
                                                     :alt="m.attachment.name"
                                                     @click="openPreview(m)"
                                                     class="max-w-[240px] max-h-60 rounded-lg cursor-pointer object-cover border border-gray-200">
                                            </template>

                                            {{-- Video --}}
                                            <template x-if="!m.attachment.expired && m.attachment.is_video">
                                                <video :src="m.attachment.url" controls
                                                       class="max-w-[240px] max-h-60 rounded-lg border border-gray-200"></video>
                                            </template>

                                            {{-- Document --}}
                                            <template x-if="!m.attachment.expired && !m.attachment.is_image && !m.attachment.is_video">
                                                <button type="button"
                                                        @click="openPreview(m)"
                                                        :class="m.is_mine ? 'bg-indigo-500/30 hover:bg-indigo-500/40' : 'bg-gray-100 hover:bg-gray-200'"
                                                        class="flex items-center gap-2 px-3 py-2 rounded-lg text-xs w-full text-left">
                                                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z"/></svg>
                                                    <span class="truncate">
                                                        <span class="font-medium block truncate" x-text="m.attachment.name"></span>
                                                        <span class="opacity-75" x-text="formatBytes(m.attachment.size)"></span>
                                                    </span>
                                                </button>
                                            </template>
                                        </div>
                                    </template>

                                    <p :class="m.is_mine ? 'text-indigo-100' : 'text-gray-400'"
                                       class="text-[10px] mt-1 text-right"
                                       x-text="m.created_at"></p>
                                </div>
                            </div>
                        </div>

                        {{-- Seen-by avatars (facebook-messenger style), only on the last message each viewer has seen --}}
                        <template x-if="seenByFor(idx).length > 0">
                            <div :class="m.is_mine ? 'justify-end' : 'justify-start pl-10'" class="flex mt-1 gap-1">
                                <template x-for="s in seenByFor(idx)" :key="s.id">
                                    <img :src="s.avatar"
                                         :alt="s.name"
                                         :title="'Seen by ' + s.name"
                                         class="w-4 h-4 rounded-full border border-white shadow">
                                </template>
                            </div>
                        </template>
                    </div>
                </template>

                <div x-show="messages.length === 0" class="text-center text-sm text-gray-400 py-10">
                    No messages yet. Say hello!
                </div>
            </div>

            {{-- Composer --}}
            <div x-show="activeThreadId" class="border-t border-gray-300" style="display:none;">
                {{-- Selected file preview --}}
                <div x-show="pendingFile" class="px-3 pt-2" style="display:none;">
                    <div class="flex items-center gap-2 bg-gray-50 border rounded-lg px-3 py-2 text-xs">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656L5.172 9"/></svg>
                        <span class="flex-1 truncate" x-text="pendingFile?.name"></span>
                        <span class="text-gray-400" x-text="formatBytes(pendingFile?.size)"></span>
                        <button type="button" @click="clearPendingFile()" class="text-red-500 hover:text-red-700">✕</button>
                    </div>
                </div>

                <form @submit.prevent="sendMessage()" class="p-3 flex items-center gap-2">
                    {{-- Upload button with menu --}}
                    <div class="relative" @click.outside="uploadMenuOpen = false">
                        <button type="button"
                                @click="uploadMenuOpen = !uploadMenuOpen"
                                title="Attach"
                                class="p-2 text-gray-500 hover:text-indigo-600 hover:bg-gray-100 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656L5.172 9"/></svg>
                        </button>
                        <div x-show="uploadMenuOpen"
                             x-transition
                             class="absolute bottom-full mb-2 left-0 w-44 bg-white border rounded-xl shadow-lg py-1 text-sm z-20"
                             style="display:none;">
                            <button type="button" @click="pickFile('document'); uploadMenuOpen=false"
                                    class="w-full text-left px-3 py-2 hover:bg-gray-50 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z"/></svg>
                                Document
                            </button>
                            <button type="button" @click="pickFile('image'); uploadMenuOpen=false"
                                    class="w-full text-left px-3 py-2 hover:bg-gray-50 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M4 16l4-4a3 3 0 014 0l4 4m0-4l2-2a3 3 0 014 0l2 2M4 6h16v12H4z"/></svg>
                                Image
                            </button>
                            <button type="button" @click="pickFile('video'); uploadMenuOpen=false"
                                    class="w-full text-left px-3 py-2 hover:bg-gray-50 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                Video
                            </button>
                        </div>
                        <input type="file" x-ref="fileInput" class="hidden" @change="handleFileSelect($event)">
                    </div>

                    {{-- Mic (speech-to-text) --}}
                    <button type="button"
                            @click="toggleDictation()"
                            :title="isDictating ? 'Stop dictation' : 'Voice to text'"
                            :class="isDictating ? 'text-red-600 bg-red-50 animate-pulse' : 'text-gray-500 hover:text-indigo-600 hover:bg-gray-100'"
                            class="p-2 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M19 11a7 7 0 01-14 0m7 7v4m-4 0h8M12 3a3 3 0 013 3v5a3 3 0 11-6 0V6a3 3 0 013-3z"/></svg>
                    </button>

                    <input type="text"
                           x-model="newMessage"
                           placeholder="Type a message..."
                           class="flex-1 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <button type="submit"
                            class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm hover:bg-indigo-700 transition">
                        Send
                    </button>
                </form>
            </div>

        </div>
    </section>

    {{-- ============== CREATE GROUP CHAT MODAL ============== --}}
    <div x-show="showCreateModal"
         @keydown.escape.window="showCreateModal = false"
         class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center"
         style="display:none;">
        <div class="bg-white rounded-2xl shadow-lg w-full max-w-2xl p-6" @click.outside="showCreateModal = false">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Create Group Chat</h3>
                <button type="button" @click="showCreateModal = false" class="text-gray-500 hover:text-gray-800">✕</button>
            </div>

            <form method="POST" action="{{ route('communication.chat.store') }}">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-gray-700 mb-1 block">Group Title</label>
                        <input type="text" name="title"
                               class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm"
                               placeholder="e.g. BSIT 3A Project Team">
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700 mb-1 block">Participants</label>
                        <x-assignable-dropdown :groups="$groups" />
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-5">
                    <button type="button" @click="showCreateModal = false"
                            class="px-4 py-2 border rounded-lg text-sm">Cancel</button>
                    <button type="submit"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm">Create</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============== MEMBERS MODAL (see / add / remove) ============== --}}
    <div x-show="showMembersModal"
         @keydown.escape.window="showMembersModal = false"
         class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center"
         style="display:none;">
        <div class="bg-white rounded-2xl shadow-lg w-full max-w-xl p-6" @click.outside="showMembersModal = false">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">
                    <span x-text="membersMode === 'add' ? 'Add Members' : (membersMode === 'remove' ? 'Remove Members' : 'Group Members')"></span>
                </h3>
                <button type="button" @click="showMembersModal = false" class="text-gray-500 hover:text-gray-800">✕</button>
            </div>

            {{-- ADD MODE: search users to add --}}
            <div x-show="membersMode === 'add'" style="display:none;">
                <input type="text" x-model="memberSearch" @input.debounce.200ms="searchMemberCandidates()"
                       placeholder="Search users to add..."
                       class="w-full border rounded-lg px-3 py-2 text-sm mb-3">
                <div class="max-h-72 overflow-y-auto divide-y">
                    <template x-for="u in memberCandidates" :key="u.id">
                        <div class="flex items-center justify-between py-2">
                            <div>
                                <p class="text-sm font-medium" x-text="u.name"></p>
                                <p class="text-xs text-gray-500" x-text="u.email"></p>
                            </div>
                            <button type="button" @click="addMember(u.id)"
                                    class="px-3 py-1 text-xs bg-indigo-600 text-white rounded-lg">Add</button>
                        </div>
                    </template>
                    <template x-if="memberCandidates.length === 0">
                        <div class="py-6 text-center text-sm text-gray-400">No users found.</div>
                    </template>
                </div>
            </div>

            {{-- SEE / REMOVE MODE --}}
            <div x-show="membersMode !== 'add'" style="display:none;">
                <div class="max-h-72 overflow-y-auto divide-y">
                    <template x-for="m in members" :key="m.id">
                        <div class="flex items-center justify-between py-2">
                            <div>
                                <p class="text-sm font-medium">
                                    <span x-text="m.name"></span>
                                    <span x-show="m.is_creator" class="ml-2 text-[10px] font-semibold text-indigo-600 bg-indigo-100 px-2 py-0.5 rounded-full">Creator</span>
                                </p>
                                <p class="text-xs text-gray-500" x-text="m.email"></p>
                            </div>
                            <template x-if="membersMode === 'remove' && !m.is_creator">
                                <button type="button" @click="removeMember(m.id)"
                                        class="px-3 py-1 text-xs bg-red-600 text-white rounded-lg">Remove</button>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-5">
                <template x-if="membersMode === 'remove' && membersThread?.is_creator">
                    <button type="button" @click="membersMode = 'view'"
                            class="px-4 py-2 border rounded-lg text-sm">Back</button>
                </template>
                <template x-if="membersMode === 'add'">
                    <button type="button" @click="membersMode = 'view'"
                            class="px-4 py-2 border rounded-lg text-sm">Back</button>
                </template>
                <button type="button" @click="showMembersModal = false"
                        class="px-4 py-2 bg-gray-100 rounded-lg text-sm">Close</button>
            </div>
        </div>
    </div>

    {{-- ============== DELETE CONFIRM MODAL ============== --}}
    <div x-show="showDeleteModal"
         @keydown.escape.window="showDeleteModal = false"
         class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center"
         style="display:none;">
        <div class="bg-white rounded-2xl shadow-lg w-full max-w-sm p-6" @click.outside="showDeleteModal = false">
            <h3 class="text-lg font-semibold mb-2">Delete conversation?</h3>
            <p class="text-sm text-gray-600 mb-4">
                This will permanently delete
                <span class="font-semibold" x-text="deleteTarget?.title"></span>
                and all its messages. This cannot be undone.
            </p>
            <div class="flex justify-end gap-2">
                <button type="button" @click="showDeleteModal = false"
                        class="px-4 py-2 border rounded-lg text-sm">Cancel</button>
                <button type="button" @click="deleteThread()"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm">Delete</button>
            </div>
        </div>
    </div>

    {{-- ============== ATTACHMENT PREVIEW MODAL ============== --}}
    <div x-show="showPreview"
         @keydown.escape.window="showPreview = false"
         class="fixed inset-0 bg-black/70 z-50 flex items-center justify-center p-6"
         style="display:none;">
        <div class="bg-white rounded-2xl shadow-lg w-full max-w-3xl max-h-[90vh] flex flex-col" @click.outside="showPreview = false">
            <div class="flex justify-between items-center px-4 py-3 border-b">
                <div class="min-w-0">
                    <p class="font-semibold text-sm truncate" x-text="previewMessage?.attachment?.name"></p>
                    <p class="text-[11px] text-gray-500">
                        Expires: <span x-text="previewMessage?.attachment?.expires_at"></span>
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <a :href="previewMessage?.attachment?.download"
                       class="px-3 py-1.5 bg-indigo-600 text-white rounded-lg text-xs hover:bg-indigo-700">
                        Download
                    </a>
                    <button type="button" @click="showPreview = false" class="text-gray-500 hover:text-gray-800">✕</button>
                </div>
            </div>
            <div class="flex-1 overflow-auto p-4 flex items-center justify-center bg-gray-50 relative">
                {{-- Loading overlay --}}
                <div x-show="previewLoading" class="absolute inset-0 flex flex-col items-center justify-center bg-white/80 z-10" style="display:none;">
                    <svg class="animate-spin w-8 h-8 text-indigo-600 mb-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                    <p class="text-xs text-gray-600">Preparing preview…</p>
                </div>

                <template x-if="previewMessage?.attachment?.is_image">
                    <img :src="previewMessage.attachment.url" :alt="previewMessage.attachment.name" class="max-w-full max-h-[70vh] object-contain" @load="previewLoading = false">
                </template>
                <template x-if="previewMessage?.attachment?.is_video">
                    <video :src="previewMessage.attachment.url" controls class="max-w-full max-h-[70vh]" @loadeddata="previewLoading = false"></video>
                </template>
                <template x-if="previewMessage && !previewMessage.attachment.is_image && !previewMessage.attachment.is_video">
                    <template x-if="previewMessage.attachment.preview_url">
                        <iframe :src="previewMessage.attachment.preview_url"
                                class="w-full h-[70vh] border-0"
                                :title="previewMessage.attachment.name"
                                @load="previewLoading = false"></iframe>
                    </template>
                </template>
                <template x-if="previewMessage && !previewMessage.attachment.is_image && !previewMessage.attachment.is_video && !previewMessage.attachment.preview_url">
                    <div class="flex flex-col items-center justify-center text-center py-16 text-gray-500">
                        <svg class="w-12 h-12 mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z"/></svg>
                        <p class="text-sm font-medium text-gray-700" x-text="previewMessage.attachment.name"></p>
                        <p class="text-xs mt-1">Preview not available for this file type.</p>
                        <p class="text-xs mt-0.5">Use the <strong>Download</strong> button to open it on your computer.</p>
                    </div>
                </template>
            </div>
        </div>
    </div>

</div>

<script>
function chatPage(initialThreads){
    return {
        threads: initialThreads || [],
        activeTab: 'all',
        userSearch: '',
        searchResults: [],
        activeThreadId: null,
        activeThread: null,
        messages: [],
        newMessage: '',
        showCreateModal: false,

        // Kebab menu / thread actions
        menuOpenId: null,
        showDeleteModal: false,
        deleteTarget: null,
        showMembersModal: false,
        membersMode: 'view', // 'view' | 'add' | 'remove'
        membersThread: null,
        members: [],
        memberSearch: '',
        memberCandidates: [],

        // Composer: upload + mic + preview
        uploadMenuOpen: false,
        pendingFile: null,
        isDictating: false,
        recognizer: null,
        showPreview: false,
        previewMessage: null,
        previewLoading: false,

        init(){
            // Auto-open thread from URL hash (e.g. after startDirect redirect).
            const hash = window.location.hash.match(/thread-(\d+)/);
            if(hash){
                const t = this.threads.find(t => t.id === Number(hash[1]));
                if(t) this.selectThread(t);
            }

            // Listen for bell-notification clicks targeting a thread
            window.addEventListener('open-chat-thread', (e) => {
                const id = Number(e.detail?.id);
                if(!id) return;
                const t = this.threads.find(t => t.id === id);
                if(t){
                    this.selectThread(t);
                } else {
                    // Thread not in current list; reload the page so it gets fetched
                    window.location.reload();
                }
            });

            if(window.lucide){ window.lucide.createIcons(); }
        },

        get filteredThreads(){
            if(this.activeTab === 'unread'){
                return this.threads.filter(t => t.unread_count > 0);
            }
            return this.threads;
        },

        openCreateGroup(){ this.showCreateModal = true; },

        async searchUsers(){
            const q = this.userSearch.trim();
            if(q.length === 0){ this.searchResults = []; return; }
            try {
                const res = await fetch(`{{ route('communication.chat.users.search') }}?q=${encodeURIComponent(q)}`, {
                    headers: { 'Accept': 'application/json' }
                });
                this.searchResults = await res.json();
            } catch(e){ this.searchResults = []; }
        },

        async startDirect(user){
            try {
                const res = await fetch(`{{ url('communication/chat/direct') }}/${user.id}`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    }
                });
                const data = await res.json();
                this.userSearch = '';
                this.searchResults = [];

                if(!data.thread) return;

                // Insert (or move to top) the thread in the left list, then select it.
                const existing = this.threads.find(t => t.id === data.thread.id);
                if(existing){
                    Object.assign(existing, data.thread);
                    this.threads = [existing, ...this.threads.filter(t => t.id !== existing.id)];
                } else {
                    this.threads = [data.thread, ...this.threads];
                }
                this.selectThread(data.thread);
            } catch(e){ console.error(e); }
        },

        async selectThread(t){
            this.activeThreadId = t.id;
            this.activeThread = t;
            this.messages = [];
            try {
                const res = await fetch(`{{ url('communication/chat') }}/${t.id}/messages`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                this.activeThread = { ...t, ...data.thread };
                this.messages = data.messages;
                t.unread_count = 0; // clear badge
                this.$nextTick(() => this.scrollToBottom());
            } catch(e){ console.error(e); }
        },

        async sendMessage(){
            const text = this.newMessage.trim();
            if(!this.activeThreadId) return;
            if(!text && !this.pendingFile) return;
            try {
                const fd = new FormData();
                if(text) fd.append('message', text);
                if(this.pendingFile) fd.append('attachment', this.pendingFile);

                const res = await fetch(`{{ url('communication/chat') }}/${this.activeThreadId}/message`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: fd,
                });
                const data = await res.json();
                if(data.message){
                    this.messages.push(data.message);
                    this.newMessage = '';
                    this.clearPendingFile();
                    this.$nextTick(() => this.scrollToBottom());
                } else if(data.errors){
                    alert(Object.values(data.errors).flat().join('\n'));
                }
            } catch(e){ console.error(e); }
        },

        // ================= COMPOSER ATTACHMENTS =================
        pickFile(kind){
            const input = this.$refs.fileInput;
            if(!input) return;
            const accepts = {
                document: '.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,text/plain,text/csv',
                image:    'image/*',
                video:    'video/*',
            };
            input.accept = accepts[kind] || '';
            input.value = '';
            input.click();
        },
        handleFileSelect(e){
            const f = e.target.files && e.target.files[0];
            if(!f) return;
            const MAX = 20 * 1024 * 1024;
            if(f.size > MAX){
                alert('File too large. Max 20MB.');
                return;
            }
            this.pendingFile = f;
        },
        clearPendingFile(){
            this.pendingFile = null;
            if(this.$refs.fileInput) this.$refs.fileInput.value = '';
        },
        formatBytes(b){
            if(!b && b !== 0) return '';
            if(b < 1024) return b + ' B';
            if(b < 1024*1024) return (b/1024).toFixed(1) + ' KB';
            return (b/1024/1024).toFixed(1) + ' MB';
        },

        // ================= PREVIEW =================
        openPreview(m){
            if(!m || !m.attachment || m.attachment.expired) return;
            // Tear down any previous iframe first so we don't see the old
            // PDF while the new one loads (important when switching files).
            this.previewMessage = null;
            this.previewLoading = !!(m.attachment.preview_url || (!m.attachment.is_image && !m.attachment.is_video));
            this.showPreview = true;
            this.$nextTick(() => { this.previewMessage = m; });
        },

        // ================= VOICE DICTATION (Web Speech API) =================
        toggleDictation(){
            const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
            if(!SR){
                alert('Voice input is not supported in this browser. Try Chrome or Edge.');
                return;
            }
            if(this.isDictating){
                if(this.recognizer) this.recognizer.stop();
                return;
            }
            const rec = new SR();
            rec.lang = navigator.language || 'en-US';
            rec.continuous = true;
            rec.interimResults = false;
            rec.onresult = (event) => {
                let finalText = '';
                for(let i = event.resultIndex; i < event.results.length; i++){
                    if(event.results[i].isFinal){
                        finalText += event.results[i][0].transcript;
                    }
                }
                if(finalText){
                    this.newMessage = (this.newMessage ? (this.newMessage.trimEnd() + ' ') : '') + finalText.trim();
                }
            };
            rec.onerror = (e) => {
                console.warn('Dictation error:', e.error);
                this.isDictating = false;
            };
            rec.onend = () => { this.isDictating = false; };
            this.recognizer = rec;
            try {
                rec.start();
                this.isDictating = true;
            } catch(e){
                console.warn(e);
            }
        },

        scrollToBottom(){
            const el = this.$refs.messageList;
            if(el) el.scrollTop = el.scrollHeight;
        },

        // Return the list of viewers whose "last seen" message is THIS one (messenger-style).
        seenByFor(idx){
            const m = this.messages[idx];
            if(!m || !m.seen_by || m.seen_by.length === 0) return [];
            return m.seen_by.filter(viewer => {
                // Show the avatar only on the latest message this viewer has seen.
                for(let j = idx + 1; j < this.messages.length; j++){
                    const later = this.messages[j];
                    if(later.seen_by && later.seen_by.some(s => s.id === viewer.id)){
                        return false;
                    }
                }
                return true;
            });
        },

        // ================= THREAD ACTIONS =================
        confirmDeleteThread(t){
            this.deleteTarget = t;
            this.showDeleteModal = true;
        },
        async deleteThread(){
            if(!this.deleteTarget) return;
            const id = this.deleteTarget.id;
            try {
                const res = await fetch(`{{ url('communication/chat') }}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    }
                });
                if(!res.ok){
                    const err = await res.json().catch(() => ({}));
                    alert(err.message || 'Unable to delete this conversation.');
                    return;
                }
                this.threads = this.threads.filter(t => t.id !== id);
                if(this.activeThreadId === id){
                    this.activeThreadId = null;
                    this.activeThread = null;
                    this.messages = [];
                }
                this.showDeleteModal = false;
                this.deleteTarget = null;
            } catch(e){ console.error(e); }
        },

        async loadMembers(t){
            const res = await fetch(`{{ url('communication/chat') }}/${t.id}/members`, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();
            this.members = data.members || [];
            this.membersThread = data.thread || null;
        },
        async openMembers(t){
            this.membersThread = t;
            this.membersMode = 'view';
            this.showMembersModal = true;
            await this.loadMembers(t);
        },
        async openAddMembers(t){
            this.membersThread = t;
            this.membersMode = 'add';
            this.memberSearch = '';
            this.memberCandidates = [];
            this.showMembersModal = true;
            await this.loadMembers(t);
        },
        async openRemoveMembers(t){
            this.membersThread = t;
            this.membersMode = 'remove';
            this.showMembersModal = true;
            await this.loadMembers(t);
        },
        async searchMemberCandidates(){
            const q = this.memberSearch.trim();
            if(q.length === 0){ this.memberCandidates = []; return; }
            try {
                const res = await fetch(`{{ route('communication.chat.users.search') }}?q=${encodeURIComponent(q)}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const found = await res.json();
                const existingIds = this.members.map(m => m.id);
                this.memberCandidates = found.filter(u => !existingIds.includes(u.id));
            } catch(e){ this.memberCandidates = []; }
        },
        async addMember(userId){
            if(!this.membersThread) return;
            try {
                const res = await fetch(`{{ url('communication/chat') }}/${this.membersThread.id}/members`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ user_ids: [userId] })
                });
                if(!res.ok){ alert('Unable to add member.'); return; }
                const data = await res.json();
                this.members = data.members || [];
                this.memberCandidates = this.memberCandidates.filter(u => u.id !== userId);
            } catch(e){ console.error(e); }
        },
        async removeMember(userId){
            if(!this.membersThread) return;
            if(!confirm('Remove this member from the group?')) return;
            try {
                const res = await fetch(`{{ url('communication/chat') }}/${this.membersThread.id}/members/${userId}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    }
                });
                if(!res.ok){
                    const err = await res.json().catch(() => ({}));
                    alert(err.message || 'Unable to remove member.');
                    return;
                }
                const data = await res.json();
                this.members = data.members || [];
            } catch(e){ console.error(e); }
        },
    };
}
</script>

@endsection

