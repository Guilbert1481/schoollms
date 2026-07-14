<?php

namespace App\Services\Academics;

use App\Models\GradeSetting;
use App\Models\Student;
use App\Models\StudentEnrollment;
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
    /** Fallback passing mark when a school has no Grade settings row yet. */
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
        // Passing threshold + promotion rule come from the Principal's Grades
        // settings (Settings → Grades); default to 75 / average.
        $setting   = GradeSetting::forSchool((int) $student->school_id);
        $threshold = (float) $setting->passing_threshold;
        $rule      = $setting->promotion_rule;

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

            // Skeleton: every learning area assigned to this grade level (like the
            // TOR shows the whole curriculum), so all subjects appear regardless
            // of whether a grade has been recorded yet.
            $skeleton = $enr->education_node_id
                ? DB::table('grade_level_subjects as g')
                    ->join('subjects as s', 's.id', '=', 'g.subject_id')
                    ->where('g.education_node_id', $enr->education_node_id)
                    ->where('g.is_active', 1)
                    ->get(['s.id as subject_id', 's.name', 's.code'])
                : collect();

            // The learner's actual recorded subjects for this enrollment.
            $taken = DB::table('student_enrollment_subjects as ses')
                ->join('subjects as s', 's.id', '=', 'ses.subject_id')
                ->where('ses.student_enrollment_id', $enr->id)
                ->get([
                    'ses.id as esid', 'ses.subject_id', 's.name', 's.code',
                    'ses.final_grade', 'ses.grade', 'ses.status', 'ses.remarks', 'ses.class_id',
                ])
                ->keyBy('subject_id');

            // Union: skeleton learning areas first, then any recorded subject not
            // in the skeleton (e.g. credited transfers), keyed by subject id.
            $subjectList = collect();
            foreach ($skeleton as $sk) {
                $subjectList->put($sk->subject_id, (object) ['subject_id' => $sk->subject_id, 'name' => $sk->name, 'code' => $sk->code]);
            }
            foreach ($taken as $sid => $t) {
                if (! $subjectList->has($sid)) {
                    $subjectList->put($sid, (object) ['subject_id' => $sid, 'name' => $t->name, 'code' => $t->code]);
                }
            }

            if ($subjectList->isEmpty()) {
                continue;
            }

            $teacherMap = $this->teacherMap($enr, $taken->pluck('class_id')->filter()->all());

            $rows = $subjectList->sortBy('name')->map(function ($su) use ($threshold, $enr, $taken, $teacherMap) {
                $t        = $taken->get($su->subject_id);
                $final    = $t ? ($t->final_grade ?? $t->grade) : null;
                $num      = is_numeric($final) ? (float) $final : null;
                $status   = strtolower((string) ($t->status ?? ''));
                $isCredit = in_array($status, ['credit', 'credited', 'transferred'], true);

                [$remark, $tone] = match (true) {
                    $isCredit                              => ['Credit', 'sky'],
                    $t === null                            => ['Not Taken', 'slate'],
                    $num === null && $status === 'enrolled' => ['Ongoing', 'slate'],
                    $num === null                          => ['—', 'slate'],
                    $num >= $threshold                     => ['Passed', 'emerald'],
                    default                                => ['Failed', 'rose'],
                };

                // Teacher (historical reference): prefer the exact class the
                // learner took the subject in, else any class for this section.
                $teacher = ($t && $t->class_id ? ($teacherMap['by_class'][$t->class_id] ?? null) : null)
                    ?? ($teacherMap['by_subject'][$su->subject_id] ?? null);

                return [
                    'learning_area'         => $su->name,
                    'code'                  => $su->code,
                    'teacher'               => $teacher ?: '—',
                    'final_grade'           => $isCredit ? 'Credit' : ($num !== null ? $this->fmt($num) : '—'),
                    'remark'                => $remark,
                    'tone'                  => $tone,
                    '_num'                  => $isCredit ? null : $num,   // credited subjects excluded from the average
                    // --- fields the registrar edit modal needs ---
                    'subject_id'            => (int) $su->subject_id,
                    'enrollment_subject_id' => $t ? (int) $t->esid : 0,
                    'enrollment_id'         => (int) $enr->id,
                    'status_raw'            => $status ?: 'enrolled',
                    'grade_raw'             => $num,
                    'transferred_from'      => $isCredit ? (string) ($t->remarks ?? '') : '',
                ];
            })->values();

            $graded = $rows->filter(fn ($r) => $r['_num'] !== null);
            $ga     = $graded->isNotEmpty() ? round($graded->avg('_num'), 2) : null;
            $failed = $graded->contains(fn ($r) => $r['_num'] < $threshold);

            // Standing follows the Principal's promotion rule (Settings → Grades):
            //   'average'        → Promoted when GA meets the threshold
            //   'all_areas_pass' → also requires no failed learning area
            $meetsAvg  = $ga !== null && $ga >= $threshold;
            $promoted  = $rule === GradeSetting::RULE_ALL_AREAS_PASS
                ? ($meetsAvg && ! $failed)
                : $meetsAvg;

            [$sectionRemark, $remarkTone] = match (true) {
                $graded->isEmpty() => ['In Progress', 'slate'],
                $promoted          => ['Promoted', 'emerald'],
                default            => ['Retained', 'rose'],
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

    /**
     * Teacher lookup for a grade level: the classes of the learner's section
     * (and any class they actually took a subject in) → subject/class → name.
     *
     * @return array{by_class: array<int,string>, by_subject: array<int,string>}
     */
    private function teacherMap(StudentEnrollment $enr, array $takenClassIds): array
    {
        $rows = DB::table('classes as c')
            ->leftJoin('users as u', 'u.id', '=', 'c.teacher_id')
            ->where(function ($w) use ($enr, $takenClassIds) {
                $has = false;
                if ($enr->section_id) { $w->orWhere('c.section_id', $enr->section_id); $has = true; }
                if (! empty($takenClassIds)) { $w->orWhereIn('c.id', $takenClassIds); $has = true; }
                if (! $has) { $w->whereRaw('1 = 0'); }
            })
            ->get([
                'c.id as class_id', 'c.subject_id',
                DB::raw("TRIM(CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,''))) as tname"),
            ]);

        $byClass = [];
        $bySubject = [];
        foreach ($rows as $r) {
            $name = trim((string) $r->tname);
            if ($name === '') {
                continue;
            }
            $byClass[(int) $r->class_id]   = $name;
            $bySubject[(int) $r->subject_id] = $name;
        }

        return ['by_class' => $byClass, 'by_subject' => $bySubject];
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
