<?php

namespace App\Support;

use App\Models\AcademicLevel;
use Illuminate\Support\Collection;

/**
 * A "band" is the set of academic-level types a settings role governs: the
 * Principal owns basic ed, the Dean owns higher ed. The band is fixed by the
 * (role-gated) route, never by user input, so it is the boundary that keeps
 * each role's per-level configuration to its own levels.
 *
 * Single source of truth for the band → level-type mapping, shared by the
 * attendance and grading settings services.
 */
class AcademicBand
{
    public const BASIC = 'basic';

    public const HIGHER = 'higher';

    /** band → the academic_levels.type values it governs. */
    private const TYPES = [
        self::BASIC => ['basic'],
        self::HIGHER => ['higher'],
    ];

    public static function isValid(string $band): bool
    {
        return isset(self::TYPES[$band]);
    }

    /** @return array<int, string> */
    public static function types(string $band): array
    {
        return self::TYPES[$band] ?? [];
    }

    /**
     * The school's levels in this band, ordered as they appear to a user.
     * AcademicLevel has no BelongsToSchool scope, so filter school explicitly.
     *
     * @return Collection<int, AcademicLevel>
     */
    public static function levels(int $schoolId, string $band): Collection
    {
        return AcademicLevel::where('school_id', $schoolId)
            ->whereIn('type', self::types($band))
            ->orderBy('sequence_order')
            ->get();
    }
}
