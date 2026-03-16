@extends('communication.layout')
@section('communication-content')

<div class="flex justify-between items-center mb-6">
    <h2 class="text-xl font-semibold text-gray-800">
        Announcements
    </h2>

    <a href="{{ route('communication.announcements.create') }}"
       class="inline-flex items-center gap-2 bg-gray-900 text-white px-4 py-2 rounded-xl text-sm hover:bg-gray-800 transition">
        <i data-lucide="plus" class="w-4 h-4"></i>
        New Announcement
    </a>
</div>

<div class="space-y-5">

    @forelse($announcements as $announcement)
        <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-6 hover:shadow-md transition">

            <h3 class="font-semibold text-gray-900 text-lg">
                {{ $announcement->title }}
            </h3>

            <p class="text-sm text-gray-500 mt-1">
                {{ $announcement->created_at->format('M d, Y h:i A') }}
            </p>

            <div class="mt-4 text-gray-700">
                {{ \Illuminate\Support\Str::limit(strip_tags($announcement->content), 200) }}
            </div>

            <div class="mt-4">
                <a href="{{ route('communication.announcements.show', $announcement) }}"
                   class="text-sm font-medium text-gray-900 hover:underline">
                    View Details
                </a>
            </div>

        </div>
    @empty
        <div class="bg-white rounded-2xl border border-gray-100 p-8 text-center text-gray-500">
            No announcements available.
        </div>
    @endforelse

</div>

<div class="mt-8">
    {{ $announcements->links() }}
</div>

@endsection
