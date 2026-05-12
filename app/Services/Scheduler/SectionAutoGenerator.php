<?php

namespace App\Services\Scheduler;

use App\Models\Section;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Auto-generates one Section per (program, year_level) cohort for a given
 * term, using the active program_subjects rows as the source of truth.
 *
 * Idempotent: existing sections (matched by program_id + year_level + term_id
 * + name) are skipped.
 */
class SectionAutoGenerator
{
    /**
     * @return array{created:int, skipped:int, sections:array<int,array>}
     */
    public function generateForTerm(int $termId, ?int $schoolId = null, int $sectionsPerCohort = 1, int $defaultCapacity = 40): array
    {
        $term = DB::table('terms')->where('id', $termId)->first();
        if (! $term) {
            return ['created' => 0, 'skipped' => 0, 'sections' => []];
        }

        $schoolId = $schoolId ?: $term->school_id;

        $cohorts = DB::table('program_subjects as ps')
            ->join('programs as p', 'p.id', '=', 'ps.program_id')
            ->where('ps.is_active', 1)
            ->where('p.school_id', $schoolId)
            ->select('ps.program_id', 'ps.year_level', 'p.code as program_code', 'p.name as program_name')
            ->groupBy('ps.program_id', 'ps.year_level', 'p.code', 'p.name')
            ->get();

        $created = 0;
        $skipped = 0;
        $generated = [];

        foreach ($cohorts as $c) {
            $codeBase = $this->normalizeCode($c->program_code ?: $c->program_name);

            for ($i = 0; $i < $sectionsPerCohort; $i++) {
                $letter = chr(65 + $i); // A, B, C...
                $name = sprintf('%s-%dY%s', $codeBase, $c->year_level, $letter);

                $existing = Section::where('program_id', $c->program_id)
                    ->where('year_level', $c->year_level)
                    ->where('term_id', $termId)
                    ->where('name', $name)
                    ->first();

                if ($existing) {
                    $skipped++;
                    $generated[] = $existing->toArray();
                    continue;
                }

                $section = Section::create([
                    'school_id' => $schoolId,
                    'program_id' => $c->program_id,
                    'term_id' => $termId,
                    'name' => $name,
                    'year_level' => $c->year_level,
                    'capacity' => $defaultCapacity,
                    'is_active' => 0,
                    'status' => 'draft',
                ]);

                $created++;
                $generated[] = $section->toArray();
            }
        }

        return [
            'created' => $created,
            'skipped' => $skipped,
            'sections' => $generated,
        ];
    }

    protected function normalizeCode(string $code): string
    {
        $code = strtoupper($code);
        $code = preg_replace('/[^A-Z0-9]+/', '', $code);
        return $code ?: 'PROG';
    }
}
