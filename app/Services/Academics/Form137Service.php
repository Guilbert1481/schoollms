<?php

namespace App\Services\Academics;

use App\Models\Student;
use App\Models\StudentEnrollment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Builds the basic-education Form 137 (Learner's Permanent Academic Record) —
 * the basic-ed counterpart of the higher-ed Transcript of Records.
 *
 * Where the TOR groups Year → Semester with unit-weighted GWA, Form 137 groups
 * by GRADE LEVEL (Kinder, Grade 1..12) — one section per grade level + school
 * year — listing each learning area's recorded FINAL grade, a simple-mean
 * General Average, and a Promoted / Retained remark. (Per-term / quarter grades
 * live on the separate Report Card, not here.) See ADR-0006 for the level model.
 */
class Form137Service
{
    /** DepEd passing mark. TODO: read from the Principal's Grades settings once that page exists. */
    public const PASSING_GRADE = 75.0;

    /** Enrollment statuses that count as an attended grade level. */
    private const ATTENDED = ['enrolled', 'provisionally_enrolled', 'completed'];

    /** Is this student's record a basic-education one (drives Form 137 vs TOR)? */
    public function isBasicEd(Student $student): bool
    {
        $enrollment = StudentEnrollment::where('student_id', $student->id)
            ->latest('id')
            ->first();

        if (! $enrollment) {
            return false;
        }

        $termLevel = DB::table('terms')->where('id', $enrollment->term_id)->value('education_level');
        if ($termLevel) {
            return strtolower((string) $termLevel) === 'basic_ed';
        }

        return in_array(
            strtolower((string) $enrollment->education_level),
            ['kinder', 'elementary', 'junior_high', 'senior_high', 'basic_ed', 'basic'],
            true
        );
    }

    /**
     * @return array{
     *   sections: \Illuminate\Support\Collection,
     *   summary: array{level:string, current_grade:?string, general_average:?float, remark:?string, grade_levels:int}
     * }
     */
    public function build(Student $student): array
    {
        $threshold = self::PASSING_GRADE;

        $enrollments = StudentEnrollment::with('academicYear:id,name')
            ->where('student_id', $student->id)
            ->whereIn('status', self::ATTENDED)
            ->orderBy('year_level')
            ->orderBy('academic_year_id')
            ->get();

        $sections = collect();

        foreach ($enrollments as $enr) {
            $gradeLabel = $this->gradeLabel($enr);
            $syLabel    = $enr->academicYear?->name ? 'SY '.$enr->academicYear->name : '—';

            $subjects = DB::table('student_enrollment_subjects as ses')
                ->join('subjects as s', 's.id', '=', 'ses.subject_id')
                ->where('ses.student_enrollment_id', $enr->id)
                ->orderBy('s.name')
                ->get([
                    'ses.id as enrollment_subject_id',
                    'ses.subject_id',
                    's.name as learning_area',
                    's.code as code',
                    'ses.final_grade',
                    'ses.grade',
                    'ses.status',
                    'ses.remarks',
                ]);

            if ($subjects->isEmpty()) {
                continue;
            }

            $rows = $subjects->map(function ($r) use ($threshold, $enr) {
                $final  = $r->final_grade ?? $r->grade;
                $num    = is_numeric($final) ? (float) $final : null;
                $status = strtolower((string) $r->status);
                $isCredit = in_array($status, ['credit', 'credited', 'transferred'], true);

                [$remark, $tone] = match (true) {
                    $isCredit                        => ['Credit', 'sky'],
                    $num === null && $status === 'enrolled' => ['Ongoing', 'slate'],
                    $num === null                    => ['—', 'slate'],
                    $num >= $threshold               => ['Passed', 'emerald'],
                    default                          => ['Failed', 'rose'],
                };

                return [
                    'learning_area'         => $r->learning_area,
                    'code'                  => $r->code,
                    'final_grade'           => $isCredit ? 'Credit' : ($num !== null ? $this->fmt($num) : '—'),
                    'remark'                => $remark,
                    'tone'                  => $tone,
                    '_num'                  => $isCredit ? null : $num,   // credited units are excluded from the average
                    // --- fields the registrar edit modal needs ---
                    'subject_id'            => (int) $r->subject_id,
                    'enrollment_subject_id' => (int) $r->enrollment_subject_id,
                    'enrollment_id'         => (int) $enr->id,
                    'status_raw'            => $status,
                    'grade_raw'             => $num,
                    'transferred_from'      => $isCredit ? (string) ($r->remarks ?? '') : '',
                ];
            })->values();

            $graded = $rows->filter(fn ($r) => $r['_num'] !== null);
            $ga     = $graded->isNotEmpty() ? round($graded->avg('_num'), 2) : null;

            // Standing tracks the General Average. Individual failed learning
            // areas still show a "Failed" remark; whether a failed area forces
            // remediation/retention is a school policy the Principal's Grades
            // settings will govern (decision #3) — not hardcoded here.
            [$sectionRemark, $remarkTone] = match (true) {
                $graded->isEmpty()   => ['In Progress', 'slate'],
                $ga >= $threshold    => ['Promoted', 'emerald'],
                default              => ['Retained', 'rose'],
            };

            $sections->push([
                'grade_label'    => $gradeLabel,
                'sy_label'       => $syLabel,
                'year_level'     => (int) $enr->year_level,
                'rows'           => $rows,
                'general_average'=> $ga,
                'ga_display'     => $ga !== null ? $this->fmt($ga) : '—',
                'remark'         => $sectionRemark,
                'remark_tone'    => $remarkTone,
            ]);
        }

        // Newest grade level first (most recent record on top).
        $sections = $sections->sortByDesc('year_level')->values();

        $latest = $sections->first();

        return [
            'sections' => $sections,
            'summary'  => [
                'level'           => 'Basic Education',
                'current_grade'   => $latest['grade_label'] ?? null,
                'general_average' => $latest['general_average'] ?? null,
                'remark'          => $latest['remark'] ?? null,
                'grade_levels'    => $sections->count(),
            ],
        ];
    }

    /** "Grade 5" / "Kindergarten" from the enrollment's education node, else "Grade N". */
    private function gradeLabel(StudentEnrollment $enr): string
    {
        if ($enr->education_node_id) {
            $name = DB::table('education_nodes')->where('id', $enr->education_node_id)->value('name');
            if ($name) {
                return (string) $name;
            }
        }

        $yl = (int) $enr->year_level;
        return $yl > 0 ? 'Grade '.$yl : 'Kinder';
    }

    /** Trim trailing zeros: 88.00 -> 88, 87.50 -> 87.5. */
    private function fmt(float $n): string
    {
        return rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
    }
}
