<div class="grid grid-cols-1 gap-4">

    <div>
        <label class="text-sm">Title</label>
        <input type="text" name="title"
               class="w-full border rounded-lg px-3 py-2" required>
    </div>

    <div>
        <label class="text-sm">Content</label>
        <textarea name="content"
                  class="w-full border rounded-lg px-3 py-2"
                  rows="4" required></textarea>
    </div>

    <div>
        <label class="text-sm">Announcement Type</label>
        <select name="announcement_type"
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
            <input type="hidden" name="priority_level" value="normal" data-role="priority-input">
        </div>

        <div data-role="super-duration" style="display:none;">
            <label class="text-sm font-medium block mb-1">Expires in (minutes)</label>
            <input type="number" name="super_priority_minutes" value="60" min="1" max="1440" step="1"
                   class="w-32 border rounded-lg px-3 py-2">
            <p class="text-xs text-gray-500 mt-1">Auto-expires when acknowledged or after this window.</p>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4" data-role="regular-dates">
        <div>
            <label class="text-sm">Publish At</label>
            <input type="datetime-local" name="published_at"
                   class="w-full border rounded-lg px-3 py-2">
        </div>

        <div>
            <label class="text-sm">Expires At</label>
            <input type="datetime-local" name="expires_at"
                   class="w-full border rounded-lg px-3 py-2">
        </div>
    </div>

    <div class="grid grid-cols-3 gap-6">

    <!-- Offices -->
    <div>
        <label class="text-sm font-medium mb-2 block">Offices</label>
        <div class="border rounded-lg p-3 h-40 overflow-y-auto bg-gray-50">
            @foreach($offices as $office)
                <label class="flex items-center gap-2 mb-1">
                    <input type="checkbox"
                           name="offices[]"
                           value="{{ $office->id }}">
                    <span>{{ $office->name }}</span>
                </label>
            @endforeach
        </div>
    </div>

    <!-- Programs -->
    <div>
        <label class="text-sm font-medium mb-2 block">Programs</label>
        <div class="border rounded-lg p-3 h-40 overflow-y-auto bg-gray-50">
            @foreach($programs as $program)
                <label class="flex items-center gap-2 mb-1">
                    <input type="checkbox"
                           name="programs[]"
                           value="{{ $program->id }}">
                    <span>{{ $program->code }}</span>
                </label>
            @endforeach
        </div>
    </div>

    <!-- Groups -->
    <div>
        <label class="text-sm font-medium mb-2 block">Groups</label>
        <div class="border rounded-lg p-3 h-40 overflow-y-auto bg-gray-50">
            @foreach($groups as $group)
                <label class="flex items-center gap-2 mb-1">
                    <input type="checkbox"
                           name="groups[]"
                           value="{{ $group->id }}">
                    <span>{{ $group->name }}</span>
                </label>
            @endforeach
        </div>
    </div>

</div>

</div>