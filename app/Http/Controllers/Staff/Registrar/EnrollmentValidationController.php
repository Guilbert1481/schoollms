<?php

namespace App\Http\Controllers\Staff\Registrar;

use App\Http\Controllers\Controller;
use App\Models\FinanceSetting;
use App\Models\StudentEnrollment;
use App\Services\Finance\InvoiceService;
use App\Support\EducationLevels;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Registrar's enrollment-validation queue.
 *
 * Workflow:
 *  - Admission Manager opens the enrolment window and publishes sections.
 *  - Students submit enrolment (status = 'submitted').
 *  - Registrar reviews here -> approves (status = 'validated') or rejects.
 *  - Approved enrolments then move on to billing/payment gates.
 */
class EnrollmentValidationController extends Controller
{
    public function __construct(private readonly InvoiceService $invoices)
    {
    }

    public function index(Request $request)
    {
        $schoolId = auth()->user()->school_id ?? null;

        $offeredRoots = EducationLevels::offeredRoots();
        $nodeToRoot   = EducationLevels::nodeRootMap();

        // Pending-validation enrollments (status = submitted), with the fields
        // needed to bucket each one by education level.
        $pending = StudentEnrollment::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('status', StudentEnrollment::STATUS_SUBMITTED)
            ->with([
                'student:id,first_name,last_name,student_number',
                'program:id,name,code,education_node_id',
            ])
            ->orderByDesc('created_at')
            ->get();

        $rootOf = fn ($e) => $nodeToRoot[$e->education_node_id ?? null]
            ?? $nodeToRoot[optional($e->program)->education_node_id ?? null]
            ?? null;

        // Per-level counts for the red tab badges.
        $counts = [];
        foreach ($pending as $e) {
            $r = (int) ($rootOf($e) ?? 0);
            $counts[$r] = ($counts[$r] ?? 0) + 1;
        }

        $activeLevelId      = (int) ($request->integer('level') ?: ($offeredRoots->first()->id ?? 0));
        $activeLevel        = $offeredRoots->firstWhere('id', $activeLevelId);
        $activeLevelIsBasic = $activeLevel && EducationLevels::isBasic($activeLevel->name);

        $yearLevel = $request->query('year_level');
        $yearLevel = ($yearLevel === null || $yearLevel === '') ? null : (string) $yearLevel;
        $programId = (int) $request->integer('program_id');

        $rows = $pending
            ->filter(fn ($e) => (int) ($rootOf($e) ?? 0) === $activeLevelId)
            ->when($yearLevel !== null, fn ($c) => $c->filter(fn ($e) => (string) $e->year_level === $yearLevel))
            ->when($programId, fn ($c) => $c->filter(fn ($e) => (int) $e->program_id === $programId))
            ->map(function ($e) use ($activeLevelIsBasic) {
                $last  = optional($e->student)->last_name;
                $first = optional($e->student)->first_name;
                $name  = trim(trim((string) $last).', '.trim((string) $first), ', ');

                return (object) [
                    'id'             => $e->id,
                    'student_number' => optional($e->student)->student_number ?: '—',
                    'name'           => $name !== '' ? $name : '—',
                    'grade_level'    => $activeLevelIsBasic && is_numeric($e->year_level)
                        ? 'Grade '.(int) $e->year_level
                        : ($e->year_level ? 'Year '.(int) $e->year_level : '—'),
                    'program'        => optional($e->program)->code ?: (optional($e->program)->name ?: '—'),
                    'enrollee_type'  => $e->enrollee_type ? ucfirst((string) $e->enrollee_type) : '—',
                    'total_units'    => $e->total_units ?: '—',
                    'status'         => '<span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-bold text-amber-700">Submitted</span>',
                ];
            })
            ->values();

        // Columns vary by level: Basic shows Grade Level (no Program); higher
        // levels show Program + Year Level.
        $columns = $activeLevelIsBasic
            ? [
                ['key' => 'student_number', 'label' => 'Student #',   'width' => '140px'],
                ['key' => 'name',           'label' => 'Name',        'width' => 'auto'],
                ['key' => 'grade_level',    'label' => 'Grade Level', 'width' => '140px'],
                ['key' => 'enrollee_type',  'label' => 'Type',        'width' => '130px'],
                ['key' => 'status',         'label' => 'Status',      'width' => '160px', 'raw' => true],
            ]
            : [
                ['key' => 'student_number', 'label' => 'Student #',  'width' => '140px'],
                ['key' => 'name',           'label' => 'Name',       'width' => 'auto'],
                ['key' => 'program',        'label' => 'Program',    'width' => '200px'],
                ['key' => 'grade_level',    'label' => 'Year Level', 'width' => '120px'],
                ['key' => 'total_units',    'label' => 'Units',      'width' => '90px'],
                ['key' => 'status',         'label' => 'Status',     'width' => '160px', 'raw' => true],
            ];

        // Filter option sets.
        $gradeOptions = EducationLevels::basicGradeOptions();
        $yearOptions  = $pending
            ->filter(fn ($e) => (int) ($rootOf($e) ?? 0) === $activeLevelId && $e->year_level)
            ->map(fn ($e) => (int) $e->year_level)->unique()->sort()->values()
            ->mapWithKeys(fn ($y) => [(string) $y => 'Year '.$y])->all();
        $programOptions = DB::table('programs')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('code')->orderBy('name')
            ->get(['id', 'code', 'name'])
            ->mapWithKeys(fn ($p) => [(int) $p->id => $p->code ?: $p->name])->all();

        $actions = [
            ['type' => 'link', 'label' => 'Review', 'route' => 'registrar.enrollments.show', 'class' => 'bg-indigo-600 text-white hover:bg-indigo-700'],
        ];

        return view('registrar.enrollments.index', [
            'offeredRoots'       => $offeredRoots,
            'counts'             => $counts,
            'activeLevelId'      => $activeLevelId,
            'activeLevel'        => $activeLevel,
            'activeLevelIsBasic' => $activeLevelIsBasic,
            'rows'               => $rows,
            'columns'            => $columns,
            'actions'            => $actions,
            'gradeOptions'       => $gradeOptions,
            'yearOptions'        => $yearOptions,
            'programOptions'     => $programOptions,
            'yearLevel'          => $yearLevel,
            'programId'          => $programId ?: null,
        ]);
    }

