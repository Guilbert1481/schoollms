<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\KPIRegistry;
use App\Services\Dashboard\StudentDashboardService;
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
        ]);
    }
}
