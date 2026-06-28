<?php

namespace App\Support;

use App\Models\EnrollmentSetting;
use App\Models\StudentEnrollment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Builds virtual (derived) enrollment notifications for the bell.
 *
 * These are NOT stored in the notifications table. They are computed live so
 * that any student (including newly-created ones) automatically sees active
 * enrolment windows. A notification disappears once the student submits an
 * application (StudentEnrollment status leaves "draft") or the end date
 * passes.
 */
class EnrollmentNotifications
{
    /** Days before end_date when the bell should flash red. */
    public const URGENT_THRESHOLD_DAYS = 5;

    /**
     * @return array<int,array<string,mixed>> Virtual notification rows.
     */
    public static function forUser(?User $user): array
    {
        if (! $user || $user->role !== 'student') {
            return [];
        }

        $today    = Carbon::now()->startOfDay();
        $schoolId = $user->school_id;

        $settings = EnrollmentSetting::query()
            ->with('term')
            ->whereDate('end_date', '>=', $today)
            ->when($schoolId, function ($q) use ($schoolId) {
                $q->whereHas('term', fn ($t) => $t->where('school_id', $schoolId));
            })
            ->orderBy('end_date')
            ->get();

        if ($settings->isEmpty()) {
            return [];
        }

        // Find settings the student already acted on (anything beyond draft).
        // We dismiss by either matching enrollment_setting_id directly OR by
        // matching term_id (covers historic submissions where the setting id
        // wasn't recorded on the enrollment row).
        $student = $user->student ?? null;
        $studentEnrollments = $student
            ? StudentEnrollment::where('student_id', $student->id)
                ->where('status', '!=', StudentEnrollment::STATUS_DRAFT)
                ->get(['enrollment_setting_id', 'term_id'])
            : collect();

        $appliedSettingIds = $studentEnrollments->pluck('enrollment_setting_id')->filter()->all();
        $appliedTermIds    = $studentEnrollments->pluck('term_id')->filter()->unique()->all();

        $rows = [];

        foreach ($settings as $setting) {
            if (in_array($setting->id, $appliedSettingIds, true)) {
                continue;
            }
            if (in_array($setting->term_id, $appliedTermIds, true)) {
                continue;
            }

            $end       = Carbon::parse($setting->end_date)->endOfDay();
            $daysLeft  = (int) $today->diffInDays($end, false);
            $urgent    = $daysLeft >= 0 && $daysLeft <= self::URGENT_THRESHOLD_DAYS;

            $window = trim(
                ($setting->start_date ? Carbon::parse($setting->start_date)->format('M d') : '')
                .' – '
                .($setting->end_date ? Carbon::parse($setting->end_date)->format('M d, Y') : '')
            );

            $rows[] = [
                'id'           => 'enroll-'.$setting->id,
                'title'        => $urgent
                    ? 'Enrollment closing soon'
                    : 'Enrollment is now open',
                'message'      => trim(($setting->title ?: $setting->name).' '.($window ? "($window)" : '')),
                'type'         => 'enrollment',
                'reference_id' => $setting->term_id,
                'term_id'      => $setting->term_id,
                'read'         => false,
                'urgent'       => $urgent,
                'days_left'    => $daysLeft,
                'created_at'   => $setting->created_at,
            ];
        }

        return $rows;
    }

    /** Count of virtual rows (used for the bell badge). */
    public static function countForUser(?User $user): int
    {
        return count(self::forUser($user));
    }
}
