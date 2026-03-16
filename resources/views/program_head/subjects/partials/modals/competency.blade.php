<div x-show="competencyModal" x-cloak style="display:none;" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-[70]">
        <div class="bg-white rounded shadow-xl w-full max-w-lg p-6" @click.away="competencyModal = false">
            <h3 class="text-lg font-bold mb-4">Manage Competencies - <span x-text="currentSubjectCode"></span></h3>
            <select x-model="selectedLessonId" class="w-full border rounded px-3 py-2 mb-3">
                <option value="">Select Lesson</option>
                <template x-for="l in availableLessons" :key="l.id">
                    <option :value="l.id" x-text="l.name"></option>
                </template>
            </select>
            <div class="max-h-64 overflow-y-auto border rounded mb-4 p-2 bg-gray-50">
                <template x-for="(comp, idx) in competenciesBuffer" :key="idx">
                    <div class="flex items-center space-x-3 p-2 border-b last:border-0">
                        <input type="checkbox" :id="'comp-'+idx" x-model="comp.checked" class="rounded text-blue-600">
                        <label :for="'comp-'+idx" x-text="comp.name" class="text-sm text-gray-700 cursor-pointer flex-1"></label>
                    </div>
                </template>
            </div>
            <div class="flex space-x-2 mb-4">
                <input type="text" x-model="newCompetencyName" @keydown.enter.prevent="addCompetency()" class="w-full border rounded px-3 py-2 text-sm" placeholder="Enter competency name">
                <button @click="addCompetency()" class="bg-blue-600 text-white px-4 rounded text-sm">Add</button>
            </div>
            <div class="mt-4 text-right space-x-2">
                <button @click="competencyModal = false" class="px-4 py-2 bg-gray-200 rounded">Cancel</button>
                <button @click="saveCompetencies()" class="px-4 py-2 bg-green-600 text-white rounded">Save</button>
            </div>
        </div>
    </div>
