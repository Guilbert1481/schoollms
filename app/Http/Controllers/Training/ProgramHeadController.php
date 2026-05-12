<?php

namespace App\Http\Controllers\Training;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\AlertRegistry;
use App\Services\Dashboard\ChartRegistry;
use App\Services\Dashboard\KPIRegistry;

class ProgramHeadController extends Controller
{
    /**
     * Training Program Head dashboard.
     */
    public function index(
        KPIRegistry $kpiRegistry,
        ChartRegistry $chartRegistry,
        AlertRegistry $alertRegistry
    ) {
        $user = auth()->user();
        $role = 'training_program_head';

        $cards       = $kpiRegistry->getCards($user, $role);
        $trendCharts = $chartRegistry->getCharts($user, $role);
        $riskAlerts  = $alertRegistry->getAlerts($user, $role);

        return view('training.program-head.dashboard', compact(
            'cards',
            'trendCharts',
            'riskAlerts'
        ));
    }
}
