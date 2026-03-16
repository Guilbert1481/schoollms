@extends('communication.layout')

@section('communication-content')

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    <!-- Latest Announcements -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Latest Announcements
        </h3>

        @forelse($latestAnnouncements as $announcement)
            <div class="mb-3">
                <p class="font-medium text-gray-800">
                    {{ $announcement->title }}
                </p>
                <p class="text-sm text-gray-500">
                    {{ $announcement->created_at->format('M d, Y') }}
                </p>
            </div>
        @empty
            <p class="text-sm text-gray-500">
                No announcements available.
            </p>
        @endforelse
    </div>

    <!-- Upcoming Deadlines -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Upcoming Deadlines
        </h3>

        @forelse($upcomingDeadlines as $deadline)
            <div class="mb-3">
                <p class="font-medium text-gray-800">
                    {{ $deadline->title }}
                </p>
                <p class="text-sm text-gray-500">
                    Due {{ \Carbon\Carbon::parse($deadline->due_date)->format('M d, Y') }}
                </p>
            </div>
        @empty
            <p class="text-sm text-gray-500">
                No upcoming deadlines.
            </p>
        @endforelse
    </div>

    <!-- Overdue Deadlines -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h3 class="text-lg font-semibold text-red-600 mb-4">
            Overdue Deadlines
        </h3>

        @forelse($overdueDeadlines as $deadline)
            <div class="mb-3">
                <p class="font-medium text-gray-800">
                    {{ $deadline->title }}
                </p>
                <p class="text-sm text-red-500">
                    Overdue
                </p>
            </div>
        @empty
            <p class="text-sm text-gray-500">
                No overdue deadlines.
            </p>
        @endforelse
    </div>

    <!-- Recent Chats -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Recent Chats
        </h3>

        @forelse($chatThreads as $thread)
            <div class="mb-3">
                <p class="font-medium text-gray-800">
                    {{ $thread->name ?? 'Conversation' }}
                </p>
                <p class="text-sm text-gray-500">
                    Updated {{ $thread->updated_at->diffForHumans() }}
                </p>
            </div>
        @empty
            <p class="text-sm text-gray-500">
                No recent conversations.
            </p>
        @endforelse
    </div>

</div>

@endsection
