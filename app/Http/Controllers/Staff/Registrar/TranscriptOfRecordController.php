<?php

namespace App\Http\Controllers\Staff\Registrar;

use App\Http\Controllers\Controller;
use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentEnrollmentSubject;
use App\Models\TranscriptEditRequest;
use App\Services\Academics\Form137Service;
use App\Support\Spreadsheet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Registrar — Transcript of Records master list.
 *
 * Renders a master list of students grouped by the school's offered top-level
 * education levels (Basic Ed, Undergraduate, Graduate, etc.). If the school
 * only offers a single education level the level tabs are hidden.
 *
 * Columns:
 *   Full Name | Year Level | Student ID | Program(s) | Status
 *
 * Status derivation:
 *   - If `students.status` is `graduated` or `transferred`, use that.
 *   - Else map the latest enrollment's lifecycle status:
 *       draft           → Draft
 *       submitted       → Applicant       (awaiting registrar validation)
 *       assessed        → Assessed        (validated, awaiting billing)
 *       billed          → Billed
 *       partially_paid  → Partially Paid
 *       enrolled        → Enrolled        (officially enrolled)
 *       dropped         → Dropped
 *       cancelled       → Cancelled
 *       completed       → Completed
 *   - No enrollment row at all                                  → Unenrolled
 */
class TranscriptOfRecordController extends Controller
{
    public function index(Request $request)
    {
        return view('registrar.transcripts.index', $this->gather($request));
    }

