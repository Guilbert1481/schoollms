<?php

namespace App\Modules\AcadEnrolment\Services\Contracts;

interface EnrolmentValidator
{
    /**
     * Identifier used in logs / API responses: e.g. "units_range".
     */
    public function key(): string;

    public function validate(EnrolmentContext $ctx): ValidationResult;
}
