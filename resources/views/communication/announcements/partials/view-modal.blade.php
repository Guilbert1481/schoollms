<div id="viewAnnouncementModal"
     class="fixed inset-0 bg-black/40 z-50 items-center justify-center"
     style="display:none;">

    <div class="bg-white rounded-2xl shadow-lg w-full max-w-2xl p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Announcement Details</h3>
            <button type="button" onclick="closeViewModal()" class="text-gray-500 hover:text-gray-800">✕</button>
        </div>

        <div class="space-y-3 text-sm text-gray-800">
            <div>
                <span class="text-xs uppercase tracking-wide text-gray-500">Title</span>
                <p id="viewTitle" class="font-semibold text-base"></p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <span class="text-xs uppercase tracking-wide text-gray-500">Type</span>
                    <p id="viewType" class="capitalize"></p>
                </div>
                <div>
                    <span class="text-xs uppercase tracking-wide text-gray-500">Priority</span>
                    <p id="viewPriority" class="capitalize"></p>
                </div>
                <div>
                    <span class="text-xs uppercase tracking-wide text-gray-500">Published</span>
                    <p id="viewPublished"></p>
                </div>
                <div>
                    <span class="text-xs uppercase tracking-wide text-gray-500">Expires</span>
                    <p id="viewExpires"></p>
                </div>
                <div>
                    <span class="text-xs uppercase tracking-wide text-gray-500">Created By</span>
                    <p id="viewCreator"></p>
                </div>
                <div>
                    <span class="text-xs uppercase tracking-wide text-gray-500">Status</span>
                    <p id="viewStatus" class="capitalize"></p>
                </div>
            </div>

            <div>
                <span class="text-xs uppercase tracking-wide text-gray-500">Content</span>
                <div id="viewContent" class="mt-1 p-3 bg-gray-50 border rounded-lg whitespace-pre-wrap"></div>
            </div>
        </div>

        <div class="flex justify-end mt-5">
            <button type="button" onclick="closeViewModal()"
                    class="px-4 py-2 border rounded-lg">Close</button>
        </div>
    </div>
</div>