<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\KPIRegistry;
use App\Services\Dashboard\ChartRegistry;
use App\Services\Dashboard\AlertRegistry;

class DashboardController extends Controller
{
    /**
     * Master Dashboard Router (Registry-Driven System)
     */
    public function index(
        KPIRegistry $kpiRegistry,
        ChartRegistry $chartRegistry,
        AlertRegistry $alertRegistry
    ) {
        $user = auth()->user();
        $role = strtolower($user->role);

        /*
        |--------------------------------------------------------------------------
        | 1️⃣ LOAD DASHBOARD DATA VIA REGISTRIES
        |--------------------------------------------------------------------------
        */

        $cards       = $kpiRegistry->getCards($user, $role);
        $trendCharts = $chartRegistry->getCharts($user, $role);
        $riskAlerts  = $alertRegistry->getAlerts($user, $role);

        /*
        |--------------------------------------------------------------------------
        | 2️⃣ RETURN ROLE-BASED DASHBOARD VIEW
        |--------------------------------------------------------------------------
        */

        return view("$role.dashboard", compact(
            'cards',
            'trendCharts',
            'riskAlerts'
        ));
    }
}
