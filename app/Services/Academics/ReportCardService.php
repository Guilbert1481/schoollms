<?php

namespace App\Services\Academics;

use App\Models\GradeSetting;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Illuminate\Support\Facades\DB;

/**
 * Builds the Report Card for the CURRENT period:
 *  - basic ed  → the current school year broken down by grading period
 *                (1st / 2nd / 3rd grading), per learning area, from
 *                report_card_grades; the year's final = average of periods.
 *  - higher ed → the current academic year + term (semester), the enrolled
 *                subjects and their grade, from student_enrollment_subjects.
 *
 * The Form 137 / TOR hold the all-time record; the Report Card is the snapshot
 * of the current period.
 */
class ReportCardService
{
    private const ENROLLED = ['enrolled', 'provisionally_enrolled', 'completed'];

    public function build(Student $student): array
    {
        $setting = GradeSetting::forSchool((int) $student->school_id);
        $threshold = (float) $setting->passing_threshold;

        return app(Form137Service::class)->isBasicEd($student)
            ? $this->buildBasicEd($student, $threshold, $setting->periods())
            : $this->buildHigherEd($student, $threshold);
    }

    /* ------------------------------------------------------------------ */

    private function buildBasicEd(Student $student, float $threshold, array $periods): array
    {
        // The school's configured grading periods (ordinal => name), e.g.
        // [1 => '1st Quarter', …]. This drives the columns, the labels, AND the
        // averaged set — so every configured period (incl. the 4th quarter) is
        // counted in each subject Final, the period averages, and the GA.
        $ordinals = array_keys($periods);

        $enr = StudentEnrollment::with('academicYear:id,name')
            ->where('student_id', $student->id)
            ->whereIn('status', self::ENROLLED)
            ->latest('id')
            ->first();

        $base = [
            'is_basic' => true,
            'has_context' => false,
            'period_labels' => array_values($periods),
            'periods' => $ordinals,
            'rows' => collect(),
            'period_averages' => [],
            'general_average' => null,
            'remark' => null,
        ];

        if (! $enr || ! $enr->education_node_id) {
            return $base;
        }

        $nodeId = (int) $enr->education_node_id;
        $ayId = $enr->academic_year_id;
        $gradeLabel = DB::table('education_nodes')->where('id', $nodeId)->value('name') ?: 'Current Grade Level';
        $ayLabel = $enr->academicYear?->name ? 'SY '.$enr->academicYear->name : '—';

        $areas = DB::table('grade_level_subjects as g')
            ->join('subjects as s', 's.id', '=', 'g.subject_id')
            ->where('g.education_node_id', $nodeId)
            ->where('g.is_active', 1)
            ->orderBy('s.name')
            ->get(['s.id as subject_id', 's.name', 's.code']);

        if ($areas->isEmpty()) {
            return $base;
        }

        // Per-period grades, keyed "subject:period".
        $rcg = DB::table('report_card_grades')
            ->where('student_id', $student->id)
            ->where('education_node_id', $nodeId)
            ->when($ayId, fn ($q) => $q->where('academic_year_id', $ayId))
            ->get(['subject_id', 'grading_period', 'final_grade'])
            ->keyBy(fn ($r) => $r->subject_id.':'.$r->grading_period);

        $periodSums = array_fill_keys($ordinals, ['sum' => 0.0, 'n' => 0]);
        $finals = [];

        $rows = $areas->map(function ($a) use ($rcg, $threshold, $nodeId, $ordinals, &$periodSums, &$finals) {
            $cells = [];
            $present = [];
            foreach ($ordinals as $p) {
                $g = $rcg->get($a->subject_id.':'.$p);
                $num = $g && is_numeric($g->final_grade) ? (float) $g->final_grade : null;
                if ($num !== null) {
                    $present[] = $num;
                    $periodSums[$p]['sum'] += $num;
                    $periodSums[$p]['n']++;
                }
                $cells[$p] = [
                    'display' => $num !== null ? $this->fmt($num) : '—',
                    'grade_raw' => $num,
                    'tone' => $num === null ? 'slate' : ($num >= $threshold ? 'emerald' : 'rose'),
                ];
            }

            $final = count($present) ? round(array_sum($present) / count($present), 2) : null;
            if ($final !== null) {
                $finals[] = $final;
            }

            return [
                'learning_area' => $a->name,
                'code' => $a->code,
                'subject_id' => (int) $a->subject_id,
                'education_node_id' => $nodeId,
                'cells' => $cells,
                'final' => $final !== null ? $this->fmt($final) : '—',
                'final_tone' => $final === null ? 'slate' : ($final >= $threshold ? 'emerald' : 'rose'),
                'final_remark' => $final === null ? '—' : ($final >= $threshold ? 'Passed' : 'Failed'),
            ];
        })->values();

        $periodAverages = [];
        foreach ($ordinals as $p) {
            $periodAverages[$p] = $periodSums[$p]['n']
                ? $this->fmt(round($periodSums[$p]['sum'] / $periodSums[$p]['n'], 2))
                : '—';
        }

        $ga = count($finals) ? round(array_sum($finals) / count($finals), 2) : null;

        return array_merge($base, [
            'has_context' => true,
            'ay_label' => $ayLabel,
            'grade_label' => $gradeLabel,
            'academic_year_id' => $ayId,
            'rows' => $rows,
            'period_averages' => $periodAverages,
            'general_average' => $ga,
            'ga_display' => $ga !== null ? $this->fmt($ga) : '—',
            'remark' => $ga === null ? 'In Progress' : ($ga >= $threshold ? 'Promoted' : 'Retained'),
        ]);
    }

