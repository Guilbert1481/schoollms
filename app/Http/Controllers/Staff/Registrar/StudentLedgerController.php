<?php

namespace App\Http\Controllers\Staff\Registrar;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentIdSetting;
use App\Models\Term;
use App\Models\User;
use App\Support\EducationLevels;
use App\Support\EnrollmentStatuses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Registrar — Student Ledgers.
 *
 * Detailed records of officially enrolled students (status = enrolled).
 * Provisionally enrolled, billed, and other in-flight statuses are excluded so
 * the registrar can focus on students whose records are fully compliant.
 */
class StudentLedgerController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = auth()->user()->school_id;

        // Top-level education levels offered by the school (tabs / dropdown).
        $levels = DB::table('education_nodes')
            ->whereNull('parent_id')
            ->where('is_offered', 1)
            ->where('is_active', 1)
            ->orderBy('order_index')
            ->get(['id', 'name']);

        $nodeToRoot = $this->buildNodeRootMap();
        $showTabs   = $levels->count() > 1;

        // Lookup of root-level id => name so we can detect Basic Education
        // rows in O(1) when formatting the Year/Term cell.
        $rootNameById = $levels->pluck('name', 'id')->all();

        $levelParam    = $request->query('level');
        $showAll       = $levelParam === null
            || $levelParam === ''
            || strtolower((string) $levelParam) === 'all';
        $activeLevelId = $showAll ? 0 : (int) $levelParam;

        // Status filter (taxonomy). Defaults to "Enrolled" so the page keeps its
        // historical behaviour; the dropdown lets the registrar switch to any
        // ledger-group status (Graduated, On Leave, Dropped, …) or "All".
        $statusFilter  = $request->query('status') ?: 'enrolled';
        $statusOptions = EnrollmentStatuses::options('ledger');
        if ($statusFilter !== 'all' && ! array_key_exists($statusFilter, $statusOptions)) {
            $statusFilter = 'enrolled';
        }
        // Latest enrollment per student, scoped to ledger (official + post)
        // statuses only — see ledgerSelectRows(). Shared with export() so both
        // read identical data.
        $rows = $this->ledgerSelectRows($schoolId);

        $academicYearId = $request->query('academic_year_id');
        $academicYearId = ($academicYearId === null || $academicYearId === '') ? null : (int) $academicYearId;

        $yearLevelFilter = $request->query('year_level');
        $yearLevelFilter = ($yearLevelFilter === null || $yearLevelFilter === '') ? null : (string) $yearLevelFilter;

        $programId = $request->integer('program_id') ?: null;
        $sectionId = $request->integer('section_id') ?: null;

        // Normalise each enrollment into an item carrying both the raw fields
        // used for filtering and the display object rendered by the table.
        $items = $rows->map(fn ($r) => $this->ledgerItemFrom($r, $nodeToRoot, $rootNameById));

        // Narrow to the selected status using the SAME resolution as the pill, so
        // "Enrolled" never includes a student already moved to Graduated / On
        // Leave / Dropped / etc.
        if ($statusFilter !== 'all') {
            $items = $items->filter(fn ($i) => $i->status_key === $statusFilter)->values();
        }

        // Tab counts = total enrolled per level (independent of the filters).
        $counts = $items->groupBy(fn ($i) => $i->root ?? 0)->map->count();

        // Restrict to the active level (tab), then layer on the dropdown filters.
        $scoped = $showAll
            ? $items
            : $items->filter(fn ($i) => (int) ($i->root ?? 0) === $activeLevelId)->values();

        // "Basic Education" view: either the active tab is basic, or the school
        // offers only one level and it is basic.
        $activeLevel    = $levels->firstWhere('id', $activeLevelId);
        $singleLevel    = $levels->count() === 1 ? $levels->first() : null;
        $effectiveLevel = $showAll ? $singleLevel : $activeLevel;
        $activeLevelIsBasic = $effectiveLevel
            && str_contains(strtolower((string) $effectiveLevel->name), 'basic');

        // Academic-year filter options (from the active level scope).
        $academicYears = $scoped
            ->filter(fn ($i) => $i->academic_year_id)
            ->unique('academic_year_id')
            ->sortByDesc('academic_year_name')
            ->mapWithKeys(fn ($i) => [(int) $i->academic_year_id => $i->academic_year_name])
            ->all();

        $afterAy = $academicYearId
            ? $scoped->filter(fn ($i) => (int) $i->academic_year_id === $academicYearId)->values()
            : $scoped;

        // Grade / Year-level filter options, sourced from the education tree so
        // every level/year ticked there appears (even with no students yet):
        //  - Basic Education  -> all "Grade N" nodes
        //  - a higher-ed tab  -> all "Year N" nodes set under that level
        //  - All Levels (mix) -> whatever year levels are present in the data
        $dataYearLevels = fn () => $afterAy
            ->filter(fn ($i) => $i->year_level !== null && $i->year_level !== '')
            ->mapWithKeys(fn ($i) => [(string) (int) $i->year_level => 'Year '.(int) $i->year_level])
            ->all();

        if ($activeLevelIsBasic) {
            $yearLevelOptions = EducationLevels::basicGradeOptions();
        } elseif (! $showAll && $activeLevelId > 0) {
            // Tree-defined year levels, unioned with any present in the data as a
            // safety net (so a student's actual year always shows).
            $yearLevelOptions = EducationLevels::yearLevelOptions($activeLevelId) + $dataYearLevels();
            ksort($yearLevelOptions, SORT_NUMERIC);
        } else {
            $yearLevelOptions = $dataYearLevels();
            ksort($yearLevelOptions, SORT_NUMERIC);
        }

        // Final display rows. In a Basic-Education view the Grade Level cell is
        // forced to "Grade N" (no higher-ed term/semester text), regardless of
        // any stray node/term linkage on the enrollment.
        $finalRows = $afterAy
            ->when($yearLevelFilter !== null, fn ($c) => $c->filter(fn ($i) => (string) $i->year_level === $yearLevelFilter))
            ->when($programId, fn ($c) => $c->filter(fn ($i) => (int) $i->program_id === (int) $programId))
            ->when($sectionId, fn ($c) => $c->filter(fn ($i) => (int) $i->section_id === (int) $sectionId))
            ->map(function ($i) use ($activeLevelIsBasic) {
                // In a Basic-Education view the Grade Level cell is always a grade
                // (or an em dash) — never a higher-ed term/semester label.
                if ($activeLevelIsBasic) {
                    $i->display->year_term = is_numeric($i->year_level)
                        ? 'Grade '.(int) $i->year_level
                        : (($i->year_level !== null && $i->year_level !== '') ? (string) $i->year_level : '—');
                }

                return $i->display;
            })
            ->values();

        // Lookup for the Change-Status modal: student db id => name + current key.
        $ledgerStudents = $finalRows->mapWithKeys(fn ($r) => [
            (int) ($r->id ?? 0) => ['name' => $r->full_name ?? '', 'status' => $r->status_key ?? 'enrolled'],
        ])->all();

        // Per-row action — opens the Change Status modal so the registrar can move
        // a student to Graduated / On Leave / Dropped / etc.
        $actions = [[
            'type'    => 'js',
            'name'    => 'change-status',
            'label'   => 'Change Status',
            'handler' => 'openChangeStatusModal',
            'icon'    => 'refresh-cw',
            'class'   => 'text-indigo-600 hover:bg-indigo-50',
        ]];

        // Columns. Basic Education uses a dedicated set (Student ID, Full Name,
        // LRN, Grade Level, Section, Status, Academic Year); higher-ed/All Levels
        // keep the configured set with a Status pill appended.
        if ($activeLevelIsBasic) {
            $columns = [
                ['key' => 'student_id',    'label' => 'Student ID',    'width' => '140px'],
                ['key' => 'full_name',     'label' => 'Full Name',     'width' => '200px'],
                ['key' => 'lrn',           'label' => 'LRN',           'width' => '150px'],
                ['key' => 'year_term',     'label' => 'Grade Level',   'width' => '130px'],
                ['key' => 'section',       'label' => 'Section',       'width' => '130px'],
                ['key' => 'status',        'label' => 'Status',        'width' => '170px', 'raw' => true],
                ['key' => 'academic_year', 'label' => 'Academic Year', 'width' => '150px'],
            ];
        } else {
            $columns = config('tables.student_ledgers.columns', []);
            // On "All Levels" the Program column becomes the student's education
            // Level (Basic Education, Undergraduate Programs, …) and the Email
            // column is dropped.
            if ($showAll) {
                $columns = array_values(array_filter($columns, fn ($c) => ($c['key'] ?? null) !== 'email'));
                $columns = array_map(function ($c) {
                    if (($c['key'] ?? null) === 'program') {
                        $c['key']   = 'level';
                        $c['label'] = 'Level';
                    }

                    return $c;
                }, $columns);
            }
            $columns[] = ['key' => 'status', 'label' => 'Status', 'width' => '180px', 'raw' => true];
        }

        // Program filter — higher-ed levels only (Basic Ed has no programs).
        // If the active higher-ed level has no programs yet, the table shows a
        // guiding message instead of the generic "no students" one.
        $showProgramFilter = ! $showAll && ! $activeLevelIsBasic && $activeLevelId > 0;
        $programOptions = [];
        if ($showProgramFilter) {
            $nodeRoot = EducationLevels::nodeRootMap();
            $programOptions = DB::table('programs')
                ->where('school_id', $schoolId)
                ->orderBy('code')->orderBy('name')
                ->get(['id', 'code', 'name', 'education_node_id'])
                ->filter(fn ($p) => (int) ($nodeRoot[$p->education_node_id ?? null] ?? 0) === $activeLevelId)
                ->mapWithKeys(fn ($p) => [(int) $p->id => $p->code ?: $p->name])
                ->all();
        }

        // Section filter options — active sections for the school (shown on the
        // Basic Education tab). Empty => the dropdown just offers "All Sections".
        $sectionOptions = DB::table('sections')
            ->where('school_id', $schoolId)
            ->where('is_active', 1)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->mapWithKeys(fn ($s) => [(int) $s->id => $s->name])
            ->all();

        $tableEmptyMessage = ($showProgramFilter && empty($programOptions))
            ? 'No programs have been set up for this level yet.'
            : 'No students enrolled for this selection yet.';

        $levelTitle = $showAll
            ? 'All Levels'
            : ($activeLevel?->name ?? ($levels->first()->name ?? null));

        $importTerms = DB::table('terms as t')
            ->leftJoin('academic_years as ay', 'ay.id', '=', 't.academic_year_id')
            ->where('t.school_id', $schoolId)
            ->orderByDesc('t.is_current')
            ->orderByDesc('t.start_date')
            ->orderByDesc('t.id')
            ->get([
                't.id',
                't.name',
                't.academic_year_id',
                't.is_current',
                'ay.name as academic_year_name',
            ]);

        // For the import "Others" path: the academic years the term/AY can be
        // attached to, and whether a higher-ed term number should be requested
        // (true when the school offers any non-Basic-Education level).
        $importAcademicYears = DB::table('academic_years')
            ->where('school_id', $schoolId)
            ->orderByDesc('start_date')
            ->get(['id', 'name']);

        $showImportTermNumber = $levels
            ->contains(fn ($l) => ! str_contains(strtolower((string) $l->name), 'basic'));

        return view('registrar.student_ledgers.index', [
            'columns'            => $columns,
            'levels'             => $levels,
            'activeLevelId'      => $activeLevelId,
            'showAll'            => $showAll,
            'showTabs'           => $showTabs,
            'levelTitle'         => $levelTitle,
            'rows'               => $finalRows,
            'counts'             => $counts,
            'total'              => $items->count(),
            'importTerms'        => $importTerms,
            'academicYears'      => $academicYears,
            'yearLevelOptions'   => $yearLevelOptions,
            'academicYearId'     => $academicYearId,
            'yearLevel'          => $yearLevelFilter,
            'activeLevelIsBasic' => $activeLevelIsBasic,
            'programOptions'     => $programOptions,
            'programId'          => $programId,
            'showProgramFilter'  => $showProgramFilter,
            'sectionOptions'     => $sectionOptions,
            'sectionId'          => $sectionId,
            'statusOptions'      => $statusOptions,
            'statusFilter'       => $statusFilter,
            'actions'            => $actions,
            'ledgerStudents'     => $ledgerStudents,
            'tableEmptyMessage'  => $tableEmptyMessage,
            'importAcademicYears'  => $importAcademicYears,
            'showImportTermNumber' => $showImportTermNumber,
            'importGradeOptions'   => EducationLevels::basicGradeOptions(),
        ]);
    }

    /**
     * Full detail page for a single student (opened by clicking a ledger row).
     * Tabs: Profile | Enrollment History | Documents | Fees & Payments — blank
     * for now; content will be added later.
     */
    public function show(Student $student)
    {
        abort_unless((int) $student->school_id === (int) auth()->user()->school_id, 403);

        // Latest enrollment + related labels for the header card.
        $enr = DB::table('student_enrollments as se')
            ->leftJoin('sections as sec', 'sec.id', '=', 'se.section_id')
            ->leftJoin('academic_years as ay', 'ay.id', '=', 'se.academic_year_id')
            ->leftJoin('terms as t', 't.id', '=', 'se.term_id')
            ->leftJoin('programs as p', 'p.id', '=', 'se.program_id')
            ->where('se.student_id', $student->id)
            ->orderByDesc('se.id')
            ->first([
                'se.year_level',
                'se.education_node_id',
                'se.status as enrollment_status',
                'sec.name as section_name',
                'ay.name as academic_year_name',
                't.name as term_name',
                'p.education_node_id as program_node_id',
            ]);

        // "Current Grade Level": Grade N for Basic Education, else Year N.
        $nodeToRoot = $this->buildNodeRootMap();
        $rootId     = $enr
            ? ($nodeToRoot[$enr->education_node_id ?? null] ?? $nodeToRoot[$enr->program_node_id ?? null] ?? null)
            : null;
        $rootName   = $rootId ? DB::table('education_nodes')->where('id', $rootId)->value('name') : null;
        $isBasic    = $rootName && str_contains(strtolower((string) $rootName), 'basic');
        $yearLevel  = $enr->year_level ?? null;
        $gradeLevel = ($yearLevel !== null && $yearLevel !== '')
            ? (is_numeric($yearLevel) ? ($isBasic ? 'Grade ' : 'Year ').(int) $yearLevel : (string) $yearLevel)
            : '—';

        // Name as "Last, First Middle".
        $firstMid = trim(implode(' ', array_filter([$student->first_name, $student->middle_name])));
        $name     = $student->last_name
            ? $student->last_name.($firstMid !== '' ? ', '.$firstMid : '')
            : ($firstMid !== '' ? $firstMid : ($student->student_number ?? 'Student'));

        $dash = fn ($v) => ($v !== null && $v !== '') ? $v : '—';

        $header = [
            'name'          => $name,
            'photo'         => $student->photo_path ?: null,
            'is_basic'      => (bool) $isBasic,
            'status_key'    => EnrollmentStatuses::resolveKey($student->status ?? null, $enr->enrollment_status ?? null),
            'student_id'    => $dash($student->student_number),
            'lrn'           => $dash($student->lrn),
            'date_of_birth' => $student->date_of_birth ? \Carbon\Carbon::parse($student->date_of_birth)->format('M d, Y') : '—',
            'gender'        => $dash($student->gender ? ucfirst((string) $student->gender) : null),
            'grade_level'   => $gradeLevel,
            'section'       => $dash($enr->section_name ?? null),
            'academic_year' => $dash($enr->academic_year_name ?? null),
            'term'          => $enr->term_name ?? null,
        ];

        // ---- Profile tab data -------------------------------------------------
        $schoolId   = (int) auth()->user()->school_id;
        $idSettings = StudentIdSetting::forSchool($schoolId);

        // Guardians: parents (non-emergency) and the emergency contact.
        $guardians = DB::table('guardians')->where('student_id', $student->id)->get();
        $gname = fn ($g) => trim(implode(' ', array_filter([$g->first_name ?? null, $g->middle_name ?? null, $g->last_name ?? null]))) ?: '—';

        $parentRows = $guardians->filter(fn ($g) => ! $g->is_emergency_contact)->values();
        if ($parentRows->isEmpty()) {
            $parentRows = $guardians->values();
        }
        $parents = $parentRows->map(fn ($g) => (object) [
            'name'         => $gname($g),
            'relationship' => $g->relationship ?: ($g->type ?: '—'),
            'contact'      => $g->mobile_number ?: ($g->landline_number ?: '—'),
            'email'        => $g->email ?: '—',
            'occupation'   => $g->occupation ?: '—',
        ]);

        $emg = $guardians->firstWhere('is_emergency_contact', 1) ?: $guardians->firstWhere('is_primary', 1);
        $emergency = $emg ? (object) [
            'name'         => $gname($emg),
            'relationship' => $emg->relationship ?: '—',
            'contact'      => $emg->mobile_number ?: ($emg->landline_number ?: '—'),
        ] : null;

        $homeAddress = trim(implode(', ', array_filter([
            $student->address_line_1 ?: $student->street,
            $student->address_line_2 ?: $student->subdivision,
            $student->barangay,
            $student->city_municipality,
            trim(($student->province ?? '').' '.($student->zip_code ?? '')),
        ])), ', ');

        $statusLabel = $header['status_key']
            ? (EnrollmentStatuses::options('ledger')[$header['status_key']]
                ?? EnrollmentStatuses::options('validate')[$header['status_key']]
                ?? ucwords(str_replace('_', ' ', $header['status_key'])))
            : '—';

        $totalEnrollments = DB::table('student_enrollments')->where('student_id', $student->id)->count();
        $distinctYears = DB::table('student_enrollments')->where('student_id', $student->id)
            ->whereNotNull('year_level')->distinct()->count('year_level');

        $profile = [
            'full_name'            => $header['name'],
            'date_of_birth'        => $header['date_of_birth'],
            'place_of_birth'       => '—', // no column
            'gender'               => $header['gender'],
            'nationality'          => $dash($student->nationality),
            'religion'             => $dash($student->religion),
            'blood_type'           => '—', // no column
            'email'                => $dash($student->email),
            'lrn'                  => $header['lrn'],
            'student_id'           => $header['student_id'],
            'grade_level'          => $header['grade_level'],
            'section'              => $header['section'],
            'academic_year'        => $header['academic_year'],
            'status'               => $statusLabel,
            'date_of_registration' => $student->created_at ? \Carbon\Carbon::parse($student->created_at)->format('M d, Y') : '—',
            'home_address'         => $homeAddress !== '' ? $homeAddress : '—',
            'phone'                => $dash($student->mobile_number ?: $student->phone),
            'alternate'            => $dash($student->landline_number),
        ];

        $quick = [
            'age'               => $student->date_of_birth ? \Carbon\Carbon::parse($student->date_of_birth)->age.' years old' : '—',
            'current_status'    => $statusLabel,
            'total_enrollments' => $totalEnrollments,
            'promotions'        => max(0, $distinctYears - 1),
            'last_updated'      => $student->updated_at ? \Carbon\Carbon::parse($student->updated_at)->format('M d, Y g:i A') : '—',
        ];

        $barcode = $idSettings->barcode_source === 'student_number'
            ? ($student->student_number ?: '')
            : ($student->lrn ?: $student->student_number ?: '');

        $idCard = [
            'orientation'   => $idSettings->orientation,
            'show_back'     => (bool) $idSettings->show_back,
            'school_name'   => DB::table('schools')->where('id', $schoolId)->value('school_name') ?: 'School',
            'photo'         => $header['photo'],
            'name'          => $header['name'],
            'student_id'    => $header['student_id'],
            'grade_section' => trim(($header['grade_level'] !== '—' ? $header['grade_level'] : '')
                                .($header['section'] !== '—' ? ' - '.$header['section'] : ''), ' -') ?: '—',
            'barcode'       => $barcode ?: '—',
        ];

        return view('registrar.student_ledgers.show', [
            'student'   => $student,
            'header'    => $header,
            'profile'   => $profile,
            'parents'   => $parents,
            'emergency' => $emergency,
            'quick'     => $quick,
            'idCard'    => $idCard,
        ]);
    }

    /**
     * Base query: latest enrollment per student, scoped to ledger (official +
     * post-enrollment) statuses. Shared by index() and export() so both read
     * identical data. Pre-enrollment rows never appear here.
     */
    protected function ledgerSelectRows(int $schoolId): \Illuminate\Support\Collection
    {
        $ledgerEnrollDb  = EnrollmentStatuses::dbValuesForGroup('ledger');
        $ledgerStudentDb = EnrollmentStatuses::studentDbValuesForGroup('ledger');

        return DB::table('students as st')
            ->join('student_enrollments as se', 'se.id', '=', DB::raw(
                '(select max(id) from student_enrollments where student_id = st.id)'
            ))
            ->leftJoin('programs as p', 'p.id', '=', 'se.program_id')
            ->leftJoin('terms as t', 't.id', '=', 'se.term_id')
            ->leftJoin('academic_years as ay', 'ay.id', '=', 'se.academic_year_id')
            ->leftJoin('sections as sec', 'sec.id', '=', 'se.section_id')
            ->where('st.school_id', $schoolId)
            ->where(function ($w) use ($ledgerEnrollDb, $ledgerStudentDb) {
                $w->whereIn('se.status', $ledgerEnrollDb)
                  ->orWhereIn('st.status', $ledgerStudentDb);
            })
            ->orderBy('st.last_name')
            ->orderBy('st.first_name')
            ->get([
                'st.id as student_id',
                'st.student_number',
                'st.lrn',
                'st.first_name',
                'st.middle_name',
                'st.last_name',
                'st.email',
                'st.status as student_status',
                'se.status as enrollment_status',
                'se.year_level',
                'se.education_node_id',
                'se.academic_year_id',
                'se.program_id',
                'se.section_id',
                'sec.name as section_name',
                'p.code as program_code',
                'p.name as program_name',
                'p.education_node_id as program_node_id',
                't.name as term_name',
                'ay.name as academic_year_name',
                'se.updated_at as enrolled_at',
            ]);
    }

    /** Shape one raw ledger row into the item used for filtering + display. */
    protected function ledgerItemFrom($r, array $nodeToRoot, array $rootNameById): object
    {
        $rootLevelId = $nodeToRoot[$r->education_node_id ?? null]
            ?? $nodeToRoot[$r->program_node_id ?? null]
            ?? null;

        $fullName = trim(implode(' ', array_filter([
            $r->first_name, $r->middle_name, $r->last_name,
        ]))) ?: '—';

        // Resolve the taxonomy status once — used for the pill, the PHP status
        // filter, the Change-Status modal, and the export's status label.
        $statusKey = EnrollmentStatuses::resolveKey(
            $r->student_status ?? null,
            $r->enrollment_status ?? null
        ) ?? 'enrolled';

        return (object) [
            'root'               => $rootLevelId,
            'year_level'         => $r->year_level,
            'academic_year_id'   => $r->academic_year_id,
            'academic_year_name' => $r->academic_year_name,
            'program_id'         => $r->program_id,
            'section_id'         => $r->section_id,
            'status_key'         => $statusKey,
            'display'            => (object) [
                'id'            => $r->student_id,
                'full_name'     => $fullName,
                'student_id'    => $r->student_number ?? '—',
                'lrn'           => $r->lrn ?: '—',
                'email'         => $r->email ?? '—',
                'year_term'     => $this->formatYearTerm($r->year_level, $r->term_name, $rootLevelId, $rootNameById),
                'section'       => $r->section_name ?: '—',
                'program'       => $r->program_code ?: ($r->program_name ?? '—'),
                'level'         => ($rootLevelId && isset($rootNameById[$rootLevelId])) ? $rootNameById[$rootLevelId] : '—',
                'academic_year' => $r->academic_year_name ?: '—',
                'enrolled_at'   => $r->enrolled_at
                    ? \Carbon\Carbon::parse($r->enrolled_at)->format('M d, Y')
                    : '—',
                'status'        => EnrollmentStatuses::pill($statusKey),
                'status_key'    => $statusKey,
            ],
        ];
    }

    /**
     * Export the currently-filtered list as CSV or Excel (.xlsx). Honours the
     * same level/status/AY/year/program/section filters as the table.
     */
    public function export(Request $request)
    {
        $format = strtolower((string) $request->query('format')) === 'xlsx' ? 'xlsx' : 'csv';
        [$headers, $rows, $title] = $this->buildExportRows($request);

        $base = Str::slug($title ?: 'student-ledgers').'-'.now()->format('Ymd-His');

        if ($format === 'xlsx') {
            $path = $this->buildXlsx($headers, $rows);

            return response()->download($path, $base.'.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        }

        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel reads accents correctly
            fputcsv($out, $headers);
            foreach ($rows as $r) {
                fputcsv($out, array_values($r));
            }
            fclose($out);
        }, $base.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Build [headers, rows, title] for the export, mirroring the table filters. */
    protected function buildExportRows(Request $request): array
    {
        $schoolId = auth()->user()->school_id;

        $levels = DB::table('education_nodes')
            ->whereNull('parent_id')->where('is_offered', 1)->where('is_active', 1)
            ->orderBy('order_index')->get(['id', 'name']);
        $nodeToRoot   = $this->buildNodeRootMap();
        $rootNameById = $levels->pluck('name', 'id')->all();

        $levelParam    = $request->query('level');
        $showAll       = $levelParam === null || $levelParam === '' || strtolower((string) $levelParam) === 'all';
        $activeLevelId = $showAll ? 0 : (int) $levelParam;
        $activeLevel   = $levels->firstWhere('id', $activeLevelId);
        $singleLevel   = $levels->count() === 1 ? $levels->first() : null;
        $effective     = $showAll ? $singleLevel : $activeLevel;
        $isBasic       = $effective && str_contains(strtolower((string) $effective->name), 'basic');

        $statusFilter = $request->query('status') ?: 'enrolled';
        $statusLabels = EnrollmentStatuses::options('ledger');
        if ($statusFilter !== 'all' && ! array_key_exists($statusFilter, $statusLabels)) {
            $statusFilter = 'enrolled';
        }

        $academicYearId  = $request->integer('academic_year_id') ?: null;
        $yearLevelFilter = $request->query('year_level');
        $yearLevelFilter = ($yearLevelFilter === null || $yearLevelFilter === '') ? null : (string) $yearLevelFilter;
        $programId       = $request->integer('program_id') ?: null;
        $sectionId       = $request->integer('section_id') ?: null;

        $items = $this->ledgerSelectRows($schoolId)
            ->map(fn ($r) => $this->ledgerItemFrom($r, $nodeToRoot, $rootNameById));

        if ($statusFilter !== 'all') {
            $items = $items->filter(fn ($i) => $i->status_key === $statusFilter);
        }

        $items = $items
            ->when(! $showAll, fn ($c) => $c->filter(fn ($i) => (int) ($i->root ?? 0) === $activeLevelId))
            ->when($academicYearId, fn ($c) => $c->filter(fn ($i) => (int) $i->academic_year_id === $academicYearId))
            ->when($yearLevelFilter !== null, fn ($c) => $c->filter(fn ($i) => (string) $i->year_level === $yearLevelFilter))
            ->when($programId, fn ($c) => $c->filter(fn ($i) => (int) $i->program_id === (int) $programId))
            ->when($sectionId, fn ($c) => $c->filter(fn ($i) => (int) $i->section_id === (int) $sectionId))
            ->values();

        $label = fn ($key) => $statusLabels[$key] ?? ucwords(str_replace('_', ' ', (string) $key));
        $title = $showAll ? 'All Levels' : ($activeLevel->name ?? 'Student Ledgers');

        if ($isBasic) {
            $headers = ['Student ID', 'Full Name', 'LRN', 'Grade Level', 'Section', 'Status', 'Academic Year'];
            $data = $items->map(function ($i) use ($label) {
                $d = $i->display;
                $grade = is_numeric($i->year_level) ? 'Grade '.(int) $i->year_level : ($d->year_term ?? '—');

                return [$d->student_id, $d->full_name, $d->lrn, $grade, $d->section, $label($i->status_key), $d->academic_year];
            })->all();
        } else {
            // On "All Levels" the Program column is the student's education Level.
            $headers = ['Student ID', 'Full Name', 'Email', 'Year Level', $showAll ? 'Level' : 'Program', 'Status', 'Academic Year'];
            $data = $items->map(function ($i) use ($label, $showAll) {
                $d = $i->display;

                return [$d->student_id, $d->full_name, $d->email, $d->year_term, $showAll ? $d->level : $d->program, $label($i->status_key), $d->academic_year];
            })->all();
        }

        return [$headers, $data, $title];
    }

    /**
     * Build a minimal, valid .xlsx (Open XML) from headers + rows using only
     * ZipArchive — no third-party spreadsheet library. Returns a temp file path.
     */
    protected function buildXlsx(array $headers, array $rows): string
    {
        $matrix = array_merge([$headers], $rows);

        $sheetRows = '';
        foreach ($matrix as $ri => $cells) {
            $rowNum = $ri + 1;
            $cellsXml = '';
            foreach (array_values($cells) as $ci => $val) {
                $ref = $this->columnLetter($ci).$rowNum;
                $esc = htmlspecialchars((string) $val, ENT_QUOTES | ENT_XML1, 'UTF-8');
                $cellsXml .= '<c r="'.$ref.'" t="inlineStr"><is><t xml:space="preserve">'.$esc.'</t></is></c>';
            }
            $sheetRows .= '<row r="'.$rowNum.'">'.$cellsXml.'</row>';
        }

        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetData>'.$sheetRows.'</sheetData></worksheet>';

        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'</Types>';

        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';

        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="Students" sheetId="1" r:id="rId1"/></sheets></workbook>';

        $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'</Relationships>';

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new \ZipArchive();
        $zip->open($tmp, \ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $rels);
        $zip->addFromString('xl/workbook.xml', $workbook);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
        $zip->close();

        return $tmp;
    }

    /** 0-based column index => spreadsheet letter (0=A, 25=Z, 26=AA…). */
    protected function columnLetter(int $i): string
    {
        $s = '';
        $i++;
        while ($i > 0) {
            $m = ($i - 1) % 26;
            $s = chr(65 + $m).$s;
            $i = intdiv($i - 1, 26);
        }

        return $s;
    }

    /**
     * Change a student's lifecycle status from a Student Ledgers row action.
     * Writes the canonical students.status for the chosen ledger status (e.g.
     * Graduated, On Leave, Dropped); "Enrolled" resets it to "active".
     */
    public function updateStatus(Request $request, Student $student)
    {
        abort_unless((int) $student->school_id === (int) auth()->user()->school_id, 403);

        $options = EnrollmentStatuses::options('ledger');
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:'.implode(',', array_keys($options))],
        ]);

        $student->forceFill([
            'status' => EnrollmentStatuses::studentStatusToSet($validated['status']),
        ])->save();

        $name = trim($student->first_name.' '.$student->last_name) ?: 'Student';

        return back()->with('success', "{$name}'s status set to {$options[$validated['status']]}.");
    }

    public function import(Request $request)
    {
        $schoolId = (int) auth()->user()->school_id;

        $request->validate([
            'level'         => ['required', 'integer'],
            'academic_year' => ['required', 'string', 'max:255'],
            'file'          => ['required', 'file', 'max:5120'],
            'year_level'    => ['nullable', 'integer'],
            'term_number'   => ['nullable', 'in:1,2,3'],
        ]);

        // The education level being imported into (locked to the active tab, or
        // chosen on the All Levels tab). Used as the enrollment's education level
        // so imported students appear under that tab.
        $levelNodeId = (int) $request->input('level');
        $levelName   = DB::table('education_nodes')->where('id', $levelNodeId)->value('name');
        $isBasic     = $levelName && str_contains(strtolower((string) $levelName), 'basic');

        // Basic Education enrols by Grade Level (no semester); higher-ed by Term.
        $defaultYearLevel = null;
        if ($isBasic) {
            if (! $request->filled('year_level')) {
                return back()->with('error', 'Please choose a grade level for Basic Education.');
            }
            $defaultYearLevel = (int) $request->input('year_level');
            $term = $this->resolveImportTerm($schoolId, (string) $request->input('academic_year'), null);
        } else {
            if (! $request->filled('term_number')) {
                return back()->with('error', 'Please choose a term for this level.');
            }
            $term = $this->resolveImportTerm($schoolId, (string) $request->input('academic_year'), $request->input('term_number'));
        }

        if (! $term) {
            return back()->with('error', 'Please enter a valid academic year (for example, 2025 - 2026).');
        }
        if (! $term->academic_year_id) {
            return back()->with('error', 'The resolved term is missing an academic year. Please try again.');
        }

        $file = $request->file('file');
        $ext  = strtolower($file->getClientOriginalExtension());
        if (! in_array($ext, ['csv', 'txt', 'xlsx'], true)) {
            return back()->with('error', 'Please upload a CSV or Excel (.xlsx) file.');
        }
        [$rows, $parseErrors] = $ext === 'xlsx'
            ? $this->readXlsxRows($file->getRealPath())
            : $this->readCsvRows($file->getRealPath());

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors  = $parseErrors;

        foreach ($rows as $index => $row) {
            $line = $index + 2;

            try {
                $result = DB::transaction(function () use ($row, $schoolId, $term, $levelNodeId, $defaultYearLevel) {
                    return $this->importStudentRow($row, (int) $schoolId, $term, $levelNodeId, $defaultYearLevel);
                });

                if ($result === 'created') {
                    $created++;
                } elseif ($result === 'updated') {
                    $updated++;
                } else {
                    $skipped++;
                }
            } catch (\Throwable $e) {
                $skipped++;
                $errors[] = "Line {$line}: ".$e->getMessage();
            }
        }

        return back()
            ->with('success', "Import finished. {$created} created, {$updated} updated, {$skipped} skipped.")
            ->with('import_errors', array_slice($errors, 0, 20));
    }

    /**
     * Resolve the Term for the "Others" import path: find-or-create a term
     * under the chosen academic year. A term number produces a higher-ed
     * numbered term; otherwise a Basic-Education enrolment term.
     */
    protected function resolveImportTerm(int $schoolId, string $academicYearName, ?string $termNumber): ?Term
    {
        $name = trim($academicYearName);
        if ($name === '') {
            return null;
        }

        // Find-or-create the academic year so historical years (e.g. 2018-2019)
        // can be added straight from the import — that is the whole point of
        // "Others": loading a school's past records.
        [$startDate, $endDate] = $this->guessAcademicYearDates($name);

        $ay = AcademicYear::firstOrCreate(
            ['school_id' => $schoolId, 'name' => $name],
            ['start_date' => $startDate, 'end_date' => $endDate, 'status' => 'closed']
        );

        if ($termNumber) {
            return Term::firstOrCreate(
                [
                    'school_id'        => $schoolId,
                    'academic_year_id' => $ay->id,
                    'term'             => 'Term '.$termNumber,
                ],
                [
                    'education_level' => 'higher_ed',
                    'enrollment_type' => 'regular',
                    'academic_year'   => mb_substr($name, 0, 20),
                    'name'            => 'Term '.$termNumber.' ('.$name.')',
                    'title'           => 'Term '.$termNumber,
                    'start_date'      => $ay->start_date ?? $startDate,
                    'end_date'        => $ay->end_date ?? $endDate,
                    'status'          => 'active',
                    'is_active'       => 1,
                ]
            );
        }

        return Term::firstOrCreate(
            [
                'school_id'        => $schoolId,
                'academic_year_id' => $ay->id,
                'term'             => 'Enrollment',
            ],
            [
                'education_level' => 'basic_ed',
                'enrollment_type' => 'regular',
                'academic_year'   => mb_substr($name, 0, 20),
                'name'            => 'Basic Ed ('.$name.')',
                'title'           => 'Basic Ed',
                'start_date'      => $ay->start_date ?? $startDate,
                'end_date'        => $ay->end_date ?? $endDate,
                'status'          => 'active',
                'is_active'       => 1,
            ]
        );
    }

    /**
     * Best-effort start/end dates from an academic-year label like "2018-2019".
     * Falls back to today..+1 year when no years can be parsed.
     *
     * @return array{0:string,1:string}
     */
    protected function guessAcademicYearDates(string $name): array
    {
        if (preg_match_all('/\d{4}/', $name, $m) && ! empty($m[0])) {
            $y1 = (int) $m[0][0];
            $y2 = isset($m[0][1]) ? (int) $m[0][1] : $y1 + 1;

            return [sprintf('%04d-06-01', $y1), sprintf('%04d-04-30', $y2)];
        }

        return [now()->toDateString(), now()->addYear()->toDateString()];
    }

    /**
     * Format the Year/Term cell. Basic Education rows render with a
     * "Grade {N}" prefix, every other level uses "Year {N}". The term name
     * (when available) is appended after a dash. Examples:
     *
     *   Basic Education : "Grade 4 - Semester 2"
     *   Other levels    : "Year 1 - 2nd Semester"
     */
    protected function formatYearTerm($yearLevel, ?string $termName, ?int $rootLevelId, array $rootNameById): string
    {
        $isBasicEd = $rootLevelId
            && str_contains(strtolower((string) ($rootNameById[$rootLevelId] ?? '')), 'basic');

        $yearPart = null;
        if ($yearLevel !== null && $yearLevel !== '' && $yearLevel !== '—') {
            $yearPart = is_numeric($yearLevel)
                ? ($isBasicEd ? 'Grade ' : 'Year ').(int) $yearLevel
                : (string) $yearLevel;
        }

        $termPart = trim((string) ($termName ?? ''));

        if ($yearPart && $termPart !== '') {
            return $yearPart.' - '.$termPart;
        }
        if ($yearPart) {
            return $yearPart;
        }
        if ($termPart !== '') {
            return $termPart;
        }
        return '—';
    }

    protected function buildNodeRootMap(): array
    {
        $all = DB::table('education_nodes')->get(['id', 'parent_id'])->keyBy('id');

        $rootOf = [];
        foreach ($all as $id => $node) {
            $cur = $node;
            for ($i = 0; $i < 32 && $cur && $cur->parent_id; $i++) {
                $cur = $all[$cur->parent_id] ?? null;
            }
            $rootOf[$id] = $cur?->id;
        }
        return $rootOf;
    }

    protected function importStudentRow(array $row, int $schoolId, Term $defaultTerm, ?int $fallbackNodeId = null, ?int $defaultYearLevel = null): string
    {
        $firstName = trim((string) ($row['first_name'] ?? ''));
        $lastName  = trim((string) ($row['last_name'] ?? ''));

        if ($firstName === '' || $lastName === '') {
            throw new \RuntimeException('First name and last name are required.');
        }

        $email = strtolower(trim((string) ($row['email'] ?? '')));
        $email = filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;

        $studentNumber = trim((string) ($row['student_number'] ?? ''));
        if ($studentNumber !== '') {
            $owner = Student::query()
                ->where('student_number', $studentNumber)
                ->first();

            if ($owner && (int) $owner->school_id !== $schoolId) {
                throw new \RuntimeException("Student number {$studentNumber} belongs to another school.");
            }
        }

        $user = $email ? User::query()->where('email', $email)->first() : null;
        if ($user && (int) $user->school_id !== $schoolId) {
            throw new \RuntimeException("Email {$email} belongs to another school.");
        }
        if ($user && $user->role !== 'student') {
            throw new \RuntimeException("Email {$email} belongs to a non-student account.");
        }

        if ($studentNumber !== '' && $user) {
            $studentForUser = Student::query()
                ->where('school_id', $schoolId)
                ->where('user_id', $user->id)
                ->first();

            if ($studentForUser && $studentForUser->student_number !== $studentNumber) {
                throw new \RuntimeException("Email {$email} is already linked to student {$studentForUser->student_number}.");
            }
        }

        if (! $user && $email) {
            $user = User::create([
                'first_name'  => $firstName,
                'middle_name' => $this->clean($row['middle_name'] ?? null),
                'last_name'   => $lastName,
                'email'       => $email,
                'password'    => Hash::make(Str::random(16)),
                'role'        => 'student',
                'school_id'   => $schoolId,
                'phone'       => $this->clean($row['phone'] ?? $row['mobile_number'] ?? null),
            ]);
        } elseif ($user) {
            $user->fill([
                'first_name'  => $firstName,
                'middle_name' => $this->clean($row['middle_name'] ?? null),
                'last_name'   => $lastName,
                'phone'       => $this->clean($row['phone'] ?? $row['mobile_number'] ?? $user->phone),
            ])->save();
        }

        $student = null;
        if ($studentNumber !== '') {
            $student = Student::query()
                ->where('school_id', $schoolId)
                ->where('student_number', $studentNumber)
                ->first();
        }
        if (! $student && $email) {
            $student = Student::query()
                ->where('school_id', $schoolId)
                ->where('email', $email)
                ->first();
        }
        if (! $student && $user) {
            $student = Student::query()
                ->where('school_id', $schoolId)
                ->where('user_id', $user->id)
                ->first();
        }

        $wasExistingStudent = (bool) $student;
        $studentNumber = $studentNumber !== ''
            ? $studentNumber
            : ($student?->student_number ?: $this->generateStudentNumber());

        $studentData = [
            'school_id'            => $schoolId,
            'user_id'              => $user?->id,
            'student_number'       => $studentNumber,
            'lrn'                  => $this->clean($row['lrn'] ?? null),
            'first_name'           => $firstName,
            'middle_name'          => $this->clean($row['middle_name'] ?? null),
            'last_name'            => $lastName,
            'email'                => $email,
            'phone'                => $this->clean($row['phone'] ?? null),
            'mobile_number'        => $this->clean($row['mobile_number'] ?? $row['phone'] ?? null),
            'gender'               => $this->clean($row['gender'] ?? null),
            'date_of_birth'        => $this->parseDate($row['date_of_birth'] ?? $row['birthdate'] ?? null),
            'nationality'          => $this->clean($row['nationality'] ?? null),
            'barangay'             => $this->clean($row['barangay'] ?? null),
            'city_municipality'    => $this->clean($row['city_municipality'] ?? $row['city'] ?? null),
            'province'             => $this->clean($row['province'] ?? null),
            'region'               => $this->clean($row['region'] ?? null),
            'zip_code'             => $this->clean($row['zip_code'] ?? null),
            'address_line_1'       => $this->clean($row['address_line_1'] ?? $row['address'] ?? null),
            'address_line_2'       => $this->clean($row['address_line_2'] ?? null),
        ];

        $studentData = $this->filterColumns('students', $studentData);

        if ($student) {
            $student->fill($studentData)->save();
        } else {
            $student = Student::create($studentData);
        }

        if ($user) {
            $this->syncProfileAndAccess($user, $student, $schoolId);
        }

        $term = $defaultTerm;
        $rowTermId = (int) ($row['term_id'] ?? 0);
        if ($rowTermId > 0 && $rowTermId !== (int) $defaultTerm->id) {
            $term = Term::query()
                ->where('school_id', $schoolId)
                ->findOrFail($rowTermId);
        }

        if (! $term->academic_year_id) {
            throw new \RuntimeException("Term {$term->id} is missing an academic year.");
        }

        $program = $this->resolveProgram($row, $schoolId);

        // Section (CSV "section" header): matched by name, created if it doesn't
        // exist yet.
        $sectionId = $this->resolveSection(
            $row['section'] ?? null,
            $schoolId,
            $term,
            (int) ($row['year_level'] ?? 0) ?: $defaultYearLevel,
            $program
        );

        $enrollment = StudentEnrollment::query()
            ->where('student_id', $student->id)
            ->where('academic_year_id', $term->academic_year_id)
            ->where('term_id', $term->id)
            ->first();

        $enrollmentData = [
            'school_id'          => $schoolId,
            'student_id'         => $student->id,
            'academic_year_id'   => $term->academic_year_id,
            'term_id'            => $term->id,
            'program_id'         => $program?->id,
            'education_node_id'  => (int) ($row['education_node_id'] ?? 0)
                ?: ($program?->education_node_id ?: $fallbackNodeId),
            'year_level'         => (int) ($row['year_level'] ?? 0) ?: $defaultYearLevel,
            'student_type'       => $this->clean($row['student_type'] ?? null) ?: 'continuing',
            'enrollee_type'      => $this->clean($row['enrollee_type'] ?? null) ?: 'continuing',
            'program_type'       => $this->clean($row['program_type'] ?? null) ?: 'regular',
            'education_level'    => $this->clean($row['education_level'] ?? null),
            'status'             => StudentEnrollment::STATUS_ENROLLED,
            'approval_level'     => 'import',
            'approved_by'        => auth()->id(),
            'approved_at'        => now(),
            'remarks'            => 'Imported as enrolled student profile.',
        ];

        // Only override the section when one was provided/resolved (don't wipe an
        // existing section on update when the CSV has no section).
        if ($sectionId !== null) {
            $enrollmentData['section_id'] = $sectionId;
        }

        $enrollmentData = $this->filterColumns('student_enrollments', $enrollmentData);

        if ($enrollment) {
            $enrollment->fill($enrollmentData)->save();
        } else {
            StudentEnrollment::create($enrollmentData);
        }

        return $wasExistingStudent || $enrollment ? 'updated' : 'created';
    }

    protected function readCsvRows(string $path): array
    {
        $handle = fopen($path, 'rb');
        if (! $handle) {
            throw new \RuntimeException('Unable to read uploaded file.');
        }

        $header = fgetcsv($handle);
        if (! $header) {
            fclose($handle);
            return [[], ['The CSV file is empty.']];
        }

        $keys = array_map(fn ($h) => $this->normalizeHeader((string) $h), $header);
        $rows = [];
        $errors = [];

        while (($line = fgetcsv($handle)) !== false) {
            if (count(array_filter($line, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $row = [];
            foreach ($keys as $index => $key) {
                if ($key === '') {
                    continue;
                }
                $row[$key] = $line[$index] ?? null;
            }
            $rows[] = $row;
        }

        fclose($handle);

        return [$rows, $errors];
    }

    /**
     * Read rows from an .xlsx file (Open XML) using only ZipArchive + SimpleXML —
     * no third-party library. Returns [rows, errors] like readCsvRows().
     */
    protected function readXlsxRows(string $path): array
    {
        if (! class_exists(\ZipArchive::class)) {
            return [[], ['Excel import is not supported on this server.']];
        }

        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return [[], ['Could not open the Excel file.']];
        }

        // Shared strings (Excel stores most text here, referenced by index).
        $shared = [];
        if (($ss = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            $sx = @simplexml_load_string($ss);
            if ($sx !== false) {
                foreach ($sx->si as $si) {
                    $shared[] = $this->xlsxText($si);
                }
            }
        }

        // First worksheet.
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $n = $zip->getNameIndex($i);
                if (preg_match('#^xl/worksheets/.*\.xml$#', $n)) {
                    $sheetXml = $zip->getFromName($n);
                    break;
                }
            }
        }
        $zip->close();

        if (! $sheetXml) {
            return [[], ['No worksheet found in the Excel file.']];
        }

        $sx = @simplexml_load_string($sheetXml);
        if ($sx === false) {
            return [[], ['Could not read the Excel worksheet.']];
        }

        $matrix = [];
        foreach ($sx->sheetData->row as $rowEl) {
            $cells = [];
            foreach ($rowEl->c as $c) {
                $ref     = (string) $c['r'];                       // e.g. "B3"
                $colIdx  = $this->letterToIndex(preg_replace('/\d+/', '', $ref));
                $type    = (string) $c['t'];
                if ($type === 's') {
                    $cells[$colIdx] = $shared[(int) $c->v] ?? '';
                } elseif ($type === 'inlineStr') {
                    $cells[$colIdx] = $this->xlsxText($c->is);
                } else {
                    $cells[$colIdx] = (string) $c->v;
                }
            }
            $matrix[] = $cells;
        }

        if (empty($matrix)) {
            return [[], ['The Excel file is empty.']];
        }

        $header = array_shift($matrix);
        $keys = [];
        foreach ($header as $idx => $h) {
            $keys[$idx] = $this->normalizeHeader((string) $h);
        }

        $rows = [];
        foreach ($matrix as $line) {
            if (count(array_filter($line, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }
            $row = [];
            foreach ($keys as $idx => $key) {
                if ($key === '') {
                    continue;
                }
                $row[$key] = $line[$idx] ?? null;
            }
            $rows[] = $row;
        }

        return [$rows, []];
    }

    /** Extract text from an .xlsx string element (<si> or inline <is>). */
    protected function xlsxText($el): string
    {
        if ($el === null) {
            return '';
        }
        $text = isset($el->t) ? (string) $el->t : '';
        if (isset($el->r)) {
            foreach ($el->r as $run) {
                $text .= (string) $run->t;
            }
        }

        return $text;
    }

    /** Spreadsheet column letters -> 0-based index ("A"=>0, "Z"=>25, "AA"=>26). */
    protected function letterToIndex(string $letters): int
    {
        $letters = strtoupper($letters);
        $n = 0;
        for ($i = 0, $len = strlen($letters); $i < $len; $i++) {
            $n = $n * 26 + (ord($letters[$i]) - 64);
        }

        return max(0, $n - 1);
    }

    /**
     * Resolve a section by name for the school — find it, or create it if it
     * doesn't exist (so imports can group students into new sections too).
     */
    protected function resolveSection(?string $name, int $schoolId, Term $term, ?int $yearLevel, ?object $program): ?int
    {
        $name = trim((string) $name);
        if ($name === '') {
            return null;
        }

        $existing = DB::table('sections')
            ->where('school_id', $schoolId)
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->value('id');
        if ($existing) {
            return (int) $existing;
        }

        return (int) DB::table('sections')->insertGetId($this->filterColumns('sections', [
            'school_id'  => $schoolId,
            'program_id' => $program?->id,
            'term_id'    => $term->id,
            'name'       => $name,
            'year_level' => $yearLevel,
            'capacity'   => 0,
            'is_active'  => 1,
            'status'     => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    protected function normalizeHeader(string $header): string
    {
        $key = strtolower(trim($header));
        $key = preg_replace('/[^a-z0-9]+/', '_', $key);
        $key = trim((string) $key, '_');

        return match ($key) {
            'student_no', 'student_id', 'id_number', 'id_no' => 'student_number',
            'learner_reference_number', 'learner_reference_no', 'lrn_no', 'lrn_number' => 'lrn',
            'given_name', 'firstname' => 'first_name',
            'middlename' => 'middle_name',
            'surname', 'lastname', 'family_name' => 'last_name',
            'birth_date', 'birthday', 'dob' => 'date_of_birth',
            'program', 'course', 'course_code' => 'program_code',
            'grade_level', 'year', 'grade' => 'year_level',
            'contact_number', 'mobile', 'mobile_no' => 'mobile_number',
            'section_name', 'sec' => 'section',
            default => $key,
        };
    }

    protected function resolveProgram(array $row, int $schoolId): ?object
    {
        $code = trim((string) ($row['program_code'] ?? ''));
        $name = trim((string) ($row['program_name'] ?? ''));

        if ($code === '' && $name === '') {
            return null;
        }

        return DB::table('programs')
            ->where('school_id', $schoolId)
            ->when($code !== '', fn ($q) => $q->whereRaw('LOWER(code) = ?', [strtolower($code)]))
            ->when($code === '' && $name !== '', fn ($q) => $q->whereRaw('LOWER(name) = ?', [strtolower($name)]))
            ->first();
    }

    protected function syncProfileAndAccess(User $user, Student $student, int $schoolId): void
    {
        $profile = DB::table('profiles')->where('user_id', $user->id)->first();
        $profileData = $this->filterColumns('profiles', [
            'user_id'      => $user->id,
            'school_id'    => $schoolId,
            'profile_type' => 'student',
            'profile_code' => $student->student_number,
            'first_name'   => $student->first_name,
            'middle_name'  => $student->middle_name,
            'last_name'    => $student->last_name,
            'gender'       => $student->gender,
            'birthday'     => $student->date_of_birth,
            'contact_number' => $student->mobile_number ?: $student->phone,
            'address'      => $student->address_line_1,
            'city'         => $student->city_municipality,
            'province'     => $student->province,
            'country'      => $student->country,
            'nationality'  => $student->nationality,
            'status'       => 'active',
            'updated_at'   => now(),
        ]);

        if ($profile) {
            DB::table('profiles')->where('id', $profile->id)->update($profileData);
            $profileId = $profile->id;
        } else {
            $profileData['created_at'] = now();
            $profileId = DB::table('profiles')->insertGetId($profileData);
        }

        $roleId = $this->ensureStudentRole($schoolId);
        $accessData = $this->filterColumns('account_access', [
            'user_id'       => $user->id,
            'role_id'       => $roleId,
            'person_id'     => $profileId,
            'office_id'     => null,
            'role_snapshot' => 'student',
            'start_date'    => now()->toDateString(),
            'assigned_by'   => auth()->id(),
            'remarks'       => 'Imported enrolled student profile',
            'is_active'     => 1,
            'updated_at'    => now(),
        ]);

        $existingAccess = DB::table('account_access')
            ->where('user_id', $user->id)
            ->when(Schema::hasColumn('account_access', 'role_id'), fn ($q) => $q->where('role_id', $roleId))
            ->first();

        if ($existingAccess) {
            DB::table('account_access')->where('id', $existingAccess->id)->update($accessData);
        } else {
            $accessData['created_at'] = now();
            DB::table('account_access')->insert($accessData);
        }
    }

    protected function ensureStudentRole(int $schoolId): int
    {
        $role = DB::table('roles')
            ->where('school_id', $schoolId)
            ->where('name', 'student')
            ->first();

        if ($role) {
            return (int) $role->id;
        }

        $data = [
            'school_id'  => $schoolId,
            'name'       => 'student',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('roles', 'authority_level')) {
            $data['authority_level'] = 0;
        }

        return (int) DB::table('roles')->insertGetId($data);
    }

    protected function generateStudentNumber(): string
    {
        $year = now()->format('Y');

        do {
            $candidate = sprintf('S-%s-%06d', $year, random_int(1, 999999));
        } while (Student::where('student_number', $candidate)->exists());

        return $candidate;
    }

    protected function filterColumns(string $table, array $data): array
    {
        return collect($data)
            ->filter(fn ($value, $key) => Schema::hasColumn($table, $key))
            ->all();
    }

    protected function clean($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    protected function parseDate($value): ?string
    {
        $value = $this->clean($value);
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            throw new \RuntimeException("Invalid date value '{$value}'.");
        }
    }
}
