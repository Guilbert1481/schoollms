<?php

namespace App\Http\Middleware;

use App\Models\GradeSetting;
use App\Services\Dashboard\StudentDashboardService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates a student grade-view page behind the Principal's per-school toggle
 * (Settings → Grades → Student Grade). Applied to the Report Card ("Grades")
 * and Transcript ("Form 137") routes.
 *
 * Only basic-education students are gated — the Principal governs basic ed, so
 * higher-ed students (the Dean's domain) and students without an active
 * basic-ed enrollment pass through untouched. A view whose toggle is off (the
 * default) is blocked with 403, so hiding the sidebar item cannot be bypassed
 * by typing the URL.
 *
 * Usage: ->middleware('student.grade-view:grades')   // or :form137
 */
class EnsureStudentGradeViewEnabled
{
    public function __construct(private StudentDashboardService $students) {}

    public function handle(Request $request, Closure $next, string $view): Response
    {
        $user = $request->user();

        // Non-basic-ed students (including those with no active enrollment) are
        // outside the Principal's scope — never gated here.
        if ($user && $this->students->isBasicEd($user)) {
            $setting = GradeSetting::where('school_id', (int) ($user->school_id ?? 0))->first();

            $enabled = match ($view) {
                'grades' => (bool) ($setting->show_student_grades ?? false),
                'form137' => (bool) ($setting->show_student_form137 ?? false),
                default => false,
            };

            abort_unless($enabled, 403, 'This page is not available for your account right now.');
        }

        return $next($request);
    }
}
