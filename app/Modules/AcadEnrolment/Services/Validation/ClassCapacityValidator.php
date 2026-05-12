<?php

namespace App\Modules\AcadEnrolment\Services\Validation;

use App\Modules\AcadEnrolment\Services\Contracts\EnrolmentContext;
use App\Modules\AcadEnrolment\Services\Contracts\EnrolmentValidator;
use App\Modules\AcadEnrolment\Services\Contracts\ValidationResult;
use Illuminate\Support\Facades\DB;

/**
 * Per-class seat-count check (Higher Ed irregular enrolment).
 */
class ClassCapacityValidator implements EnrolmentValidator
{
    public function key(): string
    {
        return 'class_capacity';
    }

    public function validate(EnrolmentContext $ctx): ValidationResult
    {
        if ($ctx->selectedClassIds->isEmpty()) {
            return ValidationResult::pass();
        }

        $classes = DB::table('classes as c')
            ->join('subjects as s', 's.id', '=', 'c.subject_id')
            ->whereIn('c.id', $ctx->selectedClassIds)
            ->select(['c.id', 'c.max_students', 's.name as subject_name'])
            ->get();

        // Count enrolled seats per class in this term
        $seatCounts = DB::table('student_enrollment_subjects as ses')
            ->join('student_enrollments as se', 'se.id', '=', 'ses.student_enrollment_id')
            ->whereIn('ses.class_id', $ctx->selectedClassIds)
            ->where('se.term_id', $ctx->term->id)
            ->whereIn('se.status', ['enrolled', 'billed', 'partially_paid', 'assessed', 'submitted'])
            ->where('ses.status', 'enrolled')
            ->when($ctx->enrollment, fn ($q) => $q->where('se.id', '!=', $ctx->enrollment->id))
            ->select('ses.class_id', DB::raw('COUNT(*) as taken'))
            ->groupBy('ses.class_id')
            ->pluck('taken', 'class_id');

        $result = ValidationResult::pass();

        foreach ($classes as $class) {
            $cap = (int) ($class->max_students ?: 0);
            if ($cap <= 0) {
                continue;
            }

            $taken = (int) ($seatCounts[$class->id] ?? 0);
            if ($taken >= $cap) {
                $result->add(
                    ValidationResult::SEVERITY_FAIL,
                    "Class for \"{$class->subject_name}\" is full ({$taken}/{$cap}).",
                    ['class_id' => $class->id, 'taken' => $taken, 'capacity' => $cap]
                );
            }
        }

        return $result;
    }
}
