@extends('layouts.app')

@section('content')
<div class="w-full space-y-6">
	<div class="flex items-start justify-between gap-4">
		<div>
			<h1 class="text-2xl font-bold text-slate-800">My Courses</h1>
			<p class="text-sm text-slate-500">Browse available training programs and enroll to continue.</p>
		</div>
	</div>

	@if(session('success'))
		<div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-700">
			{{ session('success') }}
		</div>
	@endif

	@if(session('error'))
		<div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-700">
			{{ session('error') }}
		</div>
	@endif

	@if($courses->isEmpty())
		<div class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-slate-500">
			No active courses are available yet.
		</div>
	@else
		<div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
			@foreach($courses as $course)
				@php
					$enrollment = $enrollments->get($course->id);
					$status = strtolower((string) ($enrollment->status ?? 'not_enrolled'));
					$isEnrolled = $status === 'enrolled';
					$isPending = $status === 'pending_payment';
				@endphp

				<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
					<div class="mb-2 flex items-start justify-between gap-3">
						<h2 class="text-lg font-semibold text-slate-800">{{ $course->course_name }}</h2>

						@if($isEnrolled)
							<span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Enrolled</span>
						@elseif($isPending)
							<span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">Pending Payment</span>
						@else
							<span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">Not Enrolled</span>
						@endif
					</div>

					<p class="mb-4 line-clamp-3 text-sm text-slate-600">
						{{ $course->description ?: 'No course description available yet.' }}
					</p>

					<div class="mb-4 flex items-center justify-between text-sm text-slate-500">
						<span>Sessions: {{ $course->sessions->count() }}</span>
						<span class="font-semibold text-slate-700">Fee: {{ number_format((float) ($course->fee ?? 0), 2) }}</span>
					</div>

					@if($isEnrolled)
						<div class="rounded-lg bg-emerald-50 p-3 text-xs text-emerald-700">
							Enrollment active
							@if(!empty($enrollment->expires_at))
								until {{ \Illuminate\Support\Carbon::parse($enrollment->expires_at)->format('M d, Y') }}.
							@endif
						</div>
					@else
						<form method="POST" action="{{ route('training.trainee.courses.enroll', ['course' => $course->id]) }}">
							@csrf
							<button type="submit" class="w-full rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
								{{ $isPending ? 'Continue to Payment' : 'Enroll Now' }}
							</button>
						</form>
					@endif
				</div>
			@endforeach
		</div>
	@endif
</div>
@endsection

