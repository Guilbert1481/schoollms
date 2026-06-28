<?php

namespace App\Http\Controllers\Staff\Admissions;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Illuminate\Http\Request;

class AdmissionsController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    public function dashboard()
    {
        return view('admission.dashboard', [
            'totalApplications' => Application::count(),
            'submitted'        => Application::where('status', 'submitted')->count(),
            'underReview'      => Application::where('status', 'under_review')->count(),
            'approved'         => Application::where('status', 'approved')->count(),
            'students'         => Student::count(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Applications
    |--------------------------------------------------------------------------
    */

    public function applications()
    {
        $applications = Application::latest()->paginate(10);

        return view('admission.applications', compact('applications'));
    }

    /*
    |--------------------------------------------------------------------------
    | Students (Applicants)
    |--------------------------------------------------------------------------
    */

    public function students()
    {
        $students = Student::latest()->paginate(10);

        return view('admission.students', compact('students'));
    }

    /*
    |--------------------------------------------------------------------------
    | Applicant Directory
    | Lists every student who has submitted (or is drafting) an enrolment
    | application, with search + status filtering.
    |--------------------------------------------------------------------------
    */
    public function applicants(Request $request)
    {
        return $this->renderApplicantList($request, mode: 'active');
    }

    /**
     * Permanent record of every applicant who has been officially enrolled
     * OR whose enrollment window has expired. These rows are removed from
     * the active "Applications" page and live here forever.
     */
    public function applicantDirectory(Request $request)
    {
        return $this->renderApplicantList($request, mode: 'archived');
    }

    protected function renderApplicantList(Request $request, string $mode)
    {
        $schoolId = auth()->user()->school_id;
        $isArchived = $mode === 'archived';
        $routeName  = $isArchived ? 'admission.applicant-directory' : 'admission.applicants';

        // ------------------------------------------------------------------
        // 1. Top-level education levels the school offers (tabs)
        // ------------------------------------------------------------------
        $levels = \Illuminate\Support\Facades\DB::table('education_nodes')
            ->whereNull('parent_id')
            ->where('is_offered', 1)
            ->where('is_active', 1)
            ->orderBy('order_index')
            ->get(['id', 'name']);

        $nodeToRoot    = $this->buildNodeRootMap();
        $showTabs      = $levels->count() > 1;
        // "All" mode shows every applicant regardless of level. It is the
        // default view; selecting a specific level via ?level={id} narrows it.
        $levelParam    = $request->query('level');
        $showAll       = $levelParam === null
            || $levelParam === ''
            || strtolower((string) $levelParam) === 'all';
        $activeLevelId = $showAll
            ? 0
            : (int) $levelParam;

        // ------------------------------------------------------------------
        // 2. Pull every enrollment with student + program joined
        // ------------------------------------------------------------------
        $enrollments = StudentEnrollment::query()
            ->with([
                'student:id,school_id,first_name,middle_name,last_name,email,photo_path,student_number',
                'program:id,name,code,education_node_id',
                'modality:id,name',
                'educationNode:id,name',
                'term:id,name,academic_year_id',
                'academicYear:id,name',
                'setting:id,end_date',
            ])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->latest()
            ->get();

        // Bucket each enrollment as "archived" (enrolled / provisionally
        // enrolled, OR enrollment window already expired) vs "active".
        // Active = still being processed by Admission / Registrar / Finance.
        $today           = \Carbon\Carbon::now();
        $archivedStatuses = [
            StudentEnrollment::STATUS_ENROLLED,
            StudentEnrollment::STATUS_REJECTED,
        ];

        $enrollments = $enrollments->filter(function ($e) use ($archivedStatuses, $today, $isArchived) {
            $isEnrolled = in_array($e->status, $archivedStatuses, true);
            $endDate    = $e->setting?->end_date
                ? \Carbon\Carbon::parse($e->setting->end_date)->endOfDay()
                : null;
            $isExpired  = $endDate && $endDate->lt($today);

            $rowIsArchived = $isEnrolled || $isExpired;

            return $isArchived ? $rowIsArchived : ! $rowIsArchived;
        })->values();

        // ------------------------------------------------------------------
        // 3. Shape each row + bucket by root education level
        // ------------------------------------------------------------------
        $rowsByLevel = [];
        foreach ($enrollments as $e) {
            $programNodeId = $e->program?->education_node_id;
            $rootLevelId   = $nodeToRoot[$e->education_node_id ?? null]
                ?? $nodeToRoot[$programNodeId ?? null]
                ?? null;

            $st       = $e->student;
            $fullName = trim(($st->first_name ?? '').' '.($st->middle_name ?? '').' '.($st->last_name ?? ''));
            $fullName = $fullName !== '' ? $fullName : '— No name —';
            $initials = strtoupper(substr($st->first_name ?? '?', 0, 1).substr($st->last_name ?? '', 0, 1));
            $photo    = $st?->photo_path ? asset('storage/'.$st->photo_path) : null;

            $avatar = $photo
                ? '<img src="'.e($photo).'" style="width:36px;height:36px;border-radius:9999px;object-fit:cover;">'
                : '<div style="width:36px;height:36px;border-radius:9999px;background:#f1f5f9;border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#64748b;">'.e($initials).'</div>';

            $applicantCell =
                '<div style="display:flex;align-items:center;gap:10px;">'
                . $avatar
                . '<div style="min-width:0;">'
                .   '<div style="font-weight:600;color:#1e293b;">'.e($fullName).'</div>'
                . '</div></div>';;

            [$statusLabel, $statusBg, $statusFg] = $this->applicantStatusPill($e->status);
            $statusCell = '<span style="display:inline-block;padding:2px 10px;border-radius:9999px;font-size:11px;font-weight:700;background:'.$statusBg.';color:'.$statusFg.';">'.$statusLabel.'</span>';

            $programLbl = \App\Support\EnrollmentProgramLabel::for($e);

            // Year/Term cell — mirrors the Transcript of Records logic for the
            // year/grade prefix, then appends the term + academic year:
            //   Basic Education : "Grade {N} - {Term} {AY}"
            //   Other levels    : "Year {N} - {Term} {AY}"
            $yearLevelLbl = $this->formatYearTerm($e, $rootLevelId);

            $row = (object) [
                'id'             => $e->id,
                'applicant'      => $applicantCell,
                'student_number' => $st?->student_number ?? '—',
                'program'        => $programLbl,
                'enrollee_type'  => ucfirst(str_replace('_', ' ', (string) ($e->student_type ?? $e->enrollee_type ?? '—'))),
                'year_level'     => $yearLevelLbl,
                'status'         => $statusCell,
                'submitted_at'   => $e->created_at?->format('M d, Y') ?? '—',
                '_term'          => $this->formatTermDisplay($e),
            ];

            $bucket = $rootLevelId ?? 0;
            $rowsByLevel[$bucket][] = $row;
        }

        // Rows for the active tab (or every row when the school has just one
        // level, or when the user clicked the "All" pill next to the tab).
        $activeRows = collect(
            ($showTabs && ! $showAll)
                ? ($rowsByLevel[$activeLevelId] ?? [])
                : array_merge(...array_values($rowsByLevel) ?: [[]])
        );

        $columns = config('tables.applicants.columns', []);
        $activeLevel = $levels->firstWhere('id', $activeLevelId);
        $levelTitle  = $showAll
            ? 'All Levels'
            : ($activeLevel?->name ?? ($levels->first()->name ?? null));

        // Build the tabs array consumed by resources/views/partials/tabs.blade.php.
        // A single "Education Level" tab whose `children` are the offered levels
        // (rendered as the standard Alpine dropdown used everywhere else).
        $applicantTabs = $showTabs
            ? [[
                'label'  => $showAll
                    ? 'Education Level: All'
                    : 'Education Level'.($activeLevel ? ': '.$activeLevel->name : ''),
                'route'  => $routeName,
                'active' => $routeName.'*',
                'children' => $levels->map(fn ($lvl) => [
                    'label'  => $lvl->name.' ('.count($rowsByLevel[$lvl->id] ?? []).')',
                    'route'  => $routeName,
                    'params' => ['level' => $lvl->id],
                    'active' => $routeName,
                    'active_query' => [
                        'key'   => 'level',
                        'value' => (string) $lvl->id,
                    ],
                ])->all(),
            ]]
            : [];

        return view('admission.applicants', [
            'columns'       => $columns,
            'levels'        => $levels,
            'applicantTabs' => $applicantTabs,
            'activeLevelId' => $activeLevelId,
            'showAll'       => $showAll,
            'showTabs'      => $showTabs,
            'levelTitle'    => $levelTitle,
            'rows'          => $activeRows,
            'counts'        => collect($rowsByLevel)->map(fn ($r) => count($r)),
            'total'         => $enrollments->count(),
            'isArchived'    => $isArchived,
            'routeName'     => $routeName,
            'activeTerms'   => $activeRows
                ->pluck('_term')
                ->filter(fn ($t) => $t && $t !== '—')
                ->unique()
                ->values(),
        ]);
    }

    /**
     * Format the enrollment's year/term cell. The year prefix follows the
     * same rule as the Transcript of Records (Basic Ed → "Grade {N}",
     * everything else → "Year {N}") and is suffixed with the term name
     * and academic year when present.
     *
     *   Basic Education : "Grade 4 - Semester 2 2026-2027"
     *   Other levels    : "Year 1 - 2nd Semester 2026-2027"
     */
    protected function formatYearTerm(\App\Models\StudentEnrollment $e, ?int $rootLevelId): string
    {
        $yr = $e->year_level;

        $isBasicEd = false;
        if ($rootLevelId) {
            $rootName = \Illuminate\Support\Facades\DB::table('education_nodes')
                ->where('id', $rootLevelId)
                ->value('name');
            $isBasicEd = str_contains(strtolower((string) $rootName), 'basic');
        }

        $yearPart = null;
        if ($yr !== null && $yr !== '' && $yr !== '—') {
            $yearPart = is_numeric($yr)
                ? ($isBasicEd ? 'Grade ' : 'Year ').(int) $yr
                : (string) $yr;
        }

        $termPart = trim((string) ($e->term?->name ?? ''));

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

    /**
     * Format the enrollment's year level using the same rule as the
     * Transcript of Records: Basic Education rows render as "Grade {N}",
     * every other level renders as "Year {N}".
     */
    protected function formatYearLevel(\App\Models\StudentEnrollment $e, ?int $rootLevelId): string
    {
        $yr = $e->year_level;
        if ($yr === null || $yr === '' || $yr === '—') {
            return '—';
        }

        if (! is_numeric($yr)) {
            return (string) $yr;
        }

        $isBasicEd = false;
        if ($rootLevelId) {
            $rootName = \Illuminate\Support\Facades\DB::table('education_nodes')
                ->where('id', $rootLevelId)
                ->value('name');
            $isBasicEd = str_contains(strtolower((string) $rootName), 'basic');
        }

        return ($isBasicEd ? 'Grade ' : 'Year ').(int) $yr;
    }

    /**
     * Compose a human-readable term label, e.g. "First Semester — AY 2025-2026".
     */
    protected function formatTermDisplay(\App\Models\StudentEnrollment $e): string
    {
        $term = $e->term?->name;
        if (! $term) {
            return '—';
        }
        $ay = $e->academicYear?->name;
        return $ay ? $term.' — AY '.$ay : $term;
    }

    /**
     * Walk every education_node and resolve its root ancestor id so we can
     * classify any program / education_node_id into a top-level offered level.
     */
    protected function buildNodeRootMap(): array
    {
        $all = \Illuminate\Support\Facades\DB::table('education_nodes')
            ->get(['id', 'parent_id'])
            ->keyBy('id');

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

    /** Status pill colours for the applicant directory. */
    protected function applicantStatusPill(?string $status): array
    {
        return match (strtolower((string) $status)) {
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
            default          => ['Unknown',        '#f1f5f9', '#475569'],
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Interviews
    |--------------------------------------------------------------------------
    */

    public function interviews()
    {
        return view('admission.interviews');
    }

    /*
    |--------------------------------------------------------------------------
    | Documents
    |--------------------------------------------------------------------------
    */

    public function documents()
    {
        return view('admission.documents');
    }

    /*
    |--------------------------------------------------------------------------
    | Enrollment
    |--------------------------------------------------------------------------
    */

    public function enrollment()
    {
        return view('public.enrolment_form');
    }


    /*
    |--------------------------------------------------------------------------
    | Reports
    |--------------------------------------------------------------------------
    */

    public function reports()
    {
        return view('admission.reports');
    }

    /*
    |--------------------------------------------------------------------------
    | Communications
    |--------------------------------------------------------------------------
    */

    public function communications()
    {
        return view('admission.communications');
    }

    /*
    |--------------------------------------------------------------------------
    | Settings
    |--------------------------------------------------------------------------
    */

    public function settings()
    {
        return view('admission.settings');
    }

    /*
    |--------------------------------------------------------------------------
    | Global Search
    |--------------------------------------------------------------------------
    */

    public function search(Request $request)
    {
        $query = $request->get('q');

        if (!$query) {
            return response()->json([]);
        }

        // Search Students
        $students = Student::where('first_name', 'like', "%{$query}%")
            ->orWhere('last_name', 'like', "%{$query}%")
            ->limit(5)
            ->get()
            ->map(function ($student) {
                return [
                    'id' => $student->id,
                    'name' => $student->first_name . ' ' . $student->last_name,
                    'type' => 'student',
                    'url' => route('admission.students')
                ];
            });

        // Search Applications via Student relationship
        $applications = Application::with('student')
            ->whereHas('student', function ($q) use ($query) {
                $q->where('first_name', 'like', "%{$query}%")
                  ->orWhere('last_name', 'like', "%{$query}%");
            })
            ->limit(5)
            ->get()
            ->map(function ($app) {
                return [
                    'id' => $app->id,
                    'name' => $app->student->first_name . ' ' . $app->student->last_name,
                    'type' => 'application',
                    'url' => route('admission.applications')
                ];
            });

        return response()->json([
            'results' => $students->merge($applications)
        ]);
    }
}
