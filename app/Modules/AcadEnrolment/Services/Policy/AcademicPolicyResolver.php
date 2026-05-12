<?php

namespace App\Modules\AcadEnrolment\Services\Policy;

use App\Models\AcademicPolicy;
use Illuminate\Support\Collection;

/**
 * Resolves the most-specific applicable AcademicPolicy for an enrolment.
 *
 * Resolution order (first match wins, all filtered to is_active = true and
 * within effective_from/effective_to if set):
 *   1. school + level + program + term
 *   2. school + level + program  (term IS NULL)
 *   3. school + level + term     (program IS NULL)
 *   4. school + level            (program IS NULL, term IS NULL)
 *   5. school                    (level IS NULL, program IS NULL, term IS NULL)
 *
 * If nothing matches, returns a synthetic default policy keyed on
 * education_level so callers never have to handle null.
 */
class AcademicPolicyResolver
{
    /** Cache resolved policies per request: key => AcademicPolicy */
    protected array $cache = [];

    public function resolve(int $schoolId, ?string $educationLevel, ?int $programId, ?int $termId): AcademicPolicy
    {
        $key = "{$schoolId}|{$educationLevel}|{$programId}|{$termId}";

        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $base = AcademicPolicy::query()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->where(function ($q) {
                $today = now()->toDateString();
                $q->whereNull('effective_from')->orWhere('effective_from', '<=', $today);
            })
            ->where(function ($q) {
                $today = now()->toDateString();
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $today);
            });

        $tries = [
            ['education_level' => $educationLevel, 'program_id' => $programId, 'term_id' => $termId],
            ['education_level' => $educationLevel, 'program_id' => $programId, 'term_id' => null],
            ['education_level' => $educationLevel, 'program_id' => null,        'term_id' => $termId],
            ['education_level' => $educationLevel, 'program_id' => null,        'term_id' => null],
            ['education_level' => null,            'program_id' => null,        'term_id' => null],
        ];

        foreach ($tries as $filter) {
            $q = clone $base;

            foreach ($filter as $col => $val) {
                if ($val === null) {
                    $q->whereNull($col);
                } else {
                    $q->where($col, $val);
                }
            }

            if ($policy = $q->orderByDesc('updated_at')->first()) {
                return $this->cache[$key] = $policy;
            }
        }

        return $this->cache[$key] = $this->defaultFor($schoolId, $educationLevel);
    }

    /**
     * Synthetic, in-memory default. Not persisted.
     */
    protected function defaultFor(int $schoolId, ?string $educationLevel): AcademicPolicy
    {
        // Defaults follow the architecture decisions:
        //   K-JHS  : payment NOT required
        //   SHS    : payment NOT required (configurable per school)
        //   Higher Ed (undergrad/grad): payment required, 20% min
        $isHigherEd = in_array($educationLevel, ['undergraduate', 'graduate'], true);

        $policy = new AcademicPolicy([
            'school_id'                   => $schoolId,
            'education_level'             => $educationLevel,
            'min_units'                   => $isHigherEd ? 12 : null,
            'max_units'                   => $isHigherEd ? 24 : null,
            'overload_threshold_units'    => $isHigherEd ? 21 : null,
            'requires_payment_to_enrol'   => $isHigherEd,
            'min_payment_percent'         => $isHigherEd ? 20 : 0,
            'is_active'                   => true,
        ]);

        $policy->exists = false; // mark as synthetic

        return $policy;
    }
}
