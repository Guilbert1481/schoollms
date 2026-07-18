<?php

namespace App\Services\Payments;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\TrainingEnrollment;
use App\Models\User;
use App\Services\Enrollment\EnrollmentActivationService;
use App\Services\Finance\LedgerService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        private readonly LedgerService $ledger,
        private readonly EnrollmentActivationService $enrollments,
    ) {}

    public function recordGeneralPayment(
        User $actor,
        float $amount,
        string $paymentMethod,
        string $paymentType = 'miscellaneous',
        ?string $referenceNumber = null,
        ?int $studentId = null,
        ?int $schoolId = null,
        ?int $invoiceId = null,
        ?Carbon $paidAt = null
    ): Payment {
        return DB::transaction(function () use ($actor, $amount, $paymentMethod, $paymentType, $referenceNumber, $studentId, $schoolId, $invoiceId, $paidAt) {
            $resolvedSchoolId = (int) ($schoolId ?? $actor->school_id);
            $resolvedStudentId = (int) ($studentId ?? $actor->id);

            $payment = $this->createPayment(
                schoolId: $resolvedSchoolId,
                studentId: $resolvedStudentId,
                amount: $amount,
                paymentMethod: $paymentMethod,
                paymentType: $paymentType,
                referenceNumber: $referenceNumber,
                trainingEnrollmentId: null,
                referencePrefix: 'PAY-',
                invoiceId: $invoiceId,
                paidAt: $paidAt
            );

            $invoice = $invoiceId ? Invoice::find($invoiceId) : null;

            // Mirror the payment as a credit on the student's ledger.
            $this->ledger->postPaymentCredit($payment, $invoice, (int) $actor->id);

            if ($invoice) {
                $invoice->recomputeTotals()->save();
                // Settling the first-due invoice may officially / provisionally
                // enroll the student, per the gate's document verdict.
                $this->enrollments->syncAfterPayment($invoice, (int) $actor->id);
            }

            return $payment;
        });
    }

    /**
     * Record a payment that settles a specific invoice, recomputing the
     * invoice's paid/balance/status and posting the ledger credit.
     */
    public function recordInvoicePayment(
        User $actor,
        Invoice $invoice,
        float $amount,
        string $paymentMethod,
        ?string $referenceNumber = null
    ): Payment {
        return DB::transaction(function () use ($actor, $invoice, $amount, $paymentMethod, $referenceNumber) {
            $paymentType = match (true) {
                $invoice->items()->where('fee_type', 'tuition')->exists() => 'tuition',
                default => 'miscellaneous',
            };

            $payment = $this->createPayment(
                schoolId: (int) $invoice->school_id,
                studentId: (int) $invoice->student_id,
                amount: $amount,
                paymentMethod: $paymentMethod,
                paymentType: $paymentType,
                referenceNumber: $referenceNumber,
                trainingEnrollmentId: null,
                referencePrefix: 'PAY-',
                invoiceId: (int) $invoice->id
            );

            $this->ledger->postPaymentCredit($payment, $invoice, (int) $actor->id);
            $invoice->recomputeTotals()->save();

            // Settling the first-due invoice may officially / provisionally
            // enroll the student, per the gate's document verdict.
            $this->enrollments->syncAfterPayment($invoice, (int) $actor->id);

            return $payment;
        });
    }

    public function payTrainingEnrollment(
        User $actor,
        TrainingEnrollment $enrollment,
        string $paymentMethod,
        ?string $referenceNumber = null
    ): TrainingEnrollment {
        return DB::transaction(function () use ($actor, $enrollment, $paymentMethod, $referenceNumber) {
            $amount = (float) ($enrollment->course?->fee ?? 0);
            $payment = $this->createPayment(
                schoolId: (int) $actor->school_id,
                studentId: (int) $actor->id,
                amount: $amount,
                paymentMethod: $paymentMethod,
                paymentType: 'training',
                referenceNumber: $referenceNumber,
                trainingEnrollmentId: (int) $enrollment->id,
                referencePrefix: 'TRN-'
            );

            $baseDate = $enrollment->session?->end_date
                ? Carbon::parse($enrollment->session->end_date)->endOfDay()
                : now();

            $enrollment->status = 'enrolled';
            $enrollment->payment_status = 'paid';
            $enrollment->payment_reference = $payment->reference_number;
            $enrollment->payment_paid_at = $payment->paid_at;
            $enrollment->enrollment_date = $enrollment->enrollment_date ?: now()->toDateString();
            $enrollment->expires_at = $baseDate->copy()->addMonthNoOverflow();
            $enrollment->save();

            return $enrollment->fresh(['course', 'session']);
        });
    }

    private function createPayment(
        int $schoolId,
        int $studentId,
        float $amount,
        string $paymentMethod,
        string $paymentType,
        ?string $referenceNumber,
        ?int $trainingEnrollmentId,
        string $referencePrefix,
        ?int $invoiceId = null,
        ?Carbon $paidAt = null
    ): Payment {
        $this->assertNotDuplicate($schoolId, $studentId, $amount, $paymentMethod, $referenceNumber, $invoiceId);

        return Payment::create([
            'school_id' => $schoolId,
            'student_id' => $studentId,
            'invoice_id' => $invoiceId,
            'training_enrollment_id' => $trainingEnrollmentId,
            'amount' => $amount,
            'reference_number' => $referenceNumber ?: ($referencePrefix.strtoupper(Str::random(10))),
            'payment_method' => $paymentMethod,
            'payment_type' => $paymentType,
            'paid_at' => $paidAt ?? now(),
        ]);
    }

    /**
     * Idempotency guard (Roadmap Phase 3): block EXACT duplicate submissions
     * before any money posts. Two rules, deliberately narrow so genuinely
     * distinct payments are never blocked:
     *
     * - With an external reference number: same student + amount + reference
     *   is the same real-world transaction at any age → block.
     * - Without one: an identical entry (student + amount + method, and
     *   invoice when given) inside a 2-minute window is a double-click /
     *   double-submit → block.
     */
    private function assertNotDuplicate(
        int $schoolId,
        int $studentId,
        float $amount,
        string $paymentMethod,
        ?string $referenceNumber,
        ?int $invoiceId
    ): void {
        $base = Payment::query()
            ->withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->where('student_id', $studentId)
            ->where('amount', round($amount, 2));

        $duplicate = $referenceNumber
            ? (clone $base)->where('reference_number', $referenceNumber)->exists()
            : (clone $base)
                ->where('payment_method', $paymentMethod)
                ->when($invoiceId, fn ($q) => $q->where('invoice_id', $invoiceId))
                ->where('created_at', '>=', now()->subMinutes(2))
                ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'amount' => $referenceNumber
                    ? 'A payment with this exact amount and reference number is already recorded for this student.'
                    : 'An identical payment for this student was recorded moments ago. If this is a genuinely separate payment, wait a moment or add its reference number.',
            ]);
        }
    }
}
