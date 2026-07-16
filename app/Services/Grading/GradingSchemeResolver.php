<?php

namespace App\Services\Grading;

use App\Models\AcademicLevel;
use App\Models\ClassModel;
use App\Models\GradingSetting;
use Illuminate\Support\Facades\DB;

/**
 * Resolves which per-level grading scheme (GradingSetting + its components)
 * applies to a teaching context. The config is keyed on academic_levels (the
 * per-school level vocabulary), but a student's actual level is tracked as a
 * higher-ed year level or a basic-ed education node — so this bridges the two:
 *
 *   - higher ed:  a class's section year level → academic_level(higher, seq).
 *   - basic ed:   an education node's name      → academic_level(basic, name).
 *
 * Returns null when nothing matches. Callers MUST treat null as "no scheme" and
 * refuse to post grades — never guess a scheme, or a grade could land under the
 * wrong weights.
 */
class GradingSchemeResolver
{
    public function forClass(ClassModel $class): ?GradingSetting
    {
        $yearLevel = DB::table('sections')->where('id', $class->section_id)->value('year_level');

        if ($yearLevel === null) {
            return null;
        }

        return $this->scheme((int) $class->school_id, 'higher', sequence: (int) $yearLevel);
    }

    public function forNode(int $schoolId, int $educationNodeId): ?GradingSetting
    {
        $name = DB::table('education_nodes')->where('id', $educationNodeId)->value('name');

        if (! $name) {
            return null;
        }

        return $this->scheme($schoolId, 'basic', name: (string) $name);
    }

    /* ------------------------------------------------------------------ */

    private function scheme(int $schoolId, string $type, ?int $sequence = null, ?string $name = null): ?GradingSetting
    {
        // AcademicLevel has no BelongsToSchool scope — filter school explicitly.
        $level = AcademicLevel::where('school_id', $schoolId)
            ->where('type', $type)
            ->when($sequence !== null, fn ($q) => $q->where('sequence_order', $sequence))
            ->when($name !== null, fn ($q) => $q->where('name', $name))
            ->first();

        if (! $level) {
            return null;
        }

        return GradingSetting::with('components')
            ->where('school_id', $schoolId)
            ->where('academic_level_id', $level->id)
            ->first();
    }
}
