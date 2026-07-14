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
            ->get();
        $enrollByNode = $enrollments->keyBy('education_node_id');

        // Grade-level nodes to show: those with learning areas assigned, plus any
        // the learner attended or already has a permanent-record grade in — so
        // the record spans the full progression (Kinder → Grade 12) and a
        // transferee's earlier grade levels appear even without an enrollment.
        $attendedNodeIds = $enrollments->pluck('education_node_id')->filter()->map(fn ($v) => (int) $v)->all();
        $prNodeIds       = DB::table('permanent_record_grades')->where('student_id', $student->id)
            ->distinct()->pluck('education_node_id')->map(fn ($v) => (int) $v)->all();

        $nodeIds = DB::table('grade_level_subjects')->where('is_active', 1)->distinct()->pluck('education_node_id')
            ->merge($attendedNodeIds)
            ->merge($prNodeIds)
            ->filter()->unique()->values();

        // Keep the graded progression (Kinder → Grade 12), ranked by grade
        // number, plus any level the learner attended or has a record in. Drops
        // pre-Kinder/strand-duplicate nodes and orders them chronologically.
        $rankOf = function (?string $name): ?int {
            $n = strtolower(trim((string) $name));
            if (str_starts_with($n, 'kinder')) return 0;
            return preg_match('/grade\s+(\d+)/', $n, $m) ? (int) $m[1] : null;
        };

        $nodes = DB::table('education_nodes')->whereIn('id', $nodeIds)->get(['id', 'name', 'order_index'])
            ->map(function ($node) use ($rankOf, $attendedNodeIds, $prNodeIds) {
                $node->rank     = $rankOf($node->name);
                $node->required = in_array((int) $node->id, $attendedNodeIds, true) || in_array((int) $node->id, $prNodeIds, true);
                return $node;
            })
            ->filter(fn ($node) => $node->rank !== null || $node->required)
            ->sortBy([['rank', 'asc'], ['id', 'asc']])
            ->unique(fn ($node) => $node->rank ?? 'n'.$node->id)   // one node per grade rank
            ->values();

        // Permanent-record grades for this learner, keyed "node:subject".
        $prRows = DB::table('permanent_record_grades')->where('student_id', $student->id)->get()
            ->keyBy(fn ($r) => $r->education_node_id.':'.$r->subject_id);
        $prSubjects = $prRows->pluck('subject_id')->unique()->values()->isNotEmpty()
            ? DB::table('subjects')->whereIn('id', $prRows->pluck('subject_id')->unique()->values())->get(['id', 'name', 'code'])->keyBy('id')
            : collect();

        $sections = collect();

        foreach ($nodes as $node) {
            $nodeId = (int) $node->id;
            $enr    = $enrollByNode->get($nodeId);

            // Skeleton learning areas for this grade level.
            $skeleton = DB::table('grade_level_subjects as g')
                ->join('subjects as s', 's.id', '=', 'g.subject_id')
                ->where('g.education_node_id', $nodeId)
                ->where('g.is_active', 1)
                ->get(['s.id as subject_id', 's.name', 's.code']);

            // Enrolled-subject records at this grade level (fallback grade source).
            $ses = $enr ? DB::table('student_enrollment_subjects as ses')
                ->join('subjects as s', 's.id', '=', 'ses.subject_id')
                ->where('ses.student_enrollment_id', $enr->id)
                ->get(['ses.subject_id', 's.name', 's.code', 'ses.final_grade', 'ses.grade', 'ses.status', 'ses.remarks', 'ses.class_id'])
                ->keyBy('subject_id') : collect();

            // Union of subject ids: skeleton + enrolled + permanent-record for this node.
            $subjectList = collect();
            foreach ($skeleton as $sk) {
                $subjectList->put((int) $sk->subject_id, (object) ['subject_id' => (int) $sk->subject_id, 'name' => $sk->name, 'code' => $sk->code]);
            }
            foreach ($ses as $sid => $t) {
                if (! $subjectList->has((int) $sid)) {
                    $subjectList->put((int) $sid, (object) ['subject_id' => (int) $sid, 'name' => $t->name, 'code' => $t->code]);
                }
            }
            foreach ($prRows as $pr) {
                if ((int) $pr->education_node_id !== $nodeId || $subjectList->has((int) $pr->subject_id)) {
                    continue;
                }
                $su = $prSubjects->get($pr->subject_id);
                $subjectList->put((int) $pr->subject_id, (object) ['subject_id' => (int) $pr->subject_id, 'name' => $su->name ?? '—', 'code' => $su->code ?? '—']);
            }

            if ($subjectList->isEmpty()) {
                continue;
            }

            $teacherMap = $enr ? $this->teacherMap($enr, $ses->pluck('class_id')->filter()->all()) : ['by_class' => [], 'by_subject' => []];
            $sectionSy  = $prRows->first(fn ($pr) => (int) $pr->education_node_id === $nodeId && $pr->school_year)?->school_year;

            $rows = $subjectList->sortBy('name')->map(function ($su) use ($threshold, $nodeId, $ses, $prRows, $teacherMap) {
                $pr = $prRows->get($nodeId.':'.$su->subject_id);
                $t  = $ses->get($su->subject_id);

                // Grade source: the permanent record first, else the enrolled record.
                if ($pr) {
                    $final           = $pr->final_grade;
                    $status          = strtolower((string) ($pr->status ?? ''));
                    $transferredFrom = (string) ($pr->transferred_from ?? '');
                    $teacherName     = $pr->teacher_name ?: null;
                } elseif ($t) {
                    $final           = $t->final_grade ?? $t->grade;
                    $status          = strtolower((string) $t->status);
                    $transferredFrom = in_array($status, ['credit', 'credited', 'transferred'], true) ? (string) ($t->remarks ?? '') : '';
                    $teacherName     = null;
                } else {
                    $final = null; $status = ''; $transferredFrom = ''; $teacherName = null;
                }

                $num      = is_numeric($final) ? (float) $final : null;
                $isCredit = in_array($status, ['credit', 'credited', 'transferred'], true);

                $teacher = $teacherName
                    ?? ($t && $t->class_id ? ($teacherMap['by_class'][$t->class_id] ?? null) : null)
                    ?? ($teacherMap['by_subject'][$su->subject_id] ?? null);

                [$remark, $tone] = match (true) {
                    $isCredit                               => ['Credit', 'sky'],
                    $pr === null && $t === null             => ['Not Taken', 'slate'],
                    $num === null && $status === 'enrolled' => ['Ongoing', 'slate'],
                    $num === null                           => ['—', 'slate'],
                    $num >= $threshold                      => ['Passed', 'emerald'],
                    default                                 => ['Failed', 'rose'],
                };

                return [
                    'learning_area'     => $su->name,
                    'code'              => $su->code,
                    'teacher'           => $teacher ?: '—',
                    'final_grade'       => $isCredit ? 'Credit' : ($num !== null ? $this->fmt($num) : '—'),
                    'remark'            => $remark,
                    'tone'              => $tone,
                    '_num'              => $isCredit ? null : $num,   // credited subjects excluded from the average
                    // --- fields the registrar edit modal needs (writes the permanent record) ---
                    'subject_id'        => (int) $su->subject_id,
                    'education_node_id' => (int) $nodeId,
                    'status_raw'        => $status,
                    'grade_raw'         => $num,
                    'transferred_from'  => $transferredFrom,
                    'teacher_raw'       => $teacherName ?: '',
                ];
            })->values();

            $graded = $rows->filter(fn ($r) => $r['_num'] !== null);
            $ga     = $graded->isNotEmpty() ? round($graded->avg('_num'), 2) : null;
            $failed = $graded->contains(fn ($r) => $r['_num'] < $threshold);

            // Standing follows the Principal's promotion rule (Settings → Grades).
            $meetsAvg = $ga !== null && $ga >= $threshold;
            $promoted = $rule === GradeSetting::RULE_ALL_AREAS_PASS ? ($meetsAvg && ! $failed) : $meetsAvg;

            [$sectionRemark, $remarkTone] = match (true) {
                $graded->isEmpty() => ['In Progress', 'slate'],
                $promoted          => ['Promoted', 'emerald'],
                default            => ['Retained', 'rose'],
            };

            $syLabel = $sectionSy
                ? 'SY '.$sectionSy
                : ($enr && $enr->academicYear?->name ? 'SY '.$enr->academicYear->name : '—');

            $sections->push([
                'grade_label'     => $node->name,
                'sy_label'        => $syLabel,
                'node_id'         => $nodeId,
                'order'           => $node->rank ?? (900 + (int) $node->order_index),
                'attended'        => $enr !== null,
                'rows'            => $rows,
                'general_average' => $ga,
                'ga_display'      => $ga !== null ? $this->fmt($ga) : '—',
                'remark'          => $sectionRemark,
                'remark_tone'     => $remarkTone,
            ]);
        }

        // Chronological: earliest grade level → latest.
        $sections = $sections->sortBy('order')->values();

        // Summary headline = the learner's most recent attended grade level.
        $current = $sections->where('attended', true)->sortByDesc('order')->first() ?? $sections->last();

        return [
            'sections' => $sections,
            'summary'  => [
                'level'           => 'Basic Education',
                'current_grade'   => $current['grade_label'] ?? null,
                'general_average' => $current['general_average'] ?? null,
                'remark'          => $current['remark'] ?? null,
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
