<div id="createAnnouncementModal"
     class="fixed inset-0 bg-black/40 z-50 items-center justify-center overflow-y-auto"
     style="display:none;">

    <div class="bg-white rounded-2xl shadow-lg w-full max-w-3xl p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">New Announcement</h3>
            <button onclick="closeCreateModal()">✕</button>
        </div>

        <form action="{{ route('communication.announcements.store') }}" method="POST">
            @csrf

            @include('communication.announcements.partials.form')

            <div class="flex justify-end gap-2 mt-4">
                <button type="button"
                        onclick="closeCreateModal()"
                        class="px-4 py-2 border rounded-lg">
                    Cancel
                </button>

                <button type="submit"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg">
                    Publish
                </button>
            </div>
        </form>
    </div>
</div>