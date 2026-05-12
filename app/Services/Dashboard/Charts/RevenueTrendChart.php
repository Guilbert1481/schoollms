<?php
// app/Services/Dashboard/Charts/RevenueTrendChart.php

namespace App\Services\Dashboard\Charts;

use App\Models\Payment;
use App\Models\User;
use App\Services\Dashboard\Charts\Contracts\TrendChart;

class RevenueTrendChart implements TrendChart
{
    public static function key(): string
    {
        return 'revenue_trend';
    }

    public function build(User $user, string $scope = 'academic_year'): array
    {
        // You can later refactor these helpers into a shared trait/base class
        [$start, $end, $group] = $this->resolveRange($scope);

        if ($group === 'day') {
            $raw = Payment::where('school_id', $user->school_id)
                ->whereBetween('created_at', [$start, $end])
                ->selectRaw('DAY(created_at) as bucket, SUM(amount) as total')
                ->groupBy('bucket')
                ->orderBy('bucket')
                ->pluck('total', 'bucket')
                ->toArray();

            return [
                'labels' => array_keys($this->normalizeDays($start, $raw)),
                'data'   => array_values($this->normalizeDays($start, $raw)),
            ];
        }

        // group by month (YYYY-MM)
        $rows = Payment::where('school_id', $user->school_id)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('YEAR(created_at) as y, MONTH(created_at) as m, SUM(amount) as total')
            ->groupBy('y', 'm')
            ->orderBy('y')
            ->orderBy('m')
            ->get();

        $raw = $rows->mapWithKeys(fn ($r) => [
            sprintf('%04d-%02d', $r->y, $r->m) => (float) $r->total
        ])->toArray();

        $normalized = $this->normalizeMonthsRange($start, $end, $raw);

        return [
            'labels' => array_keys($normalized),
            'data'   => array_values($normalized),
        ];
    }

    private function resolveRange(string $scope): array
    {
        $now = now();

        return match ($scope) {
            'month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth(), 'day'],
            'semester' => $this->semesterRange($now),
            'academic_year' => $this->academicYearRange($now, (int) config('school.academic_year_start_month', 6)),
            default => [$now->copy()->startOfYear(), $now->copy()->endOfYear(), 'month'],
        };
    }

    private function semesterRange($now): array
    {
        $isSem1 = $now->month <= 6;
        $start = $isSem1 ? $now->copy()->month(1)->startOfMonth() : $now->copy()->month(7)->startOfMonth();
        $end   = $isSem1 ? $now->copy()->month(6)->endOfMonth()   : $now->copy()->month(12)->endOfMonth();
        return [$start, $end, 'month'];
    }

    private function academicYearRange($now, int $startMonth): array
    {
        $startYear = ($now->month < $startMonth) ? $now->year - 1 : $now->year;
        $start = $now->copy()->setDate($startYear, $startMonth, 1)->startOfDay();
        $end   = $start->copy()->addYear()->subDay()->endOfDay();
        return [$start, $end, 'month'];
    }

    private function normalizeDays($start, array $data): array
    {
        $out = [];
        $days = $start->daysInMonth;
        for ($d = 1; $d <= $days; $d++) {
            $out[$d] = (float) ($data[$d] ?? 0);
        }
        return $out;
    }

    private function normalizeMonthsRange($start, $end, array $data): array
    {
        $out = [];
        $cursor = $start->copy()->startOfMonth();
        while ($cursor <= $end) {
            $key = $cursor->format('Y-m');
            $out[$key] = (float) ($data[$key] ?? 0);
            $cursor->addMonth();
        }
        return $out;
    }
}