    /** Export the currently-filtered transcript master list as CSV or Excel. */
    public function export(Request $request)
    {
        $data = $this->gather($request);

        $headers = ['Full Name', 'Year Level', 'Student ID', 'Program', 'Status'];
        $rows = $data['rows']->map(fn ($r) => [
            $r->full_name,
            $r->year_level,
            $r->student_id,
            $r->program,
            $data['statusOptions'][$r->_status_raw] ?? ucwords(str_replace('_', ' ', (string) $r->_status_raw)),
        ])->all();

        $format = strtolower((string) $request->query('format')) === 'xlsx' ? 'xlsx' : 'csv';
        $base = 'transcript-records-'.now()->format('Ymd-His');

        if ($format === 'xlsx') {
            $path = Spreadsheet::xlsx($headers, $rows, 'Transcripts');

            return response()->download($path, $base.'.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        }

        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers);
            foreach ($rows as $r) {
                fputcsv($out, array_values($r));
            }
            fclose($out);
        }, $base.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Build the transcript master dataset + level tabs + Status/Academic Year/
     * Year Level filter options, applying any active filters. Shared by index()
     * and export() so both read identical data.
     */
    protected function gather(Request $request): array
    {
        $schoolId = auth()->user()->school_id;

        // Top-level education levels the school offers (tabs).
        $levels = DB::table('education_nodes')
            ->whereNull('parent_id')
            ->where('is_offered', 1)
            ->where('is_active', 1)
            ->orderBy('order_index')
            ->get(['id', 'name']);

        $nodeToRoot = $this->buildNodeRootMap();

        $levelParam    = $request->input('level');
        $showAll       = $levelParam === null || $levelParam === '' || strtolower((string) $levelParam) === 'all';
        $activeLevelId = (int) ($request->integer('level') ?: ($levels->first()->id ?? 0));
        $showTabs      = $levels->count() > 1;
        if (! $showTabs) {
            $showAll = false;
        }

        // Latest enrollment per student qualifying for a Transcript of Records.
        $allowed = ['enrolled', 'provisionally_enrolled'];
        $latestIds = DB::table('student_enrollments as se')
            ->join('students as st', 'st.id', '=', 'se.student_id')
            ->where('st.school_id', $schoolId)
            ->whereIn('se.status', $allowed)
            ->select(DB::raw('max(se.id) as id'))
            ->groupBy('se.student_id')
            ->pluck('id');

        $enrollments = StudentEnrollment::with(['student', 'program', 'modality', 'educationNode', 'academicYear'])
            ->whereIn('id', $latestIds)
            ->get()
            ->sortBy(fn ($e) => strtolower(($e->student?->last_name ?? '').' '.($e->student?->first_name ?? '')))
            ->values();

        // Shape every row, carrying both the display fields and the raw values
        // used for the Status / Academic Year / Year Level filters.
        $allRows = collect();
        foreach ($enrollments as $e) {
            $student = $e->student;
            if (! $student) {
                continue;
            }

            $rootLevelId = $nodeToRoot[$e->education_node_id ?? null]
                ?? $nodeToRoot[$e->program?->education_node_id ?? null]
                ?? null;

            $fullName = trim(implode(' ', array_filter([
                $student->first_name, $student->middle_name, $student->last_name,
            ]))) ?: '—';

            $status = $this->deriveStatus($student->status ?? null, $e->status);
            [$statusLabel, $statusBg, $statusFg] = $this->statusPill($status);

            $allRows->push((object) [
                'id'          => $student->id,
                'full_name'   => $fullName,
                'year_level'  => $e->year_level ? 'Year '.(int) $e->year_level : '—',
                'student_id'  => $student->student_number ?? '—',
                'program'     => $this->programLabel($e, $nodeToRoot),
                'status'      => '<span style="display:inline-block;padding:2px 10px;border-radius:9999px;font-size:11px;font-weight:700;background:'.$statusBg.';color:'.$statusFg.';">'.$statusLabel.'</span>',
                '_status_raw' => $status,
                '_level_id'   => $rootLevelId,
                '_year_level' => $e->year_level,
                '_ay_id'      => $e->academic_year_id,
                '_ay_name'    => $e->academicYear?->name,
            ]);
        }

        // Status filter (options derived from the statuses actually present).
        $statusOptions = $allRows->pluck('_status_raw')->filter()->unique()->sort()
            ->mapWithKeys(fn ($s) => [$s => $this->statusPill($s)[0]])->all();
        $statusFilter = $request->input('status') ?: 'all';
        if ($statusFilter !== 'all' && ! array_key_exists($statusFilter, $statusOptions)) {
            $statusFilter = 'all';
        }
        $filtered = $statusFilter === 'all'
            ? $allRows
            : $allRows->filter(fn ($r) => $r->_status_raw === $statusFilter)->values();

        // Tab counts (after status, independent of the AY / year-level dropdowns).
        $counts = $filtered->groupBy(fn ($r) => $r->_level_id ?? 0)->map->count();

        // Scope to the active tab.
        $scoped = ($showAll || ! $showTabs)
            ? $filtered
            : $filtered->filter(fn ($r) => (int) ($r->_level_id ?? 0) === $activeLevelId)->values();

        // Academic-year filter.
        $academicYears = $scoped->filter(fn ($r) => $r->_ay_id)->unique('_ay_id')
            ->sortByDesc('_ay_name')->mapWithKeys(fn ($r) => [(int) $r->_ay_id => $r->_ay_name])->all();
        $academicYearId = $request->integer('academic_year_id') ?: null;
        $afterAy = $academicYearId
            ? $scoped->filter(fn ($r) => (int) $r->_ay_id === $academicYearId)->values()
            : $scoped;

        // Year-level filter.
        $yearLevelOptions = $afterAy->filter(fn ($r) => $r->_year_level !== null && $r->_year_level !== '')
            ->mapWithKeys(fn ($r) => [(string) (int) $r->_year_level => 'Year '.(int) $r->_year_level])->all();
        ksort($yearLevelOptions, SORT_NUMERIC);
        $yearLevel = $request->input('year_level');
        $yearLevel = ($yearLevel === null || $yearLevel === '') ? null : (string) $yearLevel;
        $finalRows = $yearLevel !== null
            ? $afterAy->filter(fn ($r) => (string) $r->_year_level === $yearLevel)->values()
            : $afterAy->values();

        return [
            'columns'          => config('tables.transcript_master.columns', []),
            'levels'           => $levels,
            'activeLevelId'    => $activeLevelId,
            'showTabs'         => $showTabs,
            'showAll'          => $showAll,
            'rows'             => $finalRows,
            'counts'           => $counts,
            'statusOptions'    => $statusOptions,
            'statusFilter'     => $statusFilter,
            'academicYears'    => $academicYears,
            'academicYearId'   => $academicYearId,
            'yearLevelOptions' => $yearLevelOptions,
            'yearLevel'        => $yearLevel,
        ];
    }

    /**
     * Programme label for the Transcript of Records cell.
     *
     * Mirrors EnrollmentProgramLabel (used by Admissions / Billing) but
     * intentionally omits the modality — a transcript is independent of
     * delivery mode, so the cell stays focused on grade level + programme.
     *
     *   Basic Education : "Grade {N} - {Program}"
     *   Other levels    : "{Program}"
     */
    protected function programLabel(StudentEnrollment $e, array $nodeToRoot): string
    {
        $label = $e->program?->code
            ?? $e->program?->name
            ?? $e->educationNode?->name
            ?? '—';

        $rootId = $nodeToRoot[$e->education_node_id ?? null]
            ?? $nodeToRoot[$e->program?->education_node_id ?? null]
            ?? null;

        if ($rootId) {
            $rootName = DB::table('education_nodes')->where('id', $rootId)->value('name');
            if (str_contains(strtolower((string) $rootName), 'basic')) {
                $yr = $e->year_level;
                if ($yr !== null && $yr !== '' && $yr !== '—') {
                    $yrLabel = is_numeric($yr) ? 'Grade '.$yr : (string) $yr;
                    $label   = $yrLabel.' - '.$label;
                }
            }
        }

        return $label;
    }

    /**
     * Registrar transcript viewer for a given student.
     *
     * Mirrors the layout of the student-facing transcript page but exposes
     * an "Edit" button per subject row. Edits flow through the
     * transcript_edit_requests table and require program-head + dean
     * approval before they are applied.
     */
    public function show(Student $student)
    {
        // Basic-ed learners render Form 137 (grade-level record) instead of the
        // higher-ed Year/Semester transcript — enforce the level separation.
        $form137 = app(Form137Service::class);
        if ($form137->isBasicEd($student)) {
            $data = $form137->build($student);

            return view('transcript.form137', [
                'student'   => $student,
                'sections'  => $data['sections'],
                'summary'   => $data['summary'],
                'backUrl'   => route('registrar.transcripts.index'),
                'backLabel' => 'Back to Records',
                'editable'  => true,   // registrar can edit grades + mark transfers
            ]);
        }

        $columns = config('tables.transcript.columns', []);

        $blank = [
            'student'        => $student,
            'columns'        => $columns,
            'groups'         => collect(),
            'programLabel'   => '—',
            'totalUnits'     => 0,
            'requiredUnits'  => 0,
            'percentDone'    => 0,
            'gwa'            => null,
            'pendingByCs'    => collect(),
            'isTransfereeOrShifter' => false,
            'latestEnrollmentId'    => null,
        ];

        $latestEnrollment = StudentEnrollment::with('program:id,code,name')
            ->where('student_id', $student->id)
            ->latest('id')
            ->first();

        // Inside the transcript itself (an official document) the programme is
        // written in full: "CODE — Full Programme Name".
        $programLabel = $latestEnrollment?->program
            ? trim(($latestEnrollment->program->code ? $latestEnrollment->program->code.' — ' : '').($latestEnrollment->program->name ?? ''))
            : '—';

        // Detect if the student is a transferee or a shifter. Transferees are
        // flagged on the enrollment row itself (student_type/enrollee_type);
        // shifters are inferred from having had enrollments under more than
        // one distinct program over their history. In either case, the
        // registrar gets direct (no-approval) credit-editing rights for the
        // transcript subjects.
        $enrolleeTypes = StudentEnrollment::where('student_id', $student->id)
            ->pluck('enrollee_type')
            ->merge(StudentEnrollment::where('student_id', $student->id)->pluck('student_type'))
            ->filter()
            ->map(fn ($v) => strtolower((string) $v))
            ->unique();

        $distinctPrograms = StudentEnrollment::where('student_id', $student->id)
            ->whereNotNull('program_id')
            ->distinct()
            ->pluck('program_id')
            ->count();

        $isTransfereeOrShifter = $enrolleeTypes->intersect(['transferee', 'shifter', 'returnee'])->isNotEmpty()
            || $distinctPrograms > 1;

        $curriculum = $latestEnrollment?->program_id
            ? Curriculum::where('program_id', $latestEnrollment->program_id)
                ->where('is_active', true)
                ->orderByDesc('version')
                ->first()
            : null;

        if (! $curriculum) {
            return view('registrar.transcripts.show', array_merge($blank, [
                'programLabel'          => $programLabel,
                'isTransfereeOrShifter' => $isTransfereeOrShifter,
                'latestEnrollmentId'    => $latestEnrollment?->id,
            ]));
        }

        $termsPerYear  = (int) ($curriculum->terms_per_year ?: 2);
        $hasSummerTerm = (bool) ($curriculum->has_summer_term ?? false);
        $expectedSems  = range(1, max(1, $termsPerYear));
        if ($hasSummerTerm && ! in_array(3, $expectedSems, true)) {
            $expectedSems[] = 3;
        }

        // Source of truth: program_subjects with fallback to curriculum_subjects.
        $curriculumRows = collect();
        if ($latestEnrollment?->program_id) {
            $curriculumRows = DB::table('program_subjects as ps')
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
                ->where('curriculum_id', $curriculum->id)
                ->orderBy('year_level')
                ->orderBy('semester')
                ->get();
        }

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

        // Pending edit requests keyed by subject_id so the view can flag
        // rows that already have a request awaiting approval.
        $pendingBySubject = TranscriptEditRequest::query()
            ->where('student_id', $student->id)
            ->whereNotIn('status', [
                TranscriptEditRequest::STATUS_APPLIED,
                TranscriptEditRequest::STATUS_REJECTED,
            ])
            ->get()
            ->keyBy('subject_id');

        $requiredUnits = (float) $curriculumRows->sum('units');

        // Teacher (historical reference): who the student took each subject with,
        // via the class recorded on their enrollment-subject row.
        $teacherByClass = DB::table('classes as c')
            ->leftJoin('users as u', 'u.id', '=', 'c.teacher_id')
            ->whereIn('c.id', $taken->pluck('class_id')->filter()->unique()->values())
            ->get()
            ->mapWithKeys(fn ($c) => [(int) $c->id => trim(($c->first_name ?? '').' '.($c->last_name ?? ''))])
            ->all();

        $rows = $curriculumRows->map(function ($cs) use ($taken, $termsPerYear, $teacherByClass) {
            $subj    = $cs->subject;
            $units   = (float) ($cs->units ?? 0);
            $sem     = (int) $cs->semester;
            $year    = (int) $cs->year_level;
            $semWord = $this->semesterLabel($sem, $termsPerYear);

            $record  = $taken->get($cs->subject_id);
            $grade   = $record?->grade;
            $status  = strtolower((string) ($record->status ?? ''));
            $teacher = ($record && $record->class_id ? ($teacherByClass[(int) $record->class_id] ?? '') : '') ?: '—';

            $isCredit  = in_array($status, ['credit', 'credited', 'transferred']);
            $isFailed  = $status === 'failed' || (is_numeric($grade) && $this->isFailing((float) $grade));
            $isPassed  = ! $isCredit && ! $isFailed && in_array($status, ['passed', 'completed']) && is_numeric($grade);
            $finished  = $isCredit || $isFailed || $isPassed;

            return (object) [
                'id'                    => $cs->id,
                'subject_id'            => $cs->subject_id,
                'enrollment_subject_id' => $record?->id,
                'year_level'            => $year,
                'semester'              => $sem,
                'semester_label'        => $semWord,
                'subject_code'          => $subj->code ?? '—',
                'descriptive_title'     => $subj->name ?? '—',
                'teacher'               => $teacher,
                'final_grade'           => is_numeric($grade)
                    ? rtrim(rtrim(number_format((float) $grade, 2, '.', ''), '0'), '.')
                    : ($isCredit ? 'Credit' : '—'),
                'final_grade_raw'       => is_numeric($grade) ? (float) $grade : null,
                'units'                 => $units > 0 ? rtrim(rtrim(number_format($units, 2, '.', ''), '0'), '.') : '—',
                'status_label'          => match (true) {
                    $isCredit  => 'Credit',
                    $isFailed  => 'Failed',
                    $isPassed  => 'Passed',
                    $status === 'enrolled' => 'Ongoing',
                    default    => 'Not Taken',
                },
                '_grade_numeric'        => is_numeric($grade) ? (float) $grade : null,
                '_units_numeric'        => $units,
                '_is_passed'            => $isPassed,
                '_is_credit'            => $isCredit,
                '_finished'             => $finished,
            ];
        });

        $totalUnits  = (float) $rows->filter(fn ($r) => $r->_is_passed || $r->_is_credit)->sum('_units_numeric');
        $percentDone = $requiredUnits > 0 ? round(($totalUnits / $requiredUnits) * 100, 1) : 0;

        $weighted    = $rows->filter(fn ($r) => $r->_is_passed && $r->_grade_numeric !== null && $r->_units_numeric > 0);
        $weightSum   = (float) $weighted->sum('_units_numeric');
        $weightedSum = (float) $weighted->sum(fn ($r) => $r->_grade_numeric * $r->_units_numeric);
        $gwa         = $weightSum > 0 ? round($weightedSum / $weightSum, 2) : null;

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

        return view('registrar.transcripts.show', [
            'student'         => $student,
            'columns'         => $columns,
            'groups'          => $groups,
            'programLabel'    => $programLabel,
            'totalUnits'      => $totalUnits,
            'requiredUnits'   => $requiredUnits,
            'percentDone'     => $percentDone,
            'gwa'             => $gwa,
            'termsPerYear'    => $termsPerYear,
            'hasSummerTerm'   => $hasSummerTerm,
            'pendingBySubject' => $pendingBySubject,
            'isTransfereeOrShifter' => $isTransfereeOrShifter,
            'latestEnrollmentId'    => $latestEnrollment?->id,
        ]);
    }

    /**
     * Record a registrar's request to amend a transcript subject row.
     *
     * The request lands in `transcript_edit_requests` with status=pending
     * and is held there until the program head and dean approve. No grade
     * is applied to the underlying StudentEnrollmentSubject at this stage.
     */
    public function storeEditRequest(Request $request, Student $student)
    {
        $data = $request->validate([
            'subject_id'            => ['required', 'integer', 'exists:subjects,id'],
            'enrollment_subject_id' => ['nullable', 'integer', 'exists:student_enrollment_subjects,id'],
            'new_grade'             => ['required', 'numeric', 'min:0', 'max:100'],
            'reason'                => ['nullable', 'string', 'max:1000'],
        ]);

        $oldGrade = null;
        if (! empty($data['enrollment_subject_id'])) {
            $oldGrade = StudentEnrollmentSubject::where('id', $data['enrollment_subject_id'])->value('grade');
        }

        TranscriptEditRequest::create([
            'student_id'            => $student->id,
            'subject_id'            => $data['subject_id'],
            'enrollment_subject_id' => $data['enrollment_subject_id'] ?? null,
            'old_grade'             => $oldGrade,
            'new_grade'             => $data['new_grade'],
            'reason'                => $data['reason'] ?? null,
            'status'                => TranscriptEditRequest::STATUS_PENDING,
            'requested_by'          => Auth::id(),
        ]);

        return back()->with('success', 'Edit request submitted. Awaiting program head and dean approval.');
    }

    /**
     * Directly apply a transcript edit for transferee/shifter students.
     *
     * Unlike `storeEditRequest`, this bypasses the program-head/dean
     * approval workflow because crediting transferred coursework is part
     * of the registrar's normal duty when processing transferees and
     * shifters. The change is written immediately to the underlying
     * StudentEnrollmentSubject row (creating one if the subject had no
     * enrollment record yet).
     */
    public function applyCreditEdit(Request $request, Student $student)
    {
        $data = $request->validate([
            'subject_id'            => ['required', 'integer', 'exists:subjects,id'],
            'enrollment_subject_id' => ['nullable', 'integer', 'exists:student_enrollment_subjects,id'],
            'new_grade'             => ['nullable', 'numeric', 'min:0', 'max:100'],
            'new_status'            => ['required', 'string', 'in:credit,passed,failed,enrolled'],
            'reason'                => ['nullable', 'string', 'max:1000'],
            'latest_enrollment_id'  => ['nullable', 'integer', 'exists:student_enrollments,id'],
            // Form 137: when a subject is credited/transferred, record the
            // school it was transferred from (stored on the subject's remarks).
            'transferred_from'      => ['nullable', 'string', 'max:255'],
        ]);

        // Transfer source is only meaningful for credited subjects.
        $remarks = $data['new_status'] === 'credit'
            ? (($data['transferred_from'] ?? '') !== '' ? 'Transferred from '.$data['transferred_from'] : null)
            : null;

        // Registrar may edit any student's transcript directly. The internal
        // grade-change approval workflow (teacher → program head → dean → registrar)
        // applies only to teacher-initiated requests, not to the registrar's
        // own corrections.

        $row = null;
        if (! empty($data['enrollment_subject_id'])) {
            $row = StudentEnrollmentSubject::find($data['enrollment_subject_id']);
        }

        // If the subject has never been enrolled, attach the credit to the
        // student's most recent enrollment so it surfaces on the transcript.
        if (! $row) {
            $enrollmentId = $data['latest_enrollment_id']
                ?? StudentEnrollment::where('student_id', $student->id)->latest('id')->value('id');

            if (! $enrollmentId) {
                return back()->withErrors([
                    'enrollment' => 'The student has no enrollment record to attach this credit to.',
                ]);
            }

            $row = StudentEnrollmentSubject::create([
                'student_enrollment_id' => $enrollmentId,
                'subject_id'            => $data['subject_id'],
                'status'                => $data['new_status'],
                'grade'                 => $data['new_grade'] ?? null,
                'final_grade'           => $data['new_grade'] ?? null,
                'remarks'               => $remarks,
            ]);
        } else {
            $row->status      = $data['new_status'];
            $row->grade       = $data['new_grade'] ?? $row->grade;
            $row->final_grade = $data['new_grade'] ?? $row->final_grade;
            $row->remarks     = $remarks;
            $row->save();
        }

        // Log an "applied" TranscriptEditRequest entry so the audit trail is
        // preserved alongside normal edit requests.
        TranscriptEditRequest::create([
            'student_id'            => $student->id,
            'subject_id'            => $data['subject_id'],
            'enrollment_subject_id' => $row->id,
            'old_grade'             => null,
            'new_grade'             => $data['new_grade'] ?? null,
            'reason'                => $data['reason']
                ?? 'Registrar transcript correction (direct edit).',
            'status'                => TranscriptEditRequest::STATUS_APPLIED,
            'requested_by'          => Auth::id(),
        ]);

        return back()->with('success', 'Transcript updated successfully.');
    }

    protected function semesterLabel(int $sem, int $termsPerYear = 2): string
    {
        if ($termsPerYear >= 3) {
            return match ($sem) {
                1 => '1st Sem', 2 => '2nd Sem', 3 => '3rd Sem',
                default => 'Sem '.$sem,
            };
        }
        return match ($sem) {
            1 => '1st Sem', 2 => '2nd Sem', 3 => 'Summer',
            default => 'Sem '.$sem,
        };
    }

    protected function isFailing(float $g): bool
    {
        if ($g <= 5.0) {
            return $g > 3.0;   // 1.00–5.00 scale
        }
        return $g < 75;        // 100-pt scale
    }

    /**
     * Walk every education_node and resolve its root ancestor id so that
     * `programs.education_node_id` can be classified into a top-level level
     * (Basic Ed, Undergraduate, …) in O(1).
     */
    protected function buildNodeRootMap(): array
    {
        $all = DB::table('education_nodes')->get(['id', 'parent_id'])->keyBy('id');

        $rootOf = [];
        foreach ($all as $id => $node) {
            $cur = $node;
            // Safety bound — education tree should never be hundreds deep.
            for ($i = 0; $i < 32 && $cur && $cur->parent_id; $i++) {
                $cur = $all[$cur->parent_id] ?? null;
            }
            $rootOf[$id] = $cur?->id;
        }
        return $rootOf;
    }

    protected function deriveStatus(?string $studentStatus, ?string $enrollmentStatus): string
    {
        $studentStatus = strtolower((string) $studentStatus);
        if (in_array($studentStatus, ['graduated', 'transferred'], true)) {
            return $studentStatus;
        }

        $enrollmentStatus = strtolower((string) $enrollmentStatus);
        if ($enrollmentStatus === '') {
            return 'unenrolled';
        }

        // Pass through known enrollment lifecycle values; anything else falls
        // back to "unenrolled" so we never leak raw enum strings to the UI.
        $known = [
            'draft', 'submitted', 'assessed', 'billed',
            'partially_paid', 'enrolled', 'provisionally_enrolled',
            'dropped', 'cancelled', 'completed',
        ];
        return in_array($enrollmentStatus, $known, true) ? $enrollmentStatus : 'unenrolled';
    }

    /** Return [label, bg, fg] for the pill renderer. */
    protected function statusPill(string $status): array
    {
        return match ($status) {
            'draft'          => ['Draft',          '#f1f5f9', '#475569'],
            'submitted'      => ['Submitted Application', '#e0e7ff', '#4338ca'],
            'exam_passed'    => ['Passed Entrance Exam', '#dbeafe', '#1d4ed8'],
            'exam_failed'    => ['Failed Entrance Exam', '#fee2e2', '#b91c1c'],
            'assessed'       => ['Assessed',       '#e0f2fe', '#0369a1'],
            'provisional'    => ['Provisional',    '#fef3c7', '#a16207'],
            'rejected'       => ['Rejected',       '#fee2e2', '#b91c1c'],
            'sent_billing'   => ['Sent Billing',   '#ede9fe', '#6d28d9'],
            'billed'         => ['Billed',         '#fef9c3', '#854d0e'],
            'partially_paid' => ['Partially Paid', '#fef3c7', '#a16207'],
            'enrolled'       => ['Enrolled',       '#dcfce7', '#15803d'],
            'provisionally_enrolled' => ['Provisionally Enrolled', '#dcfce7', '#047857'],
            'dropped'        => ['Dropped',        '#fee2e2', '#b91c1c'],
            'cancelled'      => ['Cancelled',      '#fee2e2', '#b91c1c'],
            'completed'      => ['Completed',      '#dcfce7', '#15803d'],
            'graduated'      => ['Graduated',      '#dbeafe', '#1d4ed8'],
            'transferred'    => ['Transferred',    '#fef3c7', '#a16207'],
            default          => ['Unenrolled',     '#f1f5f9', '#475569'],
        };
    }
}
