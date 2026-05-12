<?php

namespace App\Modules\AcadEnrolment\Services\Validation;

use App\Modules\AcadEnrolment\Services\Contracts\EnrolmentContext;
use App\Modules\AcadEnrolment\Services\Contracts\EnrolmentValidator;
use App\Modules\AcadEnrolment\Services\Contracts\ValidationResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Block enrolment into sections that have not been published yet.
 *
 * Sections are created in 'draft' state by the scheduler. The Registrar
 * reviews and flips them to 'published' before enrolment opens.
 */
class SectionPublishedValidator implements EnrolmentValidator
{
    public function key(): string
    {
        return 'section_published';
    }

    public function validate(EnrolmentContext $ctx): ValidationResult
    {
        if (! $ctx->sectionId) {
            return ValidationResult::pass();
        }

        // Backwards-compatible: if column doesn't exist yet, skip.
        if (! Schema::hasColumn('sections', 'status')) {
            return ValidationResult::pass();
        }

        $section = DB::table('sections')
            ->select('id', 'name', 'status')
            ->where('id', $ctx->sectionId)
            ->first();

        if (! $section) {
            return ValidationResult::fail('Selected section does not exist.', [
                'section_id' => $ctx->sectionId,
            ]);
        }

        if ($section->status !== 'published') {
            return ValidationResult::fail(
                "Section \"{$section->name}\" is not yet open for enrolment.",
                ['section_id' => $section->id, 'status' => $section->status]
            );
        }

        return ValidationResult::pass();
    }
}
