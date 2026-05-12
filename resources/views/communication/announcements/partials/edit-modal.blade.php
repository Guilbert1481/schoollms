<div id="editAnnouncementModal"
     class="fixed inset-0 bg-black/40 z-50 items-center justify-center overflow-y-auto"
     style="display:none;">

    <div class="bg-white rounded-2xl shadow-lg w-full max-w-3xl p-6 my-8">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Edit Announcement</h3>
            <button type="button" onclick="closeEditModal()" class="text-gray-500 hover:text-gray-800">✕</button>
        </div>

        <form method="POST" id="editAnnouncementForm">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label class="text-sm">Title</label>
                    <input type="text" name="title" id="editTitle"
                           class="w-full border rounded-lg px-3 py-2" required>
                </div>

                <div>
                    <label class="text-sm">Content</label>
                    <textarea name="content" id="editContent"
                              class="w-full border rounded-lg px-3 py-2" rows="4" required></textarea>
                </div>

                <div>
                    <label class="text-sm">Announcement Type</label>
                    <select name="announcement_type" id="editType"
                            class="w-full border rounded-lg px-3 py-2">
                        <option value="announcement">Announcement</option>
                        <option value="training">Training</option>
                        <option value="academic">Academic</option>
                        <option value="event">Event</option>
                        <option value="finance">Finance</option>
                        <option value="hr">HR</option>
                        <option value="system">System</option>
                    </select>
                </div>

                <div class="flex items-start gap-4" data-role="priority-toggle">
                    <div>
                        <label class="text-sm font-medium block mb-1">Priority</label>
                        <div class="inline-flex rounded-lg border overflow-hidden" data-role="priority-buttons">
                            <button type="button"
                                    data-priority="regular"
                                    class="px-4 py-2 text-sm bg-indigo-600 text-white"
                                    data-on-class="bg-indigo-600 text-white"
                                    data-off-class="bg-white text-gray-700 hover:bg-gray-50">
                                Regular
                            </button>
                            <button type="button"
                                    data-priority="super"
                                    class="px-4 py-2 text-sm bg-white text-gray-700 hover:bg-gray-50 border-l"
                                    data-on-class="bg-red-600 text-white"
                                    data-off-class="bg-white text-gray-700 hover:bg-gray-50">
                                Super Priority
                            </button>
                        </div>
                        <input type="hidden" name="priority_level" id="editPriority" value="normal" data-role="priority-input">
                    </div>

                    <div data-role="super-duration" style="display:none;">
                        <label class="text-sm font-medium block mb-1">Expires in (minutes)</label>
                        <input type="number" name="super_priority_minutes" id="editSuperMinutes"
                               value="60" min="1" max="1440" step="1"
                               class="w-32 border rounded-lg px-3 py-2">
                        <p class="text-xs text-gray-500 mt-1">Auto-expires when acknowledged or after this window.</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4" data-role="regular-dates">
                    <div>
                        <label class="text-sm">Publish At</label>
                        <input type="datetime-local" name="published_at" id="editPublishedAt"
                               class="w-full border rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="text-sm">Expires At</label>
                        <input type="datetime-local" name="expires_at" id="editExpiresAt"
                               class="w-full border rounded-lg px-3 py-2">
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-4">
                <button type="button" onclick="closeEditModal()"
                        class="px-4 py-2 border rounded-lg">Cancel</button>

                <button type="submit"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg">Update</button>
            </div>
        </form>
    </div>
</div>