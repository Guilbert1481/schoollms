<?php

namespace App\Policies;

use App\Models\StatementOfAccount;
use App\Models\User;

/**
 * Ownership rule for Statements of Account (Roadmap A1): the student the
 * statement was issued to (`student_id` = users.id), same-school
 * finance-side staff, or superadmin.
 */
class StatementOfAccountPolicy
{
    private const STAFF_ROLES = ['admin', 'finance_manager', 'registrar'];

    public function view(User $user, StatementOfAccount $statement): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        if ($user->isStudent()) {
            return (int) $statement->student_id === (int) $user->id;
        }

        return in_array($user->role, self::STAFF_ROLES, true)
            && (int) $statement->school_id === (int) $user->school_id;
    }
}
