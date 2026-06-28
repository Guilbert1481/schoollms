<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\StudentEnrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Finance Manager → Billing & Payment queue for admissions.
 *
 * Workflow continuation from the Registrar:
 *   - Registrar marks enrollment as Assessed or Provisional → status becomes
 *     "sent_billing" with billing_cleared_as = assessed|provisional.
 *   - Finance Manager sees the applicant here, issues / confirms SOA, and
 *     records payment.
 *   - On payment, status moves to "enrolled" (assessed path) or
 *     "provisionally_enrolled" (provisional path).
 */
class BillingQueueController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = auth()->user()->school_id ?? null;

        $status  = $request->string('status', 'sent_billing')->toString();
        $allowed = [
            'sent_billing',
            'enrolled',
            'provisionally_enrolled',
            'all',
        ];
        if (! in_array($status, $allowed, true)) {
            $status = 'sent_billing';
        }

        $enrollments = StudentEnrollment::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($status === 'all', fn ($q) => $q->whereIn('status', [
                'sent_billing', 'enrolled', 'provisionally_enrolled',
            ]))
            ->with([
                'student:id,first_name,last_name,student_number',
                'program:id,name,code,education_node_id',
                'modality:id,name',
                'educationNode:id,name',
            ])
            ->orderByDesc('approved_at')
            ->orderByDesc('updated_at')
            ->paginate(25)
            ->withQueryString();

        return view('finance.billing_queue', compact('enrollments', 'status'));
    }

    /**
     * Finance Manager records that the applicant has paid (in full or the
     * required down payment). The enrollment is transitioned to the final
     * enrolled-state determined by how the Registrar cleared them.
     */
    public function markPaid(Request $request, StudentEnrollment $enrollment)
    {
        if ($enrollment->status !== StudentEnrollment::STATUS_SENT_BILLING) {
            return back()->with('error', 'Only applicants awaiting payment can be marked paid.');
        }

        $finalStatus = $enrollment->billing_cleared_as === 'provisional'
            ? StudentEnrollment::STATUS_PROVISIONALLY_ENROLLED
            : StudentEnrollment::STATUS_ENROLLED;

        $enrollment->update([
            'status'         => $finalStatus,
            'payment_due_at' => null,
        ]);

        return back()->with('success', $finalStatus === StudentEnrollment::STATUS_PROVISIONALLY_ENROLLED
            ? 'Payment recorded. Applicant is now Provisionally Enrolled.'
            : 'Payment recorded. Applicant is now Enrolled.');
    }
}