    public function show(StudentEnrollment $enrollment)
    {
        $enrollment->load(['student', 'program', 'term']);

        return view('registrar.enrollments.show', compact('enrollment'));
    }

    public function validateEnrollment(Request $request, StudentEnrollment $enrollment)
    {
        $request->validate(['remarks' => 'nullable|string|max:1000']);

        $enrollment->update([
            'status' => StudentEnrollment::STATUS_ASSESSED,
            'approval_level' => 'registrar',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'remarks' => $request->input('remarks') ?: $enrollment->remarks,
        ]);

        $this->sendToBilling($enrollment, 'assessed');

        return back()->with('success', 'Enrollment assessed. Statement of Account sent to the applicant.');
    }

    /**
     * Registrar conditionally clears an applicant whose documents are
     * incomplete or pending. Status becomes "provisional", a SOA is
     * issued and — after payment — the applicant becomes
     * "Provisionally Enrolled" rather than "Enrolled".
     */
    public function approveProvisionally(Request $request, StudentEnrollment $enrollment)
    {
        $request->validate(['remarks' => 'nullable|string|max:1000']);

        $enrollment->update([
            'status' => StudentEnrollment::STATUS_PROVISIONAL,
            'approval_level' => 'registrar',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'remarks' => $request->input('remarks') ?: $enrollment->remarks,
        ]);

        $this->sendToBilling($enrollment, 'provisional');

        return back()->with('success', 'Enrollment approved provisionally. Statement of Account sent to the applicant.');
    }

    public function reject(Request $request, StudentEnrollment $enrollment)
    {
        $request->validate(['remarks' => 'required|string|max:1000']);

        $enrollment->update([
            'status' => StudentEnrollment::STATUS_REJECTED,
            'approval_level' => 'registrar',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'remarks' => $request->input('remarks'),
        ]);

        return back()->with('success', 'Enrollment rejected.');
    }

    /**
     * Hand the enrollment off to the Finance Manager by marking it as
     * "sent_billing". Once a payment is recorded, the FinanceBillingQueue
     * controller transitions the status to enrolled / provisionally_enrolled.
     *
     * The current $enrollment->status (assessed|provisional) is preserved in
     * the remarks audit trail so we can recover the correct final state.
     */
    protected function sendToBilling(StudentEnrollment $enrollment, string $clearedAs): void
    {
        $enrollment->update([
            'status'             => StudentEnrollment::STATUS_SENT_BILLING,
            'billing_cleared_as' => $clearedAs,
            // Give the student a payment deadline (7 days). The bell starts
            // flashing red within URGENT_THRESHOLD_DAYS and disappears once
            // payment is recorded or the window expires.
            'payment_due_at'     => now()->addDays(7),
        ]);

        // Auto-generate the invoice (and post the charge to the student ledger)
        // when finance has enabled it. Never let a billing-setup hiccup block
        // the registrar's approval flow.
        try {
            $setting = FinanceSetting::forSchool((int) $enrollment->school_id);
            if ($setting->auto_invoice_on_billing) {
                $this->invoices->generateForEnrollment(
                    $enrollment->fresh('student'),
                    actorId: (int) auth()->id(),
                );
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
