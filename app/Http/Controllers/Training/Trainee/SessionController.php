<?php

namespace App\Http\Controllers\Training\Trainee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SessionController extends Controller
{
	public function index(Request $request)
	{
		$user = $request->user();
		$trainee = $user->profile?->trainee;

		abort_if(!$trainee, 403, 'No trainee profile found for this account.');

		$sessions = DB::table('training_enrollments as te')
			->join('training_sessions as ts', 'ts.id', '=', 'te.training_session_id')
			->join('training_courses as tc', 'tc.id', '=', 'te.training_course_id')
			->where('te.trainee_id', $trainee->id)
			->where('te.status', 'enrolled')
			->select('ts.*', 'tc.course_name', 'te.expires_at')
			->orderBy('ts.start_date')
			->get();

		return view('training.trainee.sessions', [
			'sessions' => $sessions,
		]);
	}

	public function progress(Request $request)
	{
		$user = $request->user();
		$trainee = $user->profile?->trainee;

		abort_if(!$trainee, 403, 'No trainee profile found for this account.');

		$rows = DB::table('training_enrollments as te')
			->join('training_courses as tc', 'tc.id', '=', 'te.training_course_id')
			->leftJoin('training_assessment_scores as tas', 'tas.trainee_id', '=', 'te.trainee_id')
			->where('te.trainee_id', $trainee->id)
			->groupBy('te.id', 'tc.course_name', 'te.status', 'te.expires_at')
			->selectRaw('tc.course_name, te.status, te.expires_at, COALESCE(AVG(tas.score), 0) as average_score')
			->orderBy('tc.course_name')
			->get();

		return view('training.trainee.progress', [
			'progressRows' => $rows,
		]);
	}
}

