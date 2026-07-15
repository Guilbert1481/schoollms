<?php

namespace App\Services\Grading;

use App\Models\AcademicLevel;
use App\Models\GradingSetting;
use App\Support\AcademicBand;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Reads and writes per-level grading configuration for one role band (basic ed
 * for the Principal, higher ed for the Dean). The band is fixed by the route and
 * enforced here, so a crafted POST cannot reach the other role's levels.
 */
class GradingSettingsService
{
    /**
     * The band's levels, each paired with its saved grading scheme (with
     * components) or an unsaved model carrying defaults.
     *
     * @return Collection<int, array{level: AcademicLevel, setting: GradingSetting}>
     */
    public function levelsWithSettings(int $schoolId, string $band): Collection
    {
        $levels = AcademicBand::levels($schoolId, $band);

        $saved = GradingSetting::with('components')
            ->where('school_id', $schoolId)
            ->whereIn('academic_level_id', $levels->pluck('id'))
            ->get()
            ->keyBy('academic_level_id');

        return $levels->map(fn (AcademicLevel $level) => [
            'level' => $level,
            'setting' => $saved->get($level->id) ?? new GradingSetting(
                GradingSetting::defaults() + ['academic_level_id' => $level->id]
            ),
        ])->values();
    }

    /**
     * Persist the posted per-level schemes. Level ids outside this band are
     * ignored. Each scheme's components are replaced wholesale (config, low
     * volume) inside a transaction so a level never ends up half-updated.
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

            DB::transaction(function () use ($schoolId, $levelId, $attrs) {
                $setting = GradingSetting::updateOrCreate(
                    ['school_id' => $schoolId, 'academic_level_id' => (int) $levelId],
                    $this->normalizeSetting($attrs),
                );

                $this->syncComponents($setting, $attrs['components'] ?? []);
            });

            $saved++;
        }

        return $saved;
    }

    /* ------------------------------------------------------------------ */

    /** @param array<string, mixed> $attrs */
    private function normalizeSetting(array $attrs): array
    {
        $scale = $attrs['scale_type'] ?? GradingSetting::SCALE_PERCENTAGE;

        return [
            'scale_type' => in_array($scale, GradingSetting::SCALES, true) ? $scale : GradingSetting::SCALE_PERCENTAGE,
            'passing_mark' => (float) ($attrs['passing_mark'] ?? 75),
            'attendance_weight' => (float) ($attrs['attendance_weight'] ?? 0),
        ];
    }

    /**
     * Replace the setting's components with the posted rows, skipping blanks.
     *
     * @param  array<int|string, array<string, mixed>>  $components
     */
    private function syncComponents(GradingSetting $setting, array $components): void
    {
        $setting->components()->delete();

        $order = 0;
        foreach ($components as $c) {
            $name = trim((string) ($c['name'] ?? ''));
            if ($name === '') {
                continue; // the UI may submit empty rows
            }

            $setting->components()->create([
                'school_id' => $setting->school_id,
                'name' => $name,
                'weight' => (float) ($c['weight'] ?? 0),
                'sort_order' => $order++,
            ]);
        }
    }
}
