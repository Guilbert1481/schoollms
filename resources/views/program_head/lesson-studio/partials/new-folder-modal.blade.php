{{-- Draggable modal for creating a folder at the current level. --}}
@php
    $title = [
        0 => 'Add Subject',
        1 => 'Add Topic',
        2 => 'Add Lesson Folder',
        3 => 'Add Competency',
    ][$level] ?? 'Add Folder';
@endphp

<x-modal.form id="phNewFolderModal" :title="$title" widthClass="w-[28rem]">
    <form method="POST" action="{{ route('program_head.lesson-studio.folder.store') }}" class="space-y-3">
        @csrf
        {{-- Carry the current parent context so the controller knows which folder to create. --}}
        @if($subjectModel) <input type="hidden" name="subject_id" value="{{ $subjectModel->id }}"> @endif
        @if($topicModel)   <input type="hidden" name="topic_id"   value="{{ $topicModel->id }}">   @endif
        @if($lessonModel)  <input type="hidden" name="lesson_id"  value="{{ $lessonModel->id }}">  @endif

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
                        <option value="{{ $cat }}" @selected($cat === 'major')>{{ $catLabels[$cat] ?? $cat }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                <textarea name="description" rows="3" maxlength="1000"
                          class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"></textarea>
            </div>
        @endif

        <button type="submit" class="hidden">Save</button>
    </form>
</x-modal.form>
