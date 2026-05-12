<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\TrainingEnrollment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentService
{
    public function recordGeneralPayment(
        User $actor,
        float $amount,
        string $paymentMethod,
        string $paymentType = 'miscellaneous',
        ?string $referenceNumber = null,
        ?int $studentId = null,
        ?int $schoolId = null
    ): Payment {
        return DB::transaction(function () use ($actor, $amount, $paymentMethod, $paymentType, $referenceNumber, $studentId, $schoolId) {
            return $this->createPayment(
                schoolId: (int) ($schoolId ?? $actor->school_id),
                studentId: (int) ($studentId ?? $actor->id),
                amount: $amount,
                paymentMethod: $paymentMethod,
                paymentType: $paymentType,
                referenceNumber: $referenceNumber,
                trainingEnrollmentId: null,
                referencePrefix: 'PAY-'
            );
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
        string $referencePrefix
    ): Payment {
        return Payment::create([
            'school_id' => $schoolId,
            'student_id' => $studentId,
            'training_enrollment_id' => $trainingEnrollmentId,
            'amount' => $amount,
            'reference_number' => $referenceNumber ?: ($referencePrefix . strtoupper(Str::random(10))),
            'payment_method' => $paymentMethod,
            'payment_type' => $paymentType,
            'paid_at' => now(),
        ]);
    }
}
