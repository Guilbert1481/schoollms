{{-- Rename modal for the folder level currently on screen (Topic @ L1, Lesson @ L2).
     The form action is set by lsFolderEdit() before the modal opens. --}}
@php
    $folderNoun = ['topic' => 'Topic', 'lesson' => 'Lesson'][$folderType] ?? 'Folder';
@endphp

<x-modal.form id="lsEditFolderModal" :title="'Rename '.$folderNoun" widthClass="w-[26rem]">
    <form id="lsEditFolderForm" method="POST" action="" class="space-y-3">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">{{ $folderNoun }} Name</label>
            <input type="text" name="name" required maxlength="255"
                   class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
        </div>

        <button type="submit" class="hidden">Save</button>
    </form>
</x-modal.form>
