<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AdmissionExamSetting;
use App\Models\EducationNode;
use App\Models\EnrollmentDraft;
use App\Models\Modality;
use App\Models\PaymentPlan;
use App\Models\Program;
use App\Models\SchoolProfile;
use App\Models\Student;
use App\Models\StudentAcademicBackground;
use App\Models\StudentEnrollment;
use App\Models\StudentHealthRecord;
use App\Services\Finance\PaymentScheduleService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class ApplicationsController extends Controller
{
    /**
     * Human-readable labels for each StudentEnrollment status.
     */
    protected array $statusLabels = [
        StudentEnrollment::STATUS_DRAFT                  => 'Draft',
        StudentEnrollment::STATUS_SUBMITTED              => 'Submitted',
        StudentEnrollment::STATUS_EXAM_PASSED            => 'Exam Passed',
        StudentEnrollment::STATUS_EXAM_FAILED            => 'Exam Failed',
        StudentEnrollment::STATUS_ASSESSED               => 'Assessed',
        StudentEnrollment::STATUS_PROVISIONAL            => 'Provisional',
        StudentEnrollment::STATUS_REJECTED               => 'Rejected',
        StudentEnrollment::STATUS_SENT_BILLING           => 'Sent to Billing',
        StudentEnrollment::STATUS_BILLED                 => 'Billed',
        StudentEnrollment::STATUS_PARTIALLY_PAID         => 'Partially Paid',
        StudentEnrollment::STATUS_ENROLLED               => 'Enrolled',
        StudentEnrollment::STATUS_PROVISIONALLY_ENROLLED => 'Provisionally Enrolled',
        StudentEnrollment::STATUS_DROPPED                => 'Dropped',
        StudentEnrollment::STATUS_CANCELLED              => 'Cancelled',
        StudentEnrollment::STATUS_COMPLETED              => 'Completed',
    ];

    /**
     * Statuses where the application is still editable by the student.
     */
    protected array $editableStatuses = [
        StudentEnrollment::STATUS_DRAFT,
        StudentEnrollment::STATUS_SUBMITTED,
        StudentEnrollment::STATUS_REJECTED,
        StudentEnrollment::STATUS_EXAM_FAILED,
    ];

    /**
     * List all of the current student's enrolment applications. Records
     * persist permanently — they never disappear after submission.
     */
    public function index()
    {
        $student = Auth::user()?->student;
        if (! $student) {
            return redirect()->route('student.dashboard');
        }

        $rows = StudentEnrollment::query()
            ->with([
                'term:id,name,term,academic_year_id',
                'academicYear:id,name',
                'program:id,name',
            ])
            ->where('student_id', $student->id)
            ->orderByDesc('id')
            ->get()
            ->map(function (StudentEnrollment $e) {
                $ay   = $e->academicYear?->name ?? '—';
                $term = $e->term?->name ?? ($e->term?->term ? 'Term '.$e->term->term : '—');
                return (object) [
                    'id'              => $e->id,
                    'reference'       => 'ENR-'.str_pad((string) $e->id, 6, '0', STR_PAD_LEFT),
                    'academic_year'   => $ay,
                    'term_label'      => $term,
                    'program_label'   => $e->program?->name ?? '—',
                    'year_level'      => $e->year_level ?? '—',
                    'student_type'    => $e->student_type ?? '—',
                    'status'          => $e->status,
                    'status_label'    => $this->statusLabels[$e->status] ?? ucfirst((string) $e->status),
                    'submitted_at'    => $e->created_at,
                    'is_editable'     => in_array($e->status, $this->editableStatuses, true),
                    'term_id'         => $e->term_id,
                ];
            });

        return view('student.applications.index', [
            'rows' => $rows,
        ]);
    }

    /**
     * Send the student back to the enrolment wizard for the given
     * enrollment's term. The wizard will rehydrate from the saved draft.
     */
    public function edit(int $id)
    {
        $student = Auth::user()?->student;
        $enrollment = StudentEnrollment::where('student_id', optional($student)->id)
            ->findOrFail($id);

        return redirect()->route('public.apply.show', ['term' => $enrollment->term_id]);
    }

    /**
     * Stream the application PDF (A4) inline for the given enrollment.
     */
    public function pdf(int $id)
    {
        $student = Auth::user()?->student;
        if (! $student) {
            abort(403);
        }

        $enrollment = StudentEnrollment::with(['term', 'academicYear'])
            ->where('student_id', $student->id)
            ->findOrFail($id);

        $term = $enrollment->term;

        // Reconstruct pathway: prefer the saved draft, fall back to fields
        // stored on the enrollment itself.
        $draft = EnrollmentDraft::where('student_id', $student->id)
            ->where('term_id', $enrollment->term_id)
            ->first();

        $draftData = is_array($draft?->data) ? $draft->data : json_decode((string) $draft?->data, true);
        $pathway   = is_array($draftData['pathway'] ?? null) ? $draftData['pathway'] : [
            'program_id'         => $enrollment->program_id,
            'modality_id'        => $enrollment->modality_id,
            'education_node_id'  => $enrollment->education_node_id,
            'student_type'       => $enrollment->student_type,
            'year_level'         => $enrollment->year_level,
        ];

        $program  = ! empty($pathway['program_id'])        ? Program::find($pathway['program_id'])           : null;
        $modality = ! empty($pathway['modality_id'])       ? Modality::find($pathway['modality_id'])         : null;
        $node     = ! empty($pathway['education_node_id']) ? EducationNode::find($pathway['education_node_id']) : null;
        $skipped  = ($pathway['student_type'] ?? null) === 'regular';

        $backgrounds = $skipped
            ? collect()
            : StudentAcademicBackground::where('student_id', $student->id)
                ->orderBy('year_ended', 'desc')->get();

        $parents          = $student->guardians()->whereIn('type', ['parent', 'guardian'])->get();
        $emergencyContact = $student->guardians()->where('is_emergency_contact', true)->first();

        $pdf = Pdf::loadView('acad_enrolment.pdf.enrollment', [
            'enrollment'       => $enrollment,
            'student'          => $student,
            'term'             => $term,
            'pathway'          => $pathway,
            'program'          => $program,
            'modality'         => $modality,
            'node'             => $node,
            'backgrounds'      => $backgrounds,
            'skipped'          => $skipped,
            'parents'          => $parents,
            'emergencyContact' => $emergencyContact,
            'school'           => $student->school,
        ])->setPaper('a4');

        $filename = sprintf(
            'ENR-%s.pdf',
            str_pad((string) $enrollment->id, 6, '0', STR_PAD_LEFT),
        );

        return $pdf->stream($filename);
    }

    /**
     * Full letterhead preview — the SAME design as the enrolment form's
     * "Preview Application" modal — rendered read-only from a saved application.
     */
    public function view(int $id)
    {
        $student = Auth::user()?->student;
        if (! $student) {
            abort(403);
        }

        $enrollment = StudentEnrollment::with('term')
            ->where('student_id', $student->id)
            ->findOrFail($id);

        $term = $enrollment->term;

        $draft     = EnrollmentDraft::where('student_id', $student->id)->where('term_id', $enrollment->term_id)->first();
        $draftData = is_array($draft?->data) ? $draft->data : [];
        $pathway   = is_array($draftData['pathway'] ?? null) ? $draftData['pathway'] : [
            'program_id'        => $enrollment->program_id,
            'modality_id'       => $enrollment->modality_id,
            'education_node_id'  => $enrollment->education_node_id,
            'student_type'      => $enrollment->student_type,
            'year_level'        => $enrollment->year_level,
        ];

        $program  = ! empty($pathway['program_id'])        ? Program::find($pathway['program_id'])            : null;
        $modality = ! empty($pathway['modality_id'])       ? Modality::find($pathway['modality_id'])          : null;
        $node     = ! empty($pathway['education_node_id']) ? EducationNode::find($pathway['education_node_id']) : null;
        $skipped  = ($pathway['student_type'] ?? null) === 'regular';

        // Education-level chain (root › … › node), same as the review page.
        $chain  = [];
        $cursor = $node;
        for ($i = 0; $i < 20 && $cursor; $i++) {
            array_unshift($chain, $cursor);
            $cursor = $cursor->parent_id ? EducationNode::find($cursor->parent_id) : null;
        }
        $rootLevel = $chain[0] ?? null;
        $pathLabel = collect($chain)->skip(1)->pluck('name')->implode(' › ');

        $backgrounds = $skipped
            ? collect()
            : StudentAcademicBackground::where('student_id', $student->id)->orderBy('year_ended', 'desc')->get();
        $parents          = $student->guardians()->whereIn('type', ['parent', 'guardian'])->get();
        $emergencyContact = $student->guardians()->where('is_emergency_contact', true)->first();
        $health           = StudentHealthRecord::where('student_id', $student->id)->first();
        $profile          = SchoolProfile::where('school_id', $student->school_id)->first();

        // Fees + chosen plan + best-effort diagnostic scholarship (from the draft
        // score if still available) — mirrors the Financial Assessment step.
        $svc = new PaymentScheduleService();
        $preview = new StudentEnrollment([
            'school_id'         => $student->school_id,
            'academic_year_id'  => $term?->academic_year_id,
            'term_id'           => $enrollment->term_id,
            'program_id'        => $pathway['program_id'] ?? null,
            'education_node_id'  => $pathway['education_node_id'] ?? null,
            'year_level'        => $pathway['year_level'] ?? null,
        ]);
        $fees = $svc->feePreview($preview);

        $scholarship = $this->scholarshipFor((int) $student->school_id, $draftData['diagnostic']['score'] ?? null, $fees);
        $netTotal    = round(((float) $fees['total']) - ($scholarship['amount'] ?? 0), 2);

        $financialComp = null;
        if ($enrollment->payment_plan_id && $enrollment->payment_option) {
            $plan = PaymentPlan::where('school_id', $student->school_id)->find($enrollment->payment_plan_id);
            if ($plan) {
                $financialComp = $svc->computeForOption($plan, $enrollment->payment_option, $netTotal, $term);
            }
        }

        return view('student.applications.preview', [
            'enrollment'       => $enrollment,
            'term'             => $term,
            'student'          => $student,
            'pathway'          => $pathway,
            'program'          => $program,
            'modality'         => $modality,
            'node'             => $node,
            'rootLevel'        => $rootLevel,
            'pathLabel'        => $pathLabel,
            'skipped'          => $skipped,
            'backgrounds'      => $backgrounds,
            'parents'          => $parents,
            'emergencyContact' => $emergencyContact,
            'health'           => $health,
            'profile'          => $profile,
            'fees'             => $fees,
            'financialComp'    => $financialComp,
            'scholarship'      => $scholarship,
            'netTotal'         => $netTotal,
            'certifiedBy'      => $enrollment->certified_by ?: 'student',
            'acknowledged'     => (bool) $enrollment->acknowledged_accuracy,
            'privacyConsent'   => (bool) $enrollment->data_privacy_consent,
            'signDate'         => $enrollment->certified_at ?: $enrollment->created_at,
        ]);
    }

    /** Best-effort diagnostic scholarship (mirrors EnrollmentController::diagnosticScholarship). */
    protected function scholarshipFor(int $schoolId, $score, array $fees): ?array
    {
        if ($score === null) {
            return null;
        }
        $band = AdmissionExamSetting::where('school_id', $schoolId)->first()?->scholarshipForScore((int) $score);
        if (! $band) {
            return null;
        }
        $tuition = collect($fees['items'] ?? [])
            ->filter(fn ($i) => strcasecmp((string) ($i->fee_type ?? ''), 'Tuition') === 0)
            ->sum(fn ($i) => (float) $i->amount);
        $base   = ($band['apply_to'] ?? 'total') === 'tuition' ? (float) $tuition : (float) ($fees['total'] ?? 0);
        $amount = round($base * ((float) ($band['percent'] ?? 0)) / 100, 2);

        return [
            'label'    => $band['label'] ?? 'Scholarship',
            'percent'  => (float) ($band['percent'] ?? 0),
            'apply_to' => $band['apply_to'] ?? 'total',
            'amount'   => $amount,
        ];
    }
}
