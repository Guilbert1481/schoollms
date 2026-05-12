<?php

namespace App\Modules\AcadEnrolment\Services\Validation;

use App\Modules\AcadEnrolment\Services\Contracts\EnrolmentContext;
use App\Modules\AcadEnrolment\Services\Contracts\EnrolmentValidator;
use App\Modules\AcadEnrolment\Services\Contracts\ValidationResult;
use Illuminate\Support\Facades\DB;

/**
 * Verifies the term has an active enrolment_settings row and that today
 * falls inside its [start_date, end_date] window.
 */
class TermActiveValidator implements EnrolmentValidator
{
    public function key(): string
    {
        return 'term_active';
    }

    public function validate(EnrolmentContext $ctx): ValidationResult
    {
        $setting = DB::table('enrollment_settings')
            ->where('term_id', $ctx->term->id)
            ->where('is_active', true)
            ->where('is_open', true)
            ->orderByDesc('updated_at')
            ->first();

        if (!$setting) {
            return ValidationResult::fail(
                'Enrolment is not currently open for this term.',
                ['term_id' => $ctx->term->id]
            );
        }

        $today = now()->toDateString();

        if ($setting->start_date && $today < $setting->start_date) {
            return ValidationResult::fail(
                "Enrolment opens on {$setting->start_date}.",
                ['term_id' => $ctx->term->id, 'opens' => $setting->start_date]
            );
        }

        if ($setting->end_date && $today > $setting->end_date) {
            return ValidationResult::fail(
                "Enrolment closed on {$setting->end_date}.",
                ['term_id' => $ctx->term->id, 'closed' => $setting->end_date]
            );
        }

        return ValidationResult::pass();
    }
}
