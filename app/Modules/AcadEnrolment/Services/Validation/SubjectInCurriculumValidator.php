<?php

namespace App\Modules\AcadEnrolment\Services\Validation;

use App\Modules\AcadEnrolment\Services\Contracts\EnrolmentContext;
use App\Modules\AcadEnrolment\Services\Contracts\EnrolmentValidator;
use App\Modules\AcadEnrolment\Services\Contracts\ValidationResult;
use Illuminate\Support\Facades\DB;

/**
 * Ensures every selected subject is part of the program's curriculum
 * (or the home-school curriculum if the student is a cross-enrollee, in
 * which case CrossSchoolSubjectEquivalency may unlock host subjects).
 *
 * Subjects outside the curriculum produce a WARNING, not a failure –
 * irregular and special enrollees often legitimately take such subjects
 * with explicit dean approval.
 */
class SubjectInCurriculumValidator implements EnrolmentValidator
{
    public function key(): string
    {
        return 'subject_in_curriculum';
    }

    public function validate(EnrolmentContext $ctx): ValidationResult
    {
        if ($ctx->selectedClassIds->isEmpty()) {
            return ValidationResult::pass();
        }

        // Active curriculum for the program — most schools have exactly one;
        // we pick the most recently created if multiple are present.
        $curriculumId = DB::table('curriculums')
            ->where('program_id', $ctx->programId)
            ->orderByDesc('id')
            ->value('id');

        if (!$curriculumId) {
            return ValidationResult::pass();
        }

        $allowed = DB::table('curriculum_subjects')
            ->where('curriculum_id', $curriculumId)
            ->pluck('subject_id')
            ->flip();

        $selectedSubjects = DB::table('classes as c')
            ->join('subjects as s', 's.id', '=', 'c.subject_id')
            ->whereIn('c.id', $ctx->selectedClassIds)
            ->select(['c.subject_id', 's.name as subject_name'])
            ->get();

        $result = ValidationResult::pass();

        foreach ($selectedSubjects as $row) {
            if (!$allowed->has($row->subject_id)) {
                $result->add(
                    ValidationResult::SEVERITY_WARN,
                    "Subject \"{$row->subject_name}\" is not part of the program curriculum.",
                    ['subject_id' => $row->subject_id]
                );
            }
        }

        return $result;
    }
}
