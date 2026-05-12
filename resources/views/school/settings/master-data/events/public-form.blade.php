<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $event->event_name }} - Event Form</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-slate-100 p-4 md:p-8">
    <div class="mx-auto w-full max-w-2xl">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="bg-indigo-600 px-6 py-5 text-white">
                <h1 class="text-xl font-bold">{{ $event->event_name }}</h1>
                <p class="mt-1 text-sm text-indigo-100">
                    {{ ucfirst($event->event_type ?? 'other') }}
                </p>
            </div>

            <div class="px-6 py-5">
                @if(session('success'))
                    <div class="mb-4 rounded-lg bg-emerald-100 px-3 py-2 text-sm text-emerald-800">
                        {{ session('success') }}
                    </div>
                @endif

                @if(data_get($event->metadata, 'description'))
                    <p class="mb-4 text-sm text-slate-600">{{ data_get($event->metadata, 'description') }}</p>
                @endif

                <div class="mb-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                    <p class="font-semibold text-slate-800">Auto-filled Profile Details</p>
                    <p class="mt-1">Name: {{ $prefill['recipient_name'] ?: '-' }}</p>
                    <p>Email: {{ $prefill['email'] ?: '-' }}</p>
                    <p>ID: {{ $prefill['id_number'] ?: '-' }}</p>
                    <p>Organization: {{ $prefill['organization_name'] ?: '-' }}</p>
                </div>

                <form method="POST" action="{{ route('school.settings.master-data.events.public.submit', ['event' => $event->id, 'signature' => request('signature')]) }}" class="space-y-4">
                    @csrf

                    <input type="hidden" name="recipient_name" value="{{ $prefill['recipient_name'] }}">
                    <input type="hidden" name="email" value="{{ $prefill['email'] }}">
                    <input type="hidden" name="id_number" value="{{ $prefill['id_number'] }}">
                    <input type="hidden" name="organization_name" value="{{ $prefill['organization_name'] }}">

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Event Category / Activity</label>
                        <select name="selected_event_type" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                            <option value="">Select category/activity</option>
                            @foreach($eventTypeOptions as $eventType)
                                <option value="{{ $eventType }}" @selected(old('selected_event_type') === $eventType)>{{ $eventType }}</option>
                            @endforeach
                        </select>
                        @error('selected_event_type')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Role in This Event</label>
                        <select name="selected_role_type" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                            <option value="">Select role</option>
                            @foreach($roleTypeOptions as $roleType)
                                <option value="{{ $roleType }}" @selected(old('selected_role_type') === $roleType)>{{ $roleType }}</option>
                            @endforeach
                        </select>
                        @error('selected_role_type')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="w-full rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Submit Form</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
