<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;

/**
 * Access rules for a student record's sensitive artefacts (Roadmap A1).
 * Mirrors the C2 secure-document gate: the student's own account,
 * same-school document-handling staff, or superadmin.
 */
class StudentPolicy
{
    private const STAFF_ROLES = ['admin', 'registrar', 'admission_manager', 'finance_manager'];

    /** View privately-stored documents (government ID photo, …). */
    public function viewDocuments(User $user, Student $student): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        if ((int) $student->user_id === (int) $user->id) {
            return true;
        }

        return in_array($user->role, self::STAFF_ROLES, true)
            && (int) $student->school_id === (int) $user->school_id;
    }
}
