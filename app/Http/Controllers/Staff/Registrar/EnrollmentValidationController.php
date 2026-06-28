<?php

namespace App\Http\Controllers\Staff\Registrar;

use App\Http\Controllers\Controller;
use App\Models\FinanceSetting;
use App\Models\StudentEnrollment;
use App\Services\Finance\InvoiceService;
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

        $status = $request->string('status', 'submitted')->toString();
        $allowed = [
            'submitted',
            'exam_passed',
            'exam_failed',
            'assessed',
            'billed',
            'partially_paid',
            'enrolled',
            'cancelled',
            'all',
        ];
        if (! in_array($status, $allowed, true)) {
            $status = 'submitted';
        }

        $termId = $request->integer('term_id') ?: optional(
            DB::table('terms')->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->orderByDesc('is_current')
                ->orderByDesc('start_date')
                ->first()
        )->id;

        $terms = DB::table('terms')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderByDesc('start_date')
            ->get(['id', 'name']);

        $enrollments = StudentEnrollment::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($termId, fn ($q) => $q->where('term_id', $termId))
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->with(['student:id,first_name,last_name,student_number', 'program:id,name,code'])
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('registrar.enrollments.index', compact(
            'enrollments', 'terms', 'termId', 'status'
        ));
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
