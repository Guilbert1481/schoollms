<?php

namespace App\Services\Dashboard;

use Illuminate\Support\Facades\Cache;
use App\Models\User;
use App\Models\Payment;
use App\Models\School;

class ChartRegistry
{
    public function getCharts($user, string $role): array
    {
        $schoolId = $user->school_id;

        return Cache::remember(
            "dashboard.charts.{$schoolId}.{$role}",
            now()->addMinutes(5),
            function () use ($schoolId, $role) {

                /*
                |--------------------------------------------------------------------------
                | RAW CHART DATA
                |--------------------------------------------------------------------------
                */

                $raw = [
                    'revenue_trend' => Payment::where('school_id', $schoolId)
                        ->selectRaw('MONTH(created_at) as month, SUM(amount) as total')
                        ->groupBy('month')
                        ->orderBy('month')
                        ->pluck('total', 'month')
                        ->toArray(),

                    'enrollment_trend' => User::where('school_id', $schoolId)
                        ->where('role', 'student')
                        ->selectRaw('MONTH(created_at) as month, COUNT(*) as total')
                        ->groupBy('month')
                        ->orderBy('month')
                        ->pluck('total', 'month')
                        ->toArray(),

                    'application_trend' => [],
                    'school_growth' => School::selectRaw('MONTH(created_at) as month, COUNT(*) as total')
                        ->groupBy('month')
                        ->orderBy('month')
                        ->pluck('total', 'month')
                        ->toArray(),
                ];

                $roleCharts  = config("dashboard.charts.roles.$role.trend_charts", []);
                $definitions = config("dashboard.charts.definitions", []);

                $charts = [];

                foreach ($roleCharts as $key) {
                    if (!isset($definitions[$key])) {
                        continue;
                    }

                    $charts[$key] = [
                        'title' => $definitions[$key]['title'],
                        'type'  => $definitions[$key]['type'],
                        'color' => $definitions[$key]['color'],
                        'data'  => $this->normalizeMonths($raw[$key] ?? []),
                    ];
                }

                return $charts;
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Ensure All 12 Months Exist (Missing Months = 0)
    |--------------------------------------------------------------------------
    */
    private function normalizeMonths(array $data): array
    {
        $normalized = [];

        for ($month = 1; $month <= 12; $month++) {
            $normalized[$month] = $data[$month] ?? 0;
        }

        return $normalized;
    }
}
