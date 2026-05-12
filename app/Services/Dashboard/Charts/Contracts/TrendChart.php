<?php
// app/Services/Dashboard/Charts/Contracts/TrendChart.php

namespace App\Services\Dashboard\Charts\Contracts;

use App\Models\User;

interface TrendChart
{
    /** Unique config key, e.g. "revenue_trend" */
    public static function key(): string;

    /**
     * Build chart output for the given user + scope.
     * Scope: month|semester|academic_year (you define supported values)
     */
    public function build(User $user, string $scope = 'academic_year'): array;
}