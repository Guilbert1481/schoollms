<?php

namespace App\Services\Scheduler;

use App\Models\Section;
use Illuminate\Support\Facades\DB;

/**
 * Auto-generates one Section per cohort for a given term:
 *  - higher ed: one per (program, year_level), from active program_subjects;
 *  - basic ed:  one per grade level (education_node), from active
 *    grade_level_subjects whose subject belongs to the school.
 *
 * Idempotent: existing sections (matched by cohort + term_id + name) are
 * skipped.
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

        // Basic ed: one cohort per grade level that has an active curriculum.
        $grades = DB::table('grade_level_subjects as gls')
            ->join('subjects as s', 's.id', '=', 'gls.subject_id')
            ->join('education_nodes as n', 'n.id', '=', 'gls.education_node_id')
            ->where('gls.is_active', 1)
            ->where('s.school_id', $schoolId)
            ->where('n.is_active', 1)
            ->select('gls.education_node_id', 'n.name as node_name')
            ->groupBy('gls.education_node_id', 'n.name')
            ->orderBy('gls.education_node_id')
            ->get();

        foreach ($grades as $g) {
            $codeBase = $this->gradeCode($g->node_name);

            for ($i = 0; $i < $sectionsPerCohort; $i++) {
                $letter = chr(65 + $i);
                $name = "{$codeBase}-{$letter}";

                $existing = Section::where('education_node_id', $g->education_node_id)
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
                    'education_node_id' => $g->education_node_id,
                    'term_id' => $termId,
                    'name' => $name,
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

    /** "Grade 5" → "G5"; "Grade 11 (Core)" → "G11CORE"; "Toddler" → "TODDLER". */
    protected function gradeCode(string $nodeName): string
    {
        if (preg_match('/grade\s*(\d+)(.*)/i', $nodeName, $m)) {
            $suffix = preg_replace('/[^A-Z0-9]+/', '', strtoupper($m[2]));

            return 'G'.$m[1].$suffix;
        }

        return $this->normalizeCode($nodeName);
    }

    protected function normalizeCode(string $code): string
    {
        $code = strtoupper($code);
        $code = preg_replace('/[^A-Z0-9]+/', '', $code);

        return $code ?: 'PROG';
    }
}
