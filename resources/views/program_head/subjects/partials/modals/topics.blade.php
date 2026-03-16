<div x-show="topicModal" x-cloak style="display: none;" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
        <div class="bg-white rounded shadow-xl w-full max-w-lg p-6" @click.away="closeTopicModal()">
            <h3 class="text-lg font-bold mb-4">Manage Topics - <span x-text="currentSubjectCode"></span></h3>
            <div class="max-h-60 overflow-y-auto mb-4">
                <template x-for="(topic, index) in topicsBuffer" :key="index">
                    <div class="flex justify-between items-center bg-gray-100 px-3 py-2 rounded mb-2">
                        <span x-text="topic"></span>
                        <button type="button" @click="topicsBuffer.splice(index,1)" class="text-red-600 text-sm hover:font-bold">✕</button>
                    </div>
                </template>
            </div>
            <div class="flex space-x-2">
                <input type="text" x-model="newTopicName" @keydown.enter.prevent="addTopic()" class="w-full border rounded px-3 py-2 outline-none" placeholder="Enter topic name">
                <button type="button" @click="addTopic()" class="bg-blue-600 text-white px-4 rounded hover:bg-blue-700">Add</button>
            </div>
            <div class="mt-6 text-right space-x-2">
                <button type="button" @click="closeTopicModal()" class="px-4 py-2 bg-gray-200 rounded">Cancel</button>
                <button type="button" @click="saveTopics()" :disabled="topicsBuffer.length === 0" class="px-4 py-2 bg-green-600 text-white rounded">Save</button>
            </div>
        </div>
    </div>
