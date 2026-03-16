<?php

namespace App\Services\Dashboard;

use App\Models\User;
use App\Models\Invoice;
use App\Models\School;
use Illuminate\Support\Facades\Cache;

class AlertRegistry
{
    public function getAlerts($user, string $role): array
    {
        $schoolId = $user->school_id;

        return Cache::remember(
            "dashboard.alerts.{$schoolId}.{$role}",
            now()->addMinutes(5),
            function () use ($schoolId, $role) {

                /*
                |--------------------------------------------------------------------------
                | 1️⃣ RAW ALERT VALUES
                |--------------------------------------------------------------------------
                */

                $raw = [

                    'high_outstanding' => Invoice::where('school_id', $schoolId)
                        ->where('status', 'unpaid')
                        ->sum('balance'),

                    'low_enrollment' => User::where('school_id', $schoolId)
                        ->where('role', 'student')
                        ->count(),

                    'inactive_schools' => School::where('is_active', false)->count(),

                    'low_application_conversion' => 0, // replace later
                ];

                /*
                |--------------------------------------------------------------------------
                | 2️⃣ LOAD ROLE-BASED ALERT KEYS FROM CONFIG
                |--------------------------------------------------------------------------
                */

                $roleAlerts  = config("alerts.roles.$role.risk_alerts", []);
                $definitions = config('alerts.definitions');

                $alerts = [];

                foreach ($roleAlerts as $key) {

                    if (!isset($definitions[$key])) {
                        continue;
                    }

                    $value     = $raw[$key] ?? 0;
                    $threshold = $definitions[$key]['threshold'] ?? null;

                    /*
                    |--------------------------------------------------------------------------
                    | 3️⃣ TRIGGER LOGIC
                    |--------------------------------------------------------------------------
                    */

                    $triggered = $threshold !== null
                        ? $value >= $threshold
                        : $value > 0;

                    if (!$triggered) {
                        continue;
                    }

                    $alerts[] = [
                        'key'   => $key,
                        'title' => $definitions[$key]['title'],
                        'value' => number_format($value),
                        'color' => $definitions[$key]['color'],
                    ];
                }

                return $alerts;
            }
        );
    }
}
