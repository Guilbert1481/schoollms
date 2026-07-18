<?php

namespace App\Policies;

use App\Models\EnrollmentDocument;
use App\Models\User;

/**
 * Ownership rule for registrar-required enrolment documents (Roadmap A1/C2):
 * the owning student's account, same-school document-handling staff, or
 * superadmin. Serve-side call sites use 404 so a foreign document ID is
 * indistinguishable from a nonexistent one.
 */
class EnrollmentDocumentPolicy
{
    private const STAFF_ROLES = ['admin', 'registrar', 'admission_manager', 'finance_manager'];

    public function view(User $user, EnrollmentDocument $document): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        $student = $document->enrollment?->student;
        if ($student === null) {
            return false;
        }

        if ((int) $student->user_id === (int) $user->id) {
            return true;
        }

        return in_array($user->role, self::STAFF_ROLES, true)
            && (int) $document->school_id === (int) $user->school_id;
    }
}
