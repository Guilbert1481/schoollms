<?php

namespace App\Services\Dashboard;

use App\Models\User;
use App\Models\Student;
use App\Models\Application;
use App\Models\Payment;
use App\Models\Invoice;
use App\Models\School;
use Carbon\Carbon;

class KPIRegistry
{
    public function getCards($user, $role): array
    {
        $roleCards = config("dashboard.kpi.roles.$role.summary_cards", []);
        $definitions = config("dashboard.kpi.summary_cards", []);

        $output = [];

        foreach ($roleCards as $cardKey) {

            if (!isset($definitions[$cardKey])) {
                continue;
            }

            $card = $definitions[$cardKey];
            $card['value'] = $this->resolveValue($cardKey, $user);

            $output[] = $card;
        }

        return $output;
    }

    private function resolveValue(string $key, $user)
    {
        return match ($key) {

            // -----------------------
            // ACADEMIC KPIs
            // -----------------------

            'students' =>
                Student::where('school_id', $user->school_id)
                    ->where('status', 'active')
                    ->count(),

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
