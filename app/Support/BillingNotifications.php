<?php

namespace App\Support;

use App\Models\StudentEnrollment;
use App\Models\User;
use Carbon\Carbon;

/**
 * Builds virtual (derived) Statement-of-Account notifications for the
 * student bell. A row appears once the Registrar has sent the enrollment
 * to billing (status = sent_billing) and disappears automatically when:
 *
 *  - The Finance Manager records payment (status moves to enrolled /
 *    provisionally_enrolled and payment_due_at is cleared); OR
 *  - The deadline (payment_due_at) has already passed.
 *
 * Within URGENT_THRESHOLD_DAYS of the deadline the row is flagged urgent
 * so the bell badge / row can flash red in the UI.
 */
class BillingNotifications
{
    /** Days before payment_due_at when the bell should flash red. */
    public const URGENT_THRESHOLD_DAYS = 3;

    /**
     * @return array<int,array<string,mixed>> Virtual notification rows.
     */
    public static function forUser(?User $user): array
    {
        if (! $user || $user->role !== 'student') {
            return [];
        }

        $student = $user->student ?? null;
        if (! $student) {
            return [];
        }

        $now = Carbon::now();

        $enrollments = StudentEnrollment::query()
            ->where('student_id', $student->id)
            ->where('status', StudentEnrollment::STATUS_SENT_BILLING)
            ->whereNotNull('payment_due_at')
            ->where('payment_due_at', '>', $now)
            ->orderBy('payment_due_at')
            ->get();

        $rows = [];

        foreach ($enrollments as $enrollment) {
            $due       = Carbon::parse($enrollment->payment_due_at);
            $daysLeft  = (int) $now->copy()->startOfDay()->diffInDays($due->copy()->startOfDay(), false);
            $urgent    = $daysLeft >= 0 && $daysLeft <= self::URGENT_THRESHOLD_DAYS;

            $cleared = $enrollment->billing_cleared_as === 'provisional'
                ? 'Provisional'
                : 'Assessed';

            $rows[] = [
                'id'           => 'soa-'.$enrollment->id,
                'title'        => $urgent
                    ? 'Payment due soon – Statement of Account'
                    : 'Statement of Account issued',
                'message'      => "Your enrollment ({$cleared}) has been forwarded to Finance. "
                                  ."Settle on or before {$due->format('M d, Y')}.",
                'type'         => 'billing',
                'reference_id' => $enrollment->id,
                'term_id'      => $enrollment->term_id,
                'read'         => false,
                'urgent'       => $urgent,
                'days_left'    => $daysLeft,
                'due_at'       => $due->toIso8601String(),
                'created_at'   => $enrollment->updated_at ?? $enrollment->created_at,
            ];
        }

        return $rows;
    }

    public static function countForUser(?User $user): int
    {
        return count(self::forUser($user));
    }
}
