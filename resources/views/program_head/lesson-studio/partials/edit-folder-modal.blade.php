{{-- Draggable modal for editing the folder at the current level. --}}
@php
    $editTitle = [
        0 => 'Edit Subject',
        1 => 'Edit Topic',
        2 => 'Edit Lesson Folder',
        3 => 'Edit Competency',
    ][$level] ?? 'Edit Folder';
@endphp

<x-modal.form id="phEditFolderModal" :title="$editTitle" widthClass="w-[28rem]">
    {{-- Action gets set by JS to /lesson-studio/folder/{type}/{id} before submit. --}}
    <form id="phEditFolderForm" method="POST" action="" class="space-y-3">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">
                @switch($level)
                    @case(0) Subject Name @break
                    @case(1) Topic Name @break
                    @case(2) Lesson Name @break
                    @default Competency Name
                @endswitch
            </label>
            <input type="text" name="name" required maxlength="255"
                   class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
        </div>

        @if($level === 0)
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Code</label>
                <input type="text" name="code" required maxlength="32"
                       class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Category</label>
                <select name="category" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white">
                    @foreach($categories as $cat)
                        @php
                            $catLabels = [
                                'gen_ed' => 'Gen Ed', 'prof_ed' => 'Prof Ed', 'major' => 'Major',
                                'pe' => 'PE', 'nstp' => 'NSTP', 'internship' => 'Internship',
                            ];
                        @endphp
                        <option value="{{ $cat }}">{{ $catLabels[$cat] ?? $cat }}</option>
                    @endforeach
                </select>
            </div>

            <p class="text-[11px] text-slate-500 italic">
                Subject status (Active/Inactive) is managed automatically by the Admissions enrollment session and cannot be changed here.
            </p>
        @endif

        @if($level !== 3 || true)
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                <textarea name="description" rows="3" maxlength="1000"
                          class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"></textarea>
            </div>
        @endif

        <button type="submit" class="hidden">Save</button>
    </form>
</x-modal.form>
