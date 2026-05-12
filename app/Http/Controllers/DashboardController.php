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

        $role = strtolower(auth()->user()->role_name ?? auth()->user()->role ?? '');
        // normalize hyphenated/spaced variants (e.g. "course-architect" or "Course Architect")
        $role = str_replace(['-', ' '], '_', $role);

        // ✅ ROLE → VIEW MAPPING
        $viewMap = [
            'superadmin'           => 'superadmin.dashboard',
            'student'               => 'student.dashboard',
            'teacher'               => 'teacher.dashboard',
            'admin'                 => 'admin.dashboard',
            'dean'                  => 'dean.dashboard',
            'trainee'               => 'training.trainee.dashboard',
            'trainor'               => 'training.trainor.dashboard',
            'admission'             => 'admission.dashboard',
            'admission_manager'     => 'admission.dashboard',
            'program_head'          => 'program_head.dashboard',
            'training_program_head' => 'training.program_head.dashboard',
            'course_architect'      => 'course-architect.dashboard',
            'registrar'             => 'registrar.dashboard',
            'finance'               => 'finance.dashboard',
            'counselor'             => 'counselor.dashboard',
            'guidance_counselor'    => 'guidance_counselor.dashboard',
            'staff'                 => 'staff.dashboard',
        ];

        // fallback if role not mapped
        $view = $viewMap[$role] ?? 'dashboard.default';

        return view($view, compact(
            'cards',
            'trendCharts',
            'riskAlerts'
        ));
    }
}
