<?php

namespace App\Services\Attendance;

use App\Models\AttendanceRecord;
use Illuminate\Support\Carbon;

/**
 * Derives an attendance status from a captured time-in and a level's late rule.
 * Used when a status is not set explicitly — i.e. device/QR capture, which
 * records a time but not a present/late/absent decision. Manual roster entry
 * sets the status directly and never reaches this.
 *
 * No time-in → absent. With a time-in and a configured late cutoff, a time-in
 * after (cutoff + grace) is late; otherwise present. Pure and DB-free.
 */
class AttendanceStatusResolver
{
    public function derive(?string $timeIn, ?string $lateAfter = null, int $graceMinutes = 0): string
    {
        if (empty($timeIn)) {
            return AttendanceRecord::STATUS_ABSENT;
        }

        if (empty($lateAfter)) {
            return AttendanceRecord::STATUS_PRESENT;
        }

        $cutoff = Carbon::createFromTimeString($lateAfter)->addMinutes(max(0, $graceMinutes));
        $arrived = Carbon::createFromTimeString($timeIn);

        return $arrived->greaterThan($cutoff)
            ? AttendanceRecord::STATUS_LATE
            : AttendanceRecord::STATUS_PRESENT;
    }
}
