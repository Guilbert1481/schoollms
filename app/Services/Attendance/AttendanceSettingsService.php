<?php

namespace App\Services\Attendance;

use App\Models\AcademicLevel;
use App\Models\AttendanceSetting;
use App\Support\AcademicBand;
use Illuminate\Support\Collection;

/**
 * Reads and writes per-level attendance configuration for one role band. The
 * "band" fixes which academic-level types a role may touch — basic ed for the
 * Principal, higher ed for the Dean — and is set by the route, never by user
 * input, so neither role can configure the other's levels (see AcademicBand).
 */
class AttendanceSettingsService
{
    /**
     * The band's levels, each paired with its saved setting or an unsaved model
     * carrying type-appropriate defaults (so the form always has values to show
     * without writing rows on a mere page view).
     *
     * @return Collection<int, array{level: AcademicLevel, setting: AttendanceSetting}>
     */
    public function levelsWithSettings(int $schoolId, string $band): Collection
    {
        $levels = AcademicBand::levels($schoolId, $band);

        $saved = AttendanceSetting::where('school_id', $schoolId)
            ->whereIn('academic_level_id', $levels->pluck('id'))
            ->get()
            ->keyBy('academic_level_id');

        return $levels->map(fn (AcademicLevel $level) => [
            'level' => $level,
            'setting' => $saved->get($level->id) ?? new AttendanceSetting(
                AttendanceSetting::defaultsForType($level->type) + ['academic_level_id' => $level->id]
            ),
        ])->values();
    }

    /**
     * Persist the posted per-level settings. Any level id not in this band is
     * ignored (a crafted POST cannot reach the other role's levels).
     *
     * @param  array<int|string, array<string, mixed>>  $rows
     */
    public function save(int $schoolId, string $band, array $rows): int
    {
        $allowedIds = AcademicBand::levels($schoolId, $band)->pluck('id')->all();

        $saved = 0;
        foreach ($rows as $levelId => $attrs) {
            if (! in_array((int) $levelId, $allowedIds, true)) {
                continue;
            }

            AttendanceSetting::updateOrCreate(
                ['school_id' => $schoolId, 'academic_level_id' => (int) $levelId],
                $this->normalize($attrs),
            );
            $saved++;
        }

        return $saved;
    }

    /* ------------------------------------------------------------------ */

    /**
     * Coerce raw form input into storable attributes. Unchecked checkboxes are
     * absent in the payload, so every method flag defaults to false.
     *
     * @param  array<string, mixed>  $attrs
     * @return array<string, mixed>
     */
    private function normalize(array $attrs): array
    {
        $daily = ($attrs['capture_mode'] ?? null) === AttendanceSetting::CAPTURE_DAILY;

        return [
            'capture_mode' => $daily ? AttendanceSetting::CAPTURE_DAILY : AttendanceSetting::CAPTURE_SESSION,
            'allow_manual' => ! empty($attrs['allow_manual']),
            'allow_qr' => ! empty($attrs['allow_qr']),
            'allow_biometric' => ! empty($attrs['allow_biometric']),
            'allow_facial' => ! empty($attrs['allow_facial']),
            'late_after' => $attrs['late_after'] ?? null ?: null,
            'grace_minutes' => (int) ($attrs['grace_minutes'] ?? 0),
            'half_day_enabled' => ! empty($attrs['half_day_enabled']),
            // Denominator only applies to daily capture; drop it for session.
            'expected_days_per_period' => $daily && ! empty($attrs['expected_days_per_period'])
                ? (int) $attrs['expected_days_per_period']
                : null,
            'qr_rotation_seconds' => max(3, (int) ($attrs['qr_rotation_seconds'] ?? 10)),
        ];
    }
}
