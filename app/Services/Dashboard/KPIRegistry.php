<?php

namespace App\Services\Dashboard;

use App\Models\User;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Application;
use App\Models\Payment;
use App\Models\Invoice;
use App\Models\School;
use Carbon\Carbon;

class KPIRegistry
{
    /** Student card values come from one shared, memoised computation. */
    private ?array $studentSummary = null;

    public function getCards($user, $role): array
    {
        $roleCards = config("dashboard.kpi.roles.$role.summary_cards", []);
        $definitions = config("dashboard.kpi.summary_cards", []);

        $output = [];

        foreach ($roleCards as $cardKey) {

            if (!isset($definitions[$cardKey])) {
                continue;
            }

            // Enrolled Units is a higher-ed concept; basic-ed has no units, so
            // hide that KPI for basic-ed students (it would always read 0).
            if ($cardKey === 'units_student'
                && app(StudentDashboardService::class)->isBasicEd($user)) {
                continue;
            }

            $card = $definitions[$cardKey];
            $card['key'] = $cardKey;   // let views target a specific tile (e.g. the at-risk modal)
            $card['value'] = $this->resolveValue($cardKey, $user);

            $subtitle = $this->resolveSubtitle($cardKey, $user);
            if ($subtitle !== null) {
                $card['subtitle'] = $subtitle;
            }

            $output[] = $card;
        }

        return $output;
    }

    private function studentSummary($user): array
    {
        return $this->studentSummary
            ??= app(StudentDashboardService::class)->summary($user);
    }

    private function resolveSubtitle(string $key, $user): ?string
    {
        if (str_ends_with($key, '_student') || $key === 'subject_at_risk') {
            return $this->studentSummary($user)[$key]['subtitle'] ?? null;
        }

        return null;
    }

    private function resolveValue(string $key, $user)
    {
        // Student cards resolve through StudentDashboardService.
        if (str_ends_with($key, '_student') || $key === 'subject_at_risk') {
            return $this->studentSummary($user)[$key]['value'] ?? 0;
        }

        return match ($key) {

            // -----------------------
            // ACADEMIC KPIs
            // -----------------------

            'students' =>
                StudentEnrollment::where('school_id', $user->school_id)
                    ->whereIn('status', [
                        StudentEnrollment::STATUS_ENROLLED,
                        StudentEnrollment::STATUS_PROVISIONALLY_ENROLLED,
                    ])
                    ->distinct('student_id')
                    ->count('student_id'),

            'teachers' =>
                User::where('school_id', $user->school_id)
                    ->where('role', 'teacher')
                    ->count(),

            'new_applications' =>
                Application::where('school_id', $user->school_id)
                    ->where('status', 'pending')
                    ->count(),

            // -----------------------
            // FINANCE KPIs
            // -----------------------

            'revenue' =>
                $this->formatCurrency(
                    $this->resolveMonthlyRevenue($user)
                ),

            'outstanding' =>
                $this->formatCurrency(
                    Invoice::where('school_id', $user->school_id)
                        ->where('status', 'unpaid')
                        ->sum('total_amount')
                ),

            'awaiting_payment' =>
                StudentEnrollment::where('school_id', $user->school_id)
                    ->where('status', StudentEnrollment::STATUS_SENT_BILLING)
                    ->count(),

            // -----------------------
            // EXECUTIVE KPIs
            // -----------------------

            'active_schools' =>
                School::where('is_active', true)->count(),

            default => 0,
        };
    }

    /**
     * Monthly Revenue (based on paid_at)
     */
    private function resolveMonthlyRevenue($user)
    {
        return Payment::where('school_id', $user->school_id)
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('amount');
    }

    /**
     * Yearly Revenue (future use if needed)
     */
    private function resolveYearlyRevenue($user)
    {
        return Payment::where('school_id', $user->school_id)
            ->whereYear('paid_at', now()->year)
            ->sum('amount');
    }

    /**
     * Format currency
     */
    private function formatCurrency($amount): string
    {
        return '₱' . number_format($amount ?? 0, 2);
    }
}
