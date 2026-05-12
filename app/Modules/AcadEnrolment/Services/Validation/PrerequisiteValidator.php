<?php

namespace App\Modules\AcadEnrolment\Services\Validation;

use App\Modules\AcadEnrolment\Services\Contracts\EnrolmentContext;
use App\Modules\AcadEnrolment\Services\Contracts\EnrolmentValidator;
use App\Modules\AcadEnrolment\Services\Contracts\ValidationResult;
use Illuminate\Support\Facades\DB;

/**
 * Ensures every selected subject's strict prerequisites have been completed.
 *
 * "Completed" = a row exists in student_enrollment_subjects for the prerequisite
 * subject, owned by this student, with status=completed. Non-strict prerequisites
 * generate warnings instead of failures.
 */
class PrerequisiteValidator implements EnrolmentValidator
{
    public function key(): string
    {
        return 'prerequisites';
    }

    public function validate(EnrolmentContext $ctx): ValidationResult
    {
        if ($ctx->selectedClassIds->isEmpty()) {
            return ValidationResult::pass();
        }

        // Map class -> subject for friendlier error messages
        $subjects = DB::table('classes')
            ->whereIn('id', $ctx->selectedClassIds)
            ->pluck('subject_id', 'id');

        if ($subjects->isEmpty()) {
            return ValidationResult::pass();
        }

        $prereqs = DB::table('subject_prerequisites as sp')
            ->join('subjects as s', 's.id', '=', 'sp.subject_id')
            ->join('subjects as ps', 'ps.id', '=', 'sp.prerequisite_subject_id')
            ->whereIn('sp.subject_id', $subjects->values())
            ->select([
                'sp.subject_id',
                'sp.prerequisite_subject_id',
                'sp.is_strict',
                's.name as subject_name',
                'ps.name as prerequisite_name',
            ])
            ->get();

        if ($prereqs->isEmpty()) {
            return ValidationResult::pass();
        }

        // All prerequisite subject IDs the student has already passed
        $passed = DB::table('student_enrollment_subjects as ses')
            ->join('student_enrollments as se', 'se.id', '=', 'ses.student_enrollment_id')
            ->where('se.student_id', $ctx->student->id)
            ->where('ses.status', 'completed')
            ->pluck('ses.subject_id')
            ->flip();

        $result = ValidationResult::pass();

        foreach ($prereqs as $row) {
            if ($passed->has($row->prerequisite_subject_id)) {
                continue;
            }

            $severity = $row->is_strict
                ? ValidationResult::SEVERITY_FAIL
                : ValidationResult::SEVERITY_WARN;

            $result->add(
                $severity,
                "{$row->subject_name} requires prerequisite \"{$row->prerequisite_name}\" which has not been completed.",
                [
                    'subject_id'      => $row->subject_id,
                    'prerequisite_id' => $row->prerequisite_subject_id,
                ]
            );
        }

        return $result;
    }
}
