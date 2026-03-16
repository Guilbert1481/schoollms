<div x-show="lessonModal" x-cloak style="display:none;" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-[60]">
        <div class="bg-white rounded shadow-xl w-full max-w-lg p-6" @click.away="lessonModal = false">
            <h3 class="text-lg font-bold mb-4">Manage Lessons - <span x-text="currentSubjectCode"></span></h3>
            <select x-model="selectedTopicId" class="w-full border rounded px-3 py-2 mb-3">
                <option value="">Select Topic</option>
                <template x-for="t in availableTopics" :key="t.id">
                    <option :value="t.id" x-text="t.name"></option>
                </template>
            </select>
            <div class="max-h-64 overflow-y-auto border rounded mb-4 p-2 bg-gray-50">
                <template x-for="(lesson, idx) in lessonsBuffer" :key="idx">
                    <div class="flex items-center space-x-3 p-2 border-b last:border-0">
                        <input type="checkbox" :id="'lesson-'+idx" x-model="lesson.checked" class="rounded text-blue-600">
                        <label :for="'lesson-'+idx" x-text="lesson.name" class="text-sm text-gray-700 cursor-pointer flex-1"></label>
                    </div>
                </template>
            </div>
            <div class="flex space-x-2 mb-4">
                <input type="text" x-model="newLessonName" @keydown.enter.prevent="addLesson()" class="w-full border rounded px-3 py-2 text-sm" placeholder="Enter lesson name">
                <button @click="addLesson()" class="bg-blue-600 text-white px-4 rounded text-sm">Add</button>
            </div>
            <div class="mt-4 text-right space-x-2">
                <button @click="lessonModal = false" class="px-4 py-2 bg-gray-200 rounded">Cancel</button>
                <button @click="saveLessons()" class="px-4 py-2 bg-green-600 text-white rounded">Save</button>
            </div>
        </div>
    </div>