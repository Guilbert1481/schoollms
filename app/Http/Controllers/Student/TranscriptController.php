<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\StudentEnrollment;
use App\Models\StudentEnrollmentSubject;
use App\Services\Academics\Form137Service;
use App\Services\Academics\ReportCardService;
use Illuminate\Support\Facades\Auth;

class TranscriptController extends Controller
{
    /**
     * Build the student's permanent transcript.
     *
     * Layout:
     *   - Pull every subject from the active curriculum of the student's
     *     latest enrolled program.
     *   - Merge each curriculum row with the student's actual taken record
     *     (StudentEnrollmentSubject) when one exists.
     *   - Group rows by year_level, then by semester so the view can render
     *     "YEAR 1", "1st Sem", "2nd Sem" separators between groups.
     */
    public function index()
    {
        $user    = Auth::user();
        $student = $user?->student ?? \App\Models\Student::where('user_id', $user?->id)->first();

        $columns = config('tables.transcript.columns', []);

        $blank = [
            'columns'        => $columns,
            'groups'         => collect(),
            'programLabel'   => '—',
            'totalUnits'     => 0,
            'requiredUnits'  => 0,
            'percentDone'    => 0,
            'gwa'            => null,
            'report'         => $student ? app(ReportCardService::class)->build($student) : null,
            'rcEditable'     => false,
        ];

        if (! $student) {
            return view('student.transcript.index', $blank);
        }

        // Basic-ed learners get Form 137 (grade-level record), not the
        // higher-ed Year/Semester transcript. Enforces the level separation.
        $form137 = app(Form137Service::class);
        if ($form137->isBasicEd($student)) {
            $data = $form137->build($student);

            return view('transcript.form137', [
                'student'    => $student,
                'sections'   => $data['sections'],
                'summary'    => $data['summary'],
                'backUrl'    => route('student.dashboard'),
                'backLabel'  => 'Back to Dashboard',
                'editable'   => false,   // students view their record read-only
                'report'     => app(ReportCardService::class)->build($student),
                'rcEditable' => false,
            ]);
        }

        $latestEnrollment = StudentEnrollment::with('program:id,code,name')
            ->where('student_id', $student->id)
            ->latest('id')
            ->first();

        $programLabel = $latestEnrollment?->program
            ? trim(($latestEnrollment->program->code ?? '').' '.$latestEnrollment->program->name)
            : '—';

        $curriculum = $latestEnrollment?->program_id
            ? Curriculum::where('program_id', $latestEnrollment->program_id)
                ->where('is_active', true)
                ->orderByDesc('version')
                ->first()
            : null;
        $curriculumId = $curriculum?->id;

        if (! $curriculumId) {
            return view('student.transcript.index', array_merge($blank, [
                'programLabel' => $programLabel,
            ]));
        }

        // Determine the school's term structure so we can render every
        // semester (1st..Nth) even when no subjects are scheduled for it yet.
        $termsPerYear  = (int) ($curriculum->terms_per_year ?: 2);
        $hasSummerTerm = (bool) ($curriculum->has_summer_term ?? false);
        $expectedSems  = range(1, max(1, $termsPerYear));
        if ($hasSummerTerm && ! in_array(3, $expectedSems, true)) {
            $expectedSems[] = 3; // legacy "summer = sem 3" convention for bi-sem
        }

        // Curriculum subjects = the master plan (every subject the student is
        // expected to take, with year/semester/units).
        //
        // Source of truth resolution:
        //   1. Prefer `program_subjects` — the table the Dean actively
        //      maintains via the "Program Subjects" panel. Units are copied
        //      from the master `subjects` table since program_subjects
        //      doesn't store its own units value.
        //   2. Fall back to the legacy `curriculum_subjects` rows only when
        //      the Dean hasn't populated the program panel yet.
        $curriculumRows = collect();
        if ($latestEnrollment?->program_id) {
            $curriculumRows = \Illuminate\Support\Facades\DB::table('program_subjects as ps')
                ->join('subjects as s', 's.id', '=', 'ps.subject_id')
                ->where('ps.program_id', $latestEnrollment->program_id)
                ->orderBy('ps.year_level')
                ->orderBy('ps.semester_number')
                ->get([
                    'ps.id',
                    'ps.subject_id',
                    'ps.year_level',
                    'ps.semester_number as semester',
                    's.units',
                    's.code as subject_code',
                    's.name as subject_name',
                ])
                ->map(fn ($r) => (object) [
                    'id'         => $r->id,
                    'subject_id' => $r->subject_id,
                    'year_level' => $r->year_level,
                    'semester'   => $r->semester,
                    'units'      => $r->units,
                    'subject'    => (object) [
                        'id'   => $r->subject_id,
                        'code' => $r->subject_code,
                        'name' => $r->subject_name,
                    ],
                ]);
        }

        if ($curriculumRows->isEmpty()) {
            $curriculumRows = CurriculumSubject::with('subject:id,code,name,units')
                ->where('curriculum_id', $curriculumId)
                ->orderBy('year_level')
                ->orderBy('semester')
                ->get();
        }

        // The student's actual taken records, keyed by subject_id (latest wins
        // if a subject was retaken, so the up-to-date grade always shows).
        $taken = StudentEnrollmentSubject::query()
            ->with([
                'enrollment:id,student_id,term_id,academic_year_id',
                'enrollment.term:id,name,term,academic_year_id',
                'enrollment.academicYear:id,name',
            ])
            ->whereHas('enrollment', fn ($q) => $q->where('student_id', $student->id))
            ->get()
            ->sortByDesc('id')
            ->keyBy('subject_id');

        // Teacher (historical reference) per subject, via the class the student
        // took it in.
        $teacherByClass = \Illuminate\Support\Facades\DB::table('classes as c')
            ->leftJoin('users as u', 'u.id', '=', 'c.teacher_id')
            ->whereIn('c.id', $taken->pluck('class_id')->filter()->unique()->values())
            ->get()
            ->mapWithKeys(fn ($c) => [(int) $c->id => trim(($c->first_name ?? '').' '.($c->last_name ?? ''))])
            ->all();

        $requiredUnits = (float) $curriculumRows->sum('units');

        $rows = $curriculumRows->map(function ($cs) use ($taken, $termsPerYear, $teacherByClass) {
            $subj    = $cs->subject;
            $units   = (float) ($cs->units ?? 0);
            $sem     = (int) $cs->semester;
            $year    = (int) $cs->year_level;
            $semWord = self::semesterLabel($sem, $termsPerYear);

            $record  = $taken->get($cs->subject_id);
            $grade   = $record?->grade;
            $status  = strtolower((string) ($record->status ?? ''));
            $teacher = ($record && $record->class_id ? ($teacherByClass[(int) $record->class_id] ?? '') : '') ?: '—';

            $isCredit  = in_array($status, ['credit', 'credited', 'transferred']);
            $isFailed  = $status === 'failed' || (is_numeric($grade) && self::isFailing((float) $grade));
            $isPassed  = ! $isCredit && ! $isFailed && in_array($status, ['passed', 'completed']) && is_numeric($grade);
            $isOngoing = ! $isCredit && ! $isFailed && ! $isPassed && $status === 'enrolled';
            $finished  = $isCredit || $isFailed || $isPassed;

            // Term column: bottom AY line shown only when subject is actually
            // finished. Otherwise just the curriculum semester is displayed.
            $ayName = $record?->enrollment?->term?->academicYear?->name;
            $termTopHtml    = '<div style="font-weight:700;color:#1e293b;">'.$semWord.'</div>';
            $termBottomHtml = $finished && $ayName
                ? '<div style="font-size:11px;color:#64748b;margin-top:2px;">'.e($ayName).'</div>'
                : '';
            $termHtml = '<div style="line-height:1.2;">'.$termTopHtml.$termBottomHtml.'</div>';

            // Final-grade colouring (green pass / red fail / blue credit).
            $gradeColor = $isCredit ? '#2563eb' : ($isFailed ? '#dc2626' : ($isPassed ? '#16a34a' : '#94a3b8'));
            $gradeText  = $isCredit
                ? 'Credit'
                : ($grade !== null ? rtrim(rtrim(number_format((float) $grade, 2, '.', ''), '0'), '.') : '—');
            $gradeHtml  = '<span style="font-weight:700;color:'.$gradeColor.';">'.$gradeText.'</span>';

            [$statusLabel, $statusBg, $statusFg] = match (true) {
                $isCredit  => ['Credit', '#dbeafe', '#1d4ed8'],
                $isFailed  => ['Failed', '#fee2e2', '#b91c1c'],
                $isPassed  => ['Passed', '#dcfce7', '#15803d'],
                $isOngoing => ['Ongoing', '#fef9c3', '#a16207'],
                default    => ['Not Taken', '#f1f5f9', '#475569'],
            };
            $statusHtml = '<span style="display:inline-block;padding:2px 10px;border-radius:9999px;font-size:11px;font-weight:700;background:'.$statusBg.';color:'.$statusFg.';">'.$statusLabel.'</span>';

            return (object) [
                'id'                => $cs->id,
                'year_level'        => $year,
                'semester'          => $sem,
                'term'              => $termHtml,
                'subject_code'      => $subj->code ?? '—',
                'descriptive_title' => $subj->name ?? '—',
                'teacher'           => $teacher,
                'final_grade'       => $gradeHtml,
                'units'             => $units > 0 ? rtrim(rtrim(number_format($units, 2, '.', ''), '0'), '.') : '—',
                'status'            => $statusHtml,
                '_grade_numeric'    => is_numeric($grade) ? (float) $grade : null,
                '_units_numeric'    => $units,
                '_is_passed'        => $isPassed,
                '_is_credit'        => $isCredit,
            ];
        });

        $totalUnits = (float) $rows
            ->filter(fn ($r) => $r->_is_passed || $r->_is_credit)
            ->sum('_units_numeric');

        $percentDone = $requiredUnits > 0
            ? round(($totalUnits / $requiredUnits) * 100, 1)
            : 0;

        $weighted    = $rows->filter(fn ($r) => $r->_is_passed && $r->_grade_numeric !== null && $r->_units_numeric > 0);
        $weightSum   = (float) $weighted->sum('_units_numeric');
        $weightedSum = (float) $weighted->sum(fn ($r) => $r->_grade_numeric * $r->_units_numeric);
        $gwa         = $weightSum > 0 ? round($weightedSum / $weightSum, 2) : null;

        // Group: year_level → semester → rows. Scaffold every expected
        // semester (per the curriculum's terms_per_year / has_summer_term)
        // so the student can see subjects they still need to take, including
        // empty buckets — e.g. a trimester school always shows 1st/2nd/3rd
        // Sem per year, even if no subject is scheduled in one of them yet.
        $maxYear = (int) ($curriculumRows->max('year_level') ?: 0);
        $years   = $maxYear > 0 ? range(1, $maxYear) : [];

        $rowsByYearSem = $rows->groupBy('year_level')
            ->map(fn ($yearRows) => $yearRows->groupBy('semester'));

        $groups = collect();
        foreach ($years as $yr) {
            $semBuckets = collect();
            foreach ($expectedSems as $sem) {
                $semBuckets->put(
                    $sem,
                    $rowsByYearSem->get($yr)?->get($sem) ?? collect()
                );
            }
            $groups->put($yr, $semBuckets);
        }

        return view('student.transcript.index', [
            'columns'       => $columns,
            'groups'        => $groups,
            'programLabel'  => $programLabel,
            'totalUnits'    => $totalUnits,
            'requiredUnits' => $requiredUnits,
            'percentDone'   => $percentDone,
            'gwa'           => $gwa,
            'termsPerYear'  => $termsPerYear,
            'hasSummerTerm' => $hasSummerTerm,
            'report'        => app(ReportCardService::class)->build($student),
            'rcEditable'    => false,
        ]);
    }

    protected static function semesterLabel(int $sem, int $termsPerYear = 2): string
    {
        // Trimester schools: 3 is a regular term, not "Summer".
        if ($termsPerYear >= 3) {
            return match ($sem) {
                1       => '1st Sem',
                2       => '2nd Sem',
                3       => '3rd Sem',
                default => 'Sem '.$sem,
            };
        }
        return match ($sem) {
            1       => '1st Sem',
            2       => '2nd Sem',
            3       => 'Summer',
            default => 'Sem '.$sem,
        };
    }

    protected static function isFailing(float $g): bool
    {
        if ($g <= 5.0) {
            return $g > 3.0;   // PH 1.00–5.00 scale
        }
        return $g < 75;        // 100-pt scale
    }
}
