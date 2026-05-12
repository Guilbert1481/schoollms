<?php

namespace App\Http\Controllers\Training\Trainee;

use App\Http\Controllers\Controller;
use App\Models\EnrollmentSetting;
use App\Models\TrainingCourse;
use App\Models\TrainingEnrollment;
use App\Services\Payments\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CourseController extends Controller
{
	public function __construct(private readonly PaymentService $paymentService)
	{
	}

	public function available(Request $request)
	{
		$today = Carbon::today();

		$sessions = EnrollmentSetting::query()
			->with(['enrollmentType', 'term', 'academicYear'])
			->whereDate('end_date', '>=', $today)
			->orderBy('start_date')
			->get();

		return view('training.trainee.available_courses', [
			'sessions' => $sessions,
		]);
	}

	public function availablePrograms(Request $request, int $session)
	{
		$setting = EnrollmentSetting::with('enrollmentType')->findOrFail($session);

		$catalog = \App\Helpers\CourseCatalog::forSession($setting);
		$programs = [];

		foreach (($catalog['programs'] ?? []) as $key => $program) {
			$programs[] = [
				'key'         => $key,
				'code'        => $program['code'] ?? strtoupper($key),
				'name'        => $program['name'] ?? ucfirst($key),
				'description' => $program['description'] ?? '',
			];
		}

		return view('training.trainee.available_programs', [
			'setting'      => $setting,
			'catalogKey'   => $catalog['key'] ?? null,
			'catalogLabel' => $catalog['label'] ?? 'Courses',
			'programs'     => $programs,
		]);
	}

	public function availableProgramSubjects(Request $request, int $session, string $program)
	{
		$setting = EnrollmentSetting::with('enrollmentType')->findOrFail($session);

		$catalog = \App\Helpers\CourseCatalog::forSession($setting);
		$programKey = strtolower($program);

		abort_if(!isset($catalog['programs'][$programKey]), 404, 'Program not found.');

		$programData = $catalog['programs'][$programKey];
		$labels = config('course_catalog.category_labels', []);

		return view('training.trainee.available_program_subjects', [
			'setting'      => $setting,
			'catalogKey'   => $catalog['key'] ?? null,
			'catalogLabel' => $catalog['label'] ?? 'Courses',
			'programKey'   => $programKey,
			'program'      => $programData,
			'labels'       => $labels,
		]);
	}

	public function index(Request $request)
	{
		$user = $request->user();
		$trainee = $user->profile?->trainee;

		abort_if(!$trainee, 403, 'No trainee profile found for this account.');

		$courses = TrainingCourse::query()
			->with(['sessions' => function ($query) {
				$query->orderByDesc('start_date')->orderByDesc('id');
			}])
			->where('status', 'active')
			->orderBy('course_name')
			->get();

		$enrollments = TrainingEnrollment::query()
			->where('trainee_id', $trainee->id)
			->get()
			->keyBy('training_course_id');

		return view('training.trainee.courses', [
			'courses' => $courses,
			'enrollments' => $enrollments,
		]);
	}

	public function enroll(Request $request, int $course)
	{
		$user = $request->user();
		$trainee = $user->profile?->trainee;

		abort_if(!$trainee, 403, 'No trainee profile found for this account.');

		$trainingCourse = TrainingCourse::query()->findOrFail($course);

		$session = $trainingCourse->sessions()
			->orderBy('start_date')
			->orderBy('id')
			->first();

		if (!$session) {
			return back()->with('error', 'This course has no active session yet.');
		}

		$enrollment = TrainingEnrollment::query()->firstOrCreate(
			[
				'trainee_id' => $trainee->id,
				'training_course_id' => $trainingCourse->id,
			],
			[
				'training_session_id' => $session->id,
				'enrollment_date' => now()->toDateString(),
				'name' => trim((string) ($user->full_name ?? '')),
				'email' => $user->email,
				'type' => 'internal',
				'status' => 'pending_payment',
			]
		);

		if ((string) $enrollment->status === 'enrolled' && $enrollment->expires_at && Carbon::parse($enrollment->expires_at)->isFuture()) {
			return back()->with('success', 'You are already enrolled in this course.');
		}

		if ($enrollment->training_session_id !== $session->id) {
			$enrollment->training_session_id = $session->id;
			$enrollment->status = 'pending_payment';
			$enrollment->save();
		}

		return redirect()->route('training.trainee.courses.payment', ['enrollment' => $enrollment->id]);
	}

	public function payment(Request $request, int $enrollment)
	{
		$user = $request->user();
		$trainee = $user->profile?->trainee;

		abort_if(!$trainee, 403, 'No trainee profile found for this account.');

		$record = TrainingEnrollment::query()
			->with(['course', 'session'])
			->where('trainee_id', $trainee->id)
			->findOrFail($enrollment);

		return view('training.trainee.payment', [
			'enrollment' => $record,
			'course' => $record->course,
			'session' => $record->session,
		]);
	}

	public function confirmPayment(Request $request, int $enrollment)
	{
		$user = $request->user();
		$trainee = $user->profile?->trainee;

		abort_if(!$trainee, 403, 'No trainee profile found for this account.');

		$record = TrainingEnrollment::query()
			->with(['course', 'session'])
			->where('trainee_id', $trainee->id)
			->findOrFail($enrollment);

		$validated = $request->validate([
			'payment_method' => ['required', 'string', 'max:50'],
			'reference_number' => ['nullable', 'string', 'max:120'],
		]);

		$this->paymentService->payTrainingEnrollment(
			$user,
			$record,
			$validated['payment_method'],
			$validated['reference_number'] ?? null
		);

		return redirect()
			->route('training.trainee.courses')
			->with('success', 'Payment detected and enrollment activated.');
	}

	public function materials(Request $request)
	{
		$user = $request->user();
		$trainee = $user->profile?->trainee;

		abort_if(!$trainee, 403, 'No trainee profile found for this account.');

		$materials = DB::table('training_materials as tm')
			->join('training_courses as tc', 'tc.id', '=', 'tm.training_course_id')
			->join('training_enrollments as te', 'te.training_course_id', '=', 'tc.id')
			->where('te.trainee_id', $trainee->id)
			->where('te.status', 'enrolled')
			->select('tm.*', 'tc.course_name')
			->orderBy('tc.course_name')
			->orderBy('tm.title')
			->get();

		return view('training.trainee.materials', [
			'materials' => $materials,
		]);
	}
}

