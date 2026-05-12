@extends('layouts.app')

@section('content')
@php
    $currency = \App\Helpers\CurrencyHelper::forCurrentSchool();
@endphp

<div class="w-full space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Available Courses</h1>
            <p class="text-sm text-slate-500">
                Browse the courses, seminars, trainings, and review classes currently offered.
            </p>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if($sessions->isEmpty())
        <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-slate-500">
            No courses are currently available. Please check back soon.
        </div>
    @else
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($sessions as $s)
                @php
                    $cover = $s->cover_image
                        ? asset('storage/' . $s->cover_image)
                        : 'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?auto=format&fit=crop&w=900&q=80';

                    $instructor = trim(($s->instructor_title ?? '') . ' ' . ($s->instructor_name ?? ''));
                    $courseName = $s->title ?: $s->name;

                    $currencySymbol = $s->currency
                        ? (\App\Helpers\CurrencyHelper::fromCountry(null)['symbol'] ?: '')
                        : $currency['symbol'];
                    // Use saved currency code, fall back to school currency symbol
                    $currencyCode = $s->currency ?: $currency['code'];
                @endphp

                <article class="rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-sm hover:shadow-lg transition-all flex flex-col">
                    <img src="{{ $cover }}"
                         alt="{{ $courseName }}"
                         class="h-44 w-full object-cover">

                    <div class="p-5 flex flex-col flex-1">
                        <h3 class="text-slate-900 font-semibold text-lg line-clamp-2">
                            {{ $courseName }}
                        </h3>

                        @if($instructor !== '')
                            <p class="text-sm text-slate-600 mt-1">{{ $instructor }}</p>
                        @endif

                        @if($s->enrollmentType)
                            <p class="text-xs text-indigo-600 font-semibold mt-2">
                                {{ $s->enrollmentType->name }}
                            </p>
                        @endif

                        @if($s->course_details)
                            <p class="mt-3 text-sm text-slate-600 line-clamp-3">
                                {{ $s->course_details }}
                            </p>
                        @endif

                        <div class="mt-3 flex items-center justify-between text-xs text-slate-500">
                            <span>
                                {{ \Carbon\Carbon::parse($s->start_date)->format('M d, Y') }}
                                –
                                {{ \Carbon\Carbon::parse($s->end_date)->format('M d, Y') }}
                            </span>
                        </div>

                        <div class="mt-4 flex items-center gap-2">
                            @if(!is_null($s->price) && (float) $s->price > 0)
                                <span class="text-xl font-bold text-slate-900">
                                    {{ $currencyCode }} {{ number_format((float) $s->price, 2) }}
                                </span>
                            @else
                                <span class="text-xl font-bold text-emerald-700">Free</span>
                            @endif
                        </div>

                        <div class="mt-auto pt-4 flex items-center gap-2">
                            @php
                                $detailPayload = [
                                    'title' => $courseName,
                                    'cover' => $cover,
                                    'instructor' => $instructor,
                                    'type' => optional($s->enrollmentType)->name,
                                    'details' => $s->course_details,
                                    'price' => $s->price,
                                    'currency' => $currencyCode,
                                    'start' => \Carbon\Carbon::parse($s->start_date)->format('M d, Y'),
                                    'end' => \Carbon\Carbon::parse($s->end_date)->format('M d, Y'),
                                ];
                                $programsUrl = route('training.trainee.available-courses.programs', ['session' => $s->id]);
                            @endphp
                            <a href="{{ $programsUrl }}"
                               class="flex-1 text-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                                Enroll
                            </a>
                            <button type="button"
                                    onclick='openAvailableCourseModal(@json($detailPayload), @json($programsUrl))'
                                    class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                                Details
                            </button>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>

{{-- Details Modal --}}
<div id="availableCourseModal"
     class="hidden fixed inset-0 z-[100] bg-slate-900/50 backdrop-blur-sm p-4">
    <div class="mx-auto mt-12 max-w-3xl rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden">
        <div class="grid md:grid-cols-2">
            <img id="acmImage" src="" alt="" class="h-56 md:h-full w-full object-cover">
            <div class="p-6">
                <div class="flex items-start justify-between gap-3">
                    <h2 id="acmTitle" class="text-2xl font-bold text-slate-900"></h2>
                    <button type="button" onclick="closeAvailableCourseModal()"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-300 text-slate-600 hover:bg-slate-100">
                        &times;
                    </button>
                </div>
                <p id="acmInstructor" class="text-sm text-slate-600 mt-2"></p>
                <p id="acmType" class="text-xs text-indigo-600 font-semibold mt-1"></p>
                <p id="acmDates" class="text-xs text-slate-500 mt-1"></p>
                <p id="acmDetails" class="text-slate-700 mt-4 leading-relaxed whitespace-pre-line"></p>
                <div class="mt-6 flex items-center gap-2">
                    <span id="acmPrice" class="text-2xl font-bold text-slate-900"></span>
                </div>
                <a id="acmEnrollLink" href="#"
                   class="mt-5 inline-block rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">
                    Enroll Now
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function openAvailableCourseModal(data, enrollUrl) {
    document.getElementById('acmImage').src = data.cover || '';
    document.getElementById('acmImage').alt = data.title || '';
    document.getElementById('acmTitle').textContent = data.title || '';
    document.getElementById('acmInstructor').textContent = data.instructor || '';
    document.getElementById('acmType').textContent = data.type || '';
    document.getElementById('acmDates').textContent =
        (data.start && data.end) ? (data.start + ' – ' + data.end) : '';
    document.getElementById('acmDetails').textContent = data.details || 'No details provided.';

    const link = document.getElementById('acmEnrollLink');
    if (link) link.href = enrollUrl || '#';

    const priceEl = document.getElementById('acmPrice');
    if (data.price && Number(data.price) > 0) {
        priceEl.textContent = (data.currency || '') + ' ' +
            Number(data.price).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        priceEl.classList.remove('text-emerald-700');
        priceEl.classList.add('text-slate-900');
    } else {
        priceEl.textContent = 'Free';
        priceEl.classList.remove('text-slate-900');
        priceEl.classList.add('text-emerald-700');
    }

    document.getElementById('availableCourseModal').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function closeAvailableCourseModal() {
    document.getElementById('availableCourseModal').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
}

document.getElementById('availableCourseModal').addEventListener('click', function (e) {
    if (e.target === this) closeAvailableCourseModal();
});
</script>
@endsection
