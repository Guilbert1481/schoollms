<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

/**
 * Ownership rule for payment records (Roadmap A1): the paying student
 * (`student_id` = users.id), same-school finance-side staff, or superadmin.
 */
class PaymentPolicy
{
    private const STAFF_ROLES = ['admin', 'finance_manager', 'registrar'];

    public function view(User $user, Payment $payment): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        if ($user->isStudent()) {
            return (int) $payment->student_id === (int) $user->id;
        }

        return in_array($user->role, self::STAFF_ROLES, true)
            && (int) $payment->school_id === (int) $user->school_id;
    }
}
