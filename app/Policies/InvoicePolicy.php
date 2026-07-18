<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

/**
 * Ownership rule for invoices (Roadmap A1).
 *
 * An invoice belongs to the student user on `student_id` (users.id). Beyond
 * the owner, only same-school finance-side staff and superadmin may see or
 * act on it. Call sites decide the HTTP status (student-facing routes use
 * 404 so foreign IDs are indistinguishable from nonexistent ones).
 */
class InvoicePolicy
{
    /** Staff roles allowed to work with any invoice of their own school. */
    private const STAFF_ROLES = ['admin', 'finance_manager', 'registrar'];

    public function view(User $user, Invoice $invoice): bool
    {
        return $this->ownerOrFinanceStaff($user, $invoice);
    }

    /**
     * Submit a checkout payment for this invoice — the owning student, or
     * same-school finance staff filing on behalf of a walk-in payer.
     */
    public function pay(User $user, Invoice $invoice): bool
    {
        return $this->ownerOrFinanceStaff($user, $invoice);
    }

    private function ownerOrFinanceStaff(User $user, Invoice $invoice): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        if ($user->isStudent()) {
            return (int) $invoice->student_id === (int) $user->id;
        }

        return in_array($user->role, self::STAFF_ROLES, true)
            && (int) $invoice->school_id === (int) $user->school_id;
    }
}
