<?php

namespace App\Services\Attendance;

use App\Models\AcademicLevel;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSetting;
use App\Support\AcademicBand;
use Illuminate\Support\Facades\DB;

/**
 * Resolves the AttendanceSetting that governs a capture context, so unattended
 * capture (QR scan, biometric/facial device) can derive present-vs-late from the
 * level's late rule instead of always recording a plain check-in.
 *
 * Config is stored per academic_level; a context reaches its level through its
 * section's year level (the same bridge the grading resolver uses):
 *
 *   - session (a class):   class → section.year_level → academic_level(higher, sequence_order)
 *   - daily   (a section): section.year_level        → academic_level(basic,  sequence_order)
 *
 * Always returns a USABLE setting — the saved row when present, otherwise an
 * unsaved model with the type defaults (no late cutoff, so capture falls back to
 * "present"). Never null, so callers always have late_after / grace_minutes to
 * hand to the ingestion funnel.
 *
 * NOTE: basic-ed levels are matched by sequence_order = year_level; a school
 * whose basic levels are not sequenced to match year levels falls back to
 * defaults. Tightening that mapping is tracked with the grading resolver.
 */
class AttendanceSettingResolver
{
    public function forContext(int $schoolId, string $scope, int $contextId): AttendanceSetting
    {
        if ($scope === AttendanceRecord::SCOPE_SESSION) {
            $sectionId = DB::table('classes')->where('id', $contextId)->value('section_id');
            $band = AcademicBand::HIGHER;
        } else {
            $sectionId = $contextId;
            $band = AcademicBand::BASIC;
        }

        $yearLevel = $sectionId
            ? DB::table('sections')->where('id', $sectionId)->value('year_level')
            : null;

        return $this->resolve($schoolId, $band, $yearLevel === null ? null : (int) $yearLevel);
    }

    /* ------------------------------------------------------------------ */

    private function resolve(int $schoolId, string $type, ?int $sequence): AttendanceSetting
    {
        // AcademicLevel has no BelongsToSchool scope — filter school explicitly.
        $level = $sequence === null ? null : AcademicLevel::where('school_id', $schoolId)
            ->where('type', $type)
            ->where('sequence_order', $sequence)
            ->first();

        if ($level) {
            $saved = AttendanceSetting::where('school_id', $schoolId)
                ->where('academic_level_id', $level->id)
                ->first();

            if ($saved) {
                return $saved;
            }
        }

        return new AttendanceSetting(
            AttendanceSetting::defaultsForType($type) + [
                'school_id' => $schoolId,
                'academic_level_id' => $level?->id,
            ]
        );
    }
}
