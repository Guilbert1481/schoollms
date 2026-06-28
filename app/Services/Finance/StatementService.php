<?php

namespace App\Services\Finance;

use App\Models\LedgerEntry;
use App\Models\StatementOfAccount;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Generates a Statement of Account for a student over a billing period by
 * snapshotting their ledger: opening balance, the period's entries, period
 * totals, and the closing balance. The cadence (period) is driven by the
 * finance-configured frequency.
 */
class StatementService
{
    /**
     * Resolve the [start, end, label] window for a calendar-driven frequency.
     * Returns null for cadences whose window must be supplied by the caller
     * (per_term needs term dates; on_demand is manual).
     */
    public function periodForFrequency(string $frequency, Carbon $asOf): ?array
    {
        return match ($frequency) {
            'monthly' => [
                'start' => $asOf->copy()->startOfMonth(),
                'end'   => $asOf->copy()->endOfMonth(),
                'label' => $asOf->format('F Y'),
            ],
            'quarterly' => [
                'start' => $asOf->copy()->firstOfQuarter(),
                'end'   => $asOf->copy()->lastOfQuarter(),
                'label' => 'Q'.$asOf->quarter.' '.$asOf->year,
            ],
            'annual' => [
                'start' => $asOf->copy()->startOfYear(),
                'end'   => $asOf->copy()->endOfYear(),
                'label' => (string) $asOf->year,
            ],
            default => null,
        };
    }

    /**
     * Generate (or return the existing) statement for a student and period.
     * Returns null when there is no ledger activity and no carry-over balance.
     */
    public function generate(int $schoolId, int $studentId, Carbon $start, Carbon $end, array $opts = []): ?StatementOfAccount
    {
        $startDate = $start->toDateString();
        $endDate   = $end->toDateString();
        $force     = (bool) ($opts['force'] ?? false);

        $existing = StatementOfAccount::query()
            ->where('school_id', $schoolId)
            ->where('student_id', $studentId)
            ->where('period_start', $startDate)
            ->where('period_end', $endDate)
            ->first();

        if ($existing && ! $force) {
            return $existing;
        }

        $base = LedgerEntry::query()
            ->where('school_id', $schoolId)
            ->where('student_id', $studentId);

        $opening = (float) (clone $base)->where('entry_date', '<', $startDate)
            ->selectRaw('COALESCE(SUM(debit) - SUM(credit), 0) as bal')->value('bal');

        $periodEntries = (clone $base)
            ->whereBetween('entry_date', [$startDate, $endDate])
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        $totalCharges = round((float) $periodEntries->sum('debit'), 2);
        $totalCredits = round((float) $periodEntries->sum('credit'), 2);
        $opening      = round($opening, 2);
        $closing      = round($opening + $totalCharges - $totalCredits, 2);

        if ($periodEntries->isEmpty() && abs($closing) < 0.005 && ! $force) {
            return null;
        }

        $lineItems = $periodEntries->map(fn (LedgerEntry $e) => [
            'date'          => optional($e->entry_date)->toDateString(),
            'type'          => $e->type,
            'description'   => $e->description,
            'reference'     => $e->reference,
            'debit'         => (float) $e->debit,
            'credit'        => (float) $e->credit,
            'balance_after' => (float) $e->balance_after,
        ])->all();

        $payload = [
            'soa_number'            => $existing?->soa_number ?? $this->generateSoaNumber($schoolId),
            'school_id'             => $schoolId,
            'student_id'            => $studentId,
            'student_enrollment_id' => $opts['student_enrollment_id'] ?? null,
            'academic_year_id'      => $opts['academic_year_id'] ?? null,
            'term_id'               => $opts['term_id'] ?? null,
            'frequency'             => $opts['frequency'] ?? 'on_demand',
            'period_start'          => $startDate,
            'period_end'            => $endDate,
            'period_label'          => $opts['period_label'] ?? ($start->format('M d').' – '.$end->format('M d, Y')),
            'opening_balance'       => $opening,
            'total_charges'         => $totalCharges,
            'total_credits'         => $totalCredits,
            'closing_balance'       => $closing,
            'due_date'              => isset($opts['due_date'])
                ? Carbon::parse($opts['due_date'])->toDateString()
                : $end->copy()->addDays((int) ($opts['due_days'] ?? 7))->toDateString(),
            'status'                => $opts['status'] ?? StatementOfAccount::STATUS_ISSUED,
            'line_items'            => $lineItems,
            'generated_by'          => $opts['generated_by'] ?? null,
            'generated_at'          => Carbon::now(),
        ];

        return DB::transaction(function () use ($existing, $payload, $force) {
            if ($existing && $force) {
                $existing->update($payload);

                return $existing->fresh();
            }

            return StatementOfAccount::create($payload);
        });
    }

    public function generateSoaNumber(int $schoolId): string
    {
        $prefix = 'SOA-'.Carbon::now()->format('Ymd').'-';

        do {
            $candidate = $prefix.strtoupper(Str::random(6));
            $exists = StatementOfAccount::query()
                ->where('school_id', $schoolId)
                ->where('soa_number', $candidate)
                ->exists();
        } while ($exists);

        return $candidate;
    }
}
