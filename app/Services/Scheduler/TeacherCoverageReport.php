<?php

namespace App\Services\Scheduler;

use Illuminate\Support\Facades\DB;

/**
 * Audits whether every active program_subject has at least one qualified
 * teacher and surfaces gaps for the Scheduler / Dean dashboards.
 */
class TeacherCoverageReport
{
    /**
     * @return array{
     *   has_gaps: bool,
     *   total_active: int,
     *   uncovered_count: int,
     *   missing: array<int,array{program:string, year_level:int, semester:int, code:string, name:string}>
     * }
     */
    public function generate(?int $schoolId = null): array
    {
        $q = DB::table('program_subjects as ps')
            ->join('programs as p', 'p.id', '=', 'ps.program_id')
            ->join('subjects as s', 's.id', '=', 'ps.subject_id')
            ->leftJoin('teacher_subjects as ts', 'ts.subject_id', '=', 'ps.subject_id')
            ->where('ps.is_active', 1);

        if ($schoolId) {
            $q->where('p.school_id', $schoolId);
        }

        $rows = $q->select(
                'ps.id',
                'ps.program_id',
                'ps.year_level',
                'ps.semester_number',
                'ps.subject_id',
                'p.code as program_code',
                'p.name as program_name',
                's.code as subject_code',
                's.name as subject_name',
                DB::raw('COUNT(ts.id) as teacher_count')
            )
            ->groupBy(
                'ps.id', 'ps.program_id', 'ps.year_level', 'ps.semester_number',
                'ps.subject_id', 'p.code', 'p.name', 's.code', 's.name'
            )
            ->orderBy('p.code')
            ->orderBy('ps.year_level')
            ->orderBy('ps.semester_number')
            ->orderBy('s.code')
            ->get();

        $missing = [];
        foreach ($rows as $r) {
            if ((int) $r->teacher_count === 0) {
                $missing[] = [
                    'program' => $r->program_code ?: $r->program_name,
                    'year_level' => (int) $r->year_level,
                    'semester' => (int) $r->semester_number,
                    'code' => $r->subject_code,
                    'name' => $r->subject_name,
                ];
            }
        }

        return [
            'has_gaps' => ! empty($missing),
            'total_active' => $rows->count(),
            'uncovered_count' => count($missing),
            'missing' => $missing,
        ];
    }
}
