<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\PrincipalDashboardService;

class DashboardController extends Controller
{
    /**
     * Principal executive dashboard. Thin controller — all data shaping
     * lives in PrincipalDashboardService (student-dashboard pattern).
     */
    public function index(PrincipalDashboardService $dashboard)
    {
        $schoolId = (int) auth()->user()->school_id;
        abort_unless($schoolId, 404);

        return view('principal.dashboard', [
            'cards' => $dashboard->cards($schoolId),
            'dash'  => $dashboard->widgets($schoolId),
        ]);
    }
}