    /* ------------------------------------------------------------------ */

    private function buildHigherEd(Student $student, float $threshold): array
    {
        $enr = StudentEnrollment::with(['academicYear:id,name', 'term:id,name'])
            ->where('student_id', $student->id)
            ->whereIn('status', self::ENROLLED)
            ->latest('id')
            ->first();

        $base = [
            'is_basic' => false,
            'has_context' => false,
            'rows' => collect(),
            'gwa' => null,
        ];

        if (! $enr) {
            return $base;
        }

        $rows = DB::table('student_enrollment_subjects as ses')
            ->join('subjects as s', 's.id', '=', 'ses.subject_id')
            ->leftJoin('classes as c', 'c.id', '=', 'ses.class_id')
            ->leftJoin('users as u', 'u.id', '=', 'c.teacher_id')
            ->where('ses.student_enrollment_id', $enr->id)
            ->orderBy('s.name')
            ->get([
                's.name as subject', 's.code', 's.units', 'ses.final_grade', 'ses.grade', 'ses.status',
                DB::raw("TRIM(CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,''))) as teacher"),
            ])
            ->map(function ($r) use ($threshold) {
                $final = $r->final_grade ?? $r->grade;
                $num = is_numeric($final) ? (float) $final : null;
                $status = strtolower((string) $r->status);
                $isCredit = in_array($status, ['credit', 'credited', 'transferred'], true);

                [$remark, $tone] = match (true) {
                    $isCredit => ['Credit', 'sky'],
                    $num === null && $status === 'enrolled' => ['Ongoing', 'slate'],
                    $num === null => ['—', 'slate'],
                    $num >= $threshold => ['Passed', 'emerald'],
                    default => ['Failed', 'rose'],
                };

                return [
                    'subject' => $r->subject,
                    'code' => $r->code,
                    'teacher' => trim((string) $r->teacher) ?: '—',
                    'units' => $r->units > 0 ? $this->fmt((float) $r->units) : '—',
                    'grade' => $isCredit ? 'Credit' : ($num !== null ? $this->fmt($num) : '—'),
                    'remark' => $remark,
                    'tone' => $tone,
                    '_num' => $isCredit ? null : $num,
                    '_units' => (float) ($r->units ?? 0),
                ];
            })->values();

        $weighted = $rows->filter(fn ($r) => $r['_num'] !== null && $r['_units'] > 0);
        $weightSum = (float) $weighted->sum('_units');
        $gwa = $weightSum > 0
            ? round($weighted->sum(fn ($r) => $r['_num'] * $r['_units']) / $weightSum, 2)
            : null;

        return array_merge($base, [
            'has_context' => true,
            'ay_label' => $enr->academicYear?->name ? 'SY '.$enr->academicYear->name : '—',
            'term_label' => $enr->term?->name ?: '—',
            'rows' => $rows,
            'gwa' => $gwa,
        ]);
    }

    private function fmt(float $n): string
    {
        return rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
    }
}
