<?php

namespace App\Support\Tenancy;

use App\Models\School;

/**
 * Per-request holder for the school resolved from the request Host header.
 *
 * Bound as a singleton and (re)set on every request by the
 * ResolveSchoolFromHost middleware — including to null on platform/central
 * hosts. Read it through the current_school() helper or by resolving this
 * class from the container.
 */
class CurrentSchool
{
    protected ?School $school = null;

    public function set(?School $school): void
    {
        $this->school = $school;
    }

    public function get(): ?School
    {
        return $this->school;
    }

    public function check(): bool
    {
        return $this->school !== null;
    }

    public function id(): ?int
    {
        return $this->school?->id;
    }
}
