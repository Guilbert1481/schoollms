<?php

namespace App\Http\Controllers\Training\Trainee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnrollmentController extends Controller
{
	public function assessment(Request $request)
	{
		$user = $request->user();
		$trainee = $user->profile?->trainee;

		abort_if(!$trainee, 403, 'No trainee profile found for this account.');

		$assessments = DB::table('training_assessments as ta')
			->leftJoin('training_assessment_scores as tas', function ($join) use ($trainee) {
				$join->on('tas.assessment_id', '=', 'ta.id')
					->where('tas.trainee_id', '=', $trainee->id);
			})
			->select('ta.*', 'tas.score', 'tas.status as score_status')
			->orderByDesc('ta.id')
			->get();

		return view('training.trainee.assessment', [
			'assessments' => $assessments,
		]);
	}

	public function attendance(Request $request)
	{
		$user = $request->user();
		$trainee = $user->profile?->trainee;

		abort_if(!$trainee, 403, 'No trainee profile found for this account.');

		$attendance = DB::table('training_attendance as ta')
			->join('training_enrollments as te', 'te.id', '=', 'ta.training_enrollment_id')
			->join('training_courses as tc', 'tc.id', '=', 'te.training_course_id')
			->where('te.trainee_id', $trainee->id)
			->select('ta.*', 'tc.course_name')
			->orderByDesc('ta.attendance_date')
			->get();

		return view('training.trainee.attendance', [
			'attendanceRows' => $attendance,
		]);
	}
}

