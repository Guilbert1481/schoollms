@extends('layouts.app')

@section('content')
<div class="p-6">

    @if(session('success'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-2 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-2 text-sm">
            <div class="font-bold mb-1">Please fix the following:</div>
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-3 gap-6">

        {{-- LEFT TABLE --}}
        <section class="col-span-1 rounded-2xl border bg-white p-6">
            <h2 class="text-sm font-extrabold mb-4">
                Upcoming Session
            </h2>

            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-500">
                        <th class="py-2 pr-4">Name</th>
                        <th class="py-2 pr-4">Start</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($upcomingTerms as $term)
                    <tr class="border-t">
                        <td class="py-2">
                            <button
                                onclick="openSessionFromTerm(
                                    {{ $term->id }},
                                    {{ $term->academic_year_id }},
                                    @js($term->name),
                                    @js($term->start_date),
                                    @js($term->end_date),
                                    @js($term->enrollment_type)
                                )"
                                class="text-indigo-600 font-bold hover:underline">
                                {{ $term->name }}
                            </button>
                        </td>
                        <td>{{ \Carbon\Carbon::parse($term->start_date)->format('Y-m-d') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </section>


        {{-- RIGHT TABLE --}}
        <section class="col-span-2 rounded-2xl border bg-white p-6">
            <h2 class="text-sm font-extrabold mb-4">
                Enrollment Settings
            </h2>

            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-500">
                        <th class="py-2 pr-4">Session Title</th>
                        <th class="py-2 pr-4">Name</th>
                        <th class="py-2 pr-4">Start</th>
                        <th class="py-2 pr-4">End</th>
                        <th class="py-2 pr-4">Status</th>
                        <th class="py-2 pr-4">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($settings as $s)
                <tr>
                    <td class="py-3 pr-4 font-semibold">{{ $s->title }}</td>
                    <td class="py-3 pr-4">{{ $s->name }}</td>
                    <td class="py-3 pr-4">{{ $s->start_date }}</td>
                    <td class="py-3 pr-4">{{ $s->end_date }}</td>

                    <td class="py-3 pr-4">
                        @if($s->status === 'Active')
                            <span class="inline-flex items-center rounded-full bg-emerald-100 text-emerald-800 px-2 py-1 text-xs font-extrabold">
                                Active
                            </span>
                        @elseif($s->status === 'Upcoming')
                            <span class="inline-flex items-center rounded-full bg-amber-100 text-amber-800 px-2 py-1 text-xs font-extrabold">
                                Upcoming
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-slate-200 text-slate-800 px-2 py-1 text-xs font-extrabold">
                                Closed
                            </span>
                        @endif
                    </td>

                    <td class="py-3 pr-4 align-middle">
                        <div class="flex items-center gap-2">

                            {{-- Edit --}}
                            @php
                                $editPayload = [
                                    'id' => $s->id,
                                    'title' => $s->title,
                                    'start_date' => $s->start_date,
                                    'end_date' => $s->end_date,
                                    'price' => $s->price,
                                    'instructor_title' => $s->instructor_title,
                                    'instructor_name' => $s->instructor_name,
                                    'course_details' => $s->course_details,
                                    'cover_image' => $s->cover_image ? asset('storage/' . $s->cover_image) : null,
                                ];
                            @endphp
                            <button
                                onclick='openEditSessionModal(@json($editPayload))'
                                class="h-8 px-3 rounded-lg text-xs font-extrabold border border-indigo-200 bg-indigo-50 text-indigo-800 flex items-center">
                                Edit
                            </button>

                            {{-- Delete --}}
                            <form method="POST"
                                action="{{ route('admission.enrollment-settings.delete', $s->id) }}"
                                class="m-0 p-0 flex items-center">
                                @csrf
                                @method('DELETE')
                                <button
                                    class="h-8 px-3 rounded-lg text-xs font-extrabold border border-red-200 bg-red-50 text-red-800 flex items-center">
                                    Delete
                                </button>
                            </form>

                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-8 text-center text-slate-500">
                        No enrollment sessions found.
                    </td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </section>

    </div>

</div>

@include('admission.settings.partials.create_session_from_term_modal')
@include('admission.settings.partials.edit_session_modal')

@endsection

<script src="{{ asset('js/staff/admissions/enrollment-settings.js') }}?v={{ time() }}"></script>