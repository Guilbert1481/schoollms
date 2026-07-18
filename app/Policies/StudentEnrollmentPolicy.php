<?php

namespace App\Policies;

use App\Models\StudentEnrollment;
use App\Models\User;

/**
 * Ownership rule for academic enrolment records (Roadmap A1).
 *
 * `student_id` here references students.id (NOT users.id), so ownership is
 * resolved through the student record's linked user account. Staff access is
 * limited to the enrolment-pipeline roles of the same school.
 */
class StudentEnrollmentPolicy
{
    private const STAFF_ROLES = ['admin', 'registrar', 'admission_manager', 'finance_manager'];

    public function view(User $user, StudentEnrollment $enrollment): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        if ($user->isStudent()) {
            return $this->owns($user, $enrollment);
        }

        return in_array($user->role, self::STAFF_ROLES, true)
            && (int) $enrollment->school_id === (int) $user->school_id;
    }

    /**
     * Wizard-side mutations (draft steps, the post-submission admission
     * exam): the owning student only. Staff decisions go through their own
     * role-gated registrar/finance controllers, not this ability.
     */
    public function update(User $user, StudentEnrollment $enrollment): bool
    {
        return $user->isStudent() && $this->owns($user, $enrollment);
    }

    private function owns(User $user, StudentEnrollment $enrollment): bool
    {
        return (int) ($enrollment->student?->user_id) === (int) $user->id;
    }
}
