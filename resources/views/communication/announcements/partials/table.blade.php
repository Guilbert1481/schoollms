<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50">
            <tr class="text-left text-gray-600">
                <th class="p-4">Title</th>
                <th class="p-4">Type</th>
                <th class="p-4">Priority</th>
                <th class="p-4">Created By</th>
                <th class="p-4">Sent To</th>
                <th class="p-4">Acknowledged</th>
                <th class="p-4">Status</th>
                <th class="p-4">Action</th>
            </tr>
        </thead>

        <tbody>
            @forelse($announcements as $announcement)
            <tr class="border-t">
                <td class="p-4 font-medium">
                    {{ $announcement->title }}
                </td>

                <td class="p-4">
                    {{ ucfirst($announcement->announcement_type) }}
                </td>

                <td class="p-4">
                    @if($announcement->priority_level == 'super')
                        <span class="text-red-600 font-semibold">Priority</span>
                    @else
                        Normal
                    @endif
                </td>

                <td class="p-4">
                    {{ $announcement->creator->full_name ?? '-' }}
                </td>

                <td class="p-4">
                    {{ $announcement->sent_count ?? 0 }}
                </td>

                <td class="p-4">
                    {{ $announcement->ack_count ?? 0 }}
                </td>

                <td class="p-4">
                    <span class="px-2 py-1 rounded-lg bg-green-100 text-green-700 text-xs">
                        {{ $announcement->status }}
                    </span>
                </td>

                <td class="p-4 flex gap-2">
                    <button type="button"
                            class="announcement-view-btn text-blue-600"
                            data-id="{{ $announcement->id }}"
                            data-title="{{ $announcement->title }}"
                            data-content="{{ $announcement->content }}"
                            data-type="{{ $announcement->announcement_type }}"
                            data-priority="{{ $announcement->priority_level }}"
                            data-published="{{ $announcement->published_at }}"
                            data-expires="{{ $announcement->expires_at }}"
                            data-creator="{{ $announcement->creator->full_name ?? '-' }}"
                            data-status="{{ $announcement->status }}">
                        View
                    </button>

                    <button type="button"
                            class="announcement-edit-btn text-yellow-600"
                            data-id="{{ $announcement->id }}"
                            data-title="{{ $announcement->title }}"
                            data-content="{{ $announcement->content }}"
                            data-type="{{ $announcement->announcement_type }}"
                            data-priority="{{ $announcement->priority_level }}"
                            data-published="{{ optional($announcement->published_at)->format('Y-m-d\TH:i') }}"
                            data-expires="{{ optional($announcement->expires_at)->format('Y-m-d\TH:i') }}"
                            data-super-minutes="{{ $announcement->super_priority_expires_at ? max(1, (int) ceil(now()->diffInSeconds($announcement->super_priority_expires_at, false) / 60)) : 60 }}"
                            data-url="{{ route('communication.announcements.update', $announcement->id) }}">
                        Edit
                    </button>

                    <form method="POST"
                          action="{{ route('communication.announcements.destroy', $announcement->id) }}"
                          onsubmit="return confirm('Delete this announcement?');">
                        @csrf
                        @method('DELETE')
                        <button class="text-red-600">Delete</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center p-6 text-gray-500">
                    No announcements found.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6">
    {{ $announcements->links() }}
</div>