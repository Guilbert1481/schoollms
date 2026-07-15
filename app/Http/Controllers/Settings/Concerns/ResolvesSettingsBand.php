<?php

namespace App\Http\Controllers\Settings\Concerns;

use App\Support\AcademicBand;
use Illuminate\Http\Request;

/**
 * Shared helpers for the per-level settings controllers (attendance, grading).
 * The band is read from the route's default — set by role-gated route groups,
 * never by user input — so it is the server-controlled boundary between what
 * the Principal (basic ed) and the Dean (higher ed) may configure.
 */
trait ResolvesSettingsBand
{
    protected function schoolId(): int
    {
        $id = (int) auth()->user()->school_id;
        abort_unless($id, 404);

        return $id;
    }

    protected function band(Request $request): string
    {
        $route = $request->route();
        $band = $route ? ($route->defaults['band'] ?? null) : null;
        abort_unless($band !== null && AcademicBand::isValid($band), 404);

        return $band;
    }
}
