<?php

namespace App\Http\Controllers\Training\Trainee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
	public function index(Request $request)
	{
		$user = $request->user();
		$trainee = $user->profile?->trainee;

		abort_if(!$trainee, 403, 'No trainee profile found for this account.');

		$now = Carbon::now();

		$stats = DB::table('training_enrollments')
			->where('trainee_id', $trainee->id)
			->selectRaw('COUNT(*) as total')
			->selectRaw("SUM(CASE WHEN status = 'enrolled' THEN 1 ELSE 0 END) as enrolled")
			->selectRaw("SUM(CASE WHEN expires_at IS NOT NULL AND expires_at < ? THEN 1 ELSE 0 END) as expired", [$now])
			->first();

		return view('training.trainee.dashboard', [
			'stats' => $stats,
		]);
	}
}

