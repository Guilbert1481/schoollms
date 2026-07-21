<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\KPIRegistry;
use App\Services\Dashboard\StudentDashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(KPIRegistry $kpis, StudentDashboardService $dashboard)
    {
        $user = Auth::user();

        // Pass the approved modality to drive the sidebar logic
        $activeModality = $user->active_modality ?? $user->enrollment_type;

        return view('student.dashboard', [
            'user'           => $user,
            'activeModality' => $activeModality,
            'cards'          => $kpis->getCards($user, 'student'),
            'dash'           => $dashboard->widgets($user),
            'atRisk'         => $dashboard->subjectsAtRisk($user),
        ]);
    }

    /** JSON month events for the dashboard calendar's prev/next navigation. */
    public function calendar(Request $request, StudentDashboardService $dashboard)
    {
        $user  = Auth::user();
        $year  = (int) $request->query('year', now()->format('Y'));
        $month = (int) $request->query('month', now()->format('n'));

        if ($month < 1 || $month > 12) {
            $month = (int) now()->format('n');
        }
        // Keep navigation within a sane window (±5 years) to avoid abuse.
        $year = max((int) now()->format('Y') - 5, min((int) now()->format('Y') + 5, $year));

        return response()->json($dashboard->calendarFor($user, $year, $month));
    }
}
