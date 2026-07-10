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

        // The student dashboard needs widget data beyond the registries
        // (schedule, deadlines, charts, calendar) — its own controller
        // assembles the full payload, so send students there.
        if ($role === 'student') {
            return redirect()->route('student.dashboard');
        }

        // Parent-portal users have their own shell (child switcher + per-child
        // pages) and no school_id, so none of the registry KPIs apply.
        if ($role === 'parent') {
            return redirect()->route('parent.dashboard');
        }

        // The principal executive dashboard needs widget data beyond the
        // registries (trend charts, tables, right rail) — its own controller
        // assembles the full payload via PrincipalDashboardService.
        if ($role === 'principal') {
            return redirect()->route('principal.dashboard');
        }

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
            'principal'             => 'principal.dashboard',
            'trainee'               => 'training.trainee.dashboard',
            'trainor'               => 'training.trainor.dashboard',
            'admission_manager'     => 'admission.dashboard',
            'admission_manager'     => 'admission.dashboard',
            'program_head'          => 'program_head.dashboard',
            'training_program_head' => 'training.program_head.dashboard',
            'course_architect'      => 'course-architect.dashboard',
            'registrar'             => 'registrar.dashboard',
            'finance_manager'       => 'finance.dashboard',
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
