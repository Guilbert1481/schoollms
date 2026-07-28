<?php

namespace App\Http\Middleware;

use App\Models\Student;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates a student grade-view page behind the Registrar's per-student toggle
 * (Transcript of Records → Grades / Form 137). Applied to the Report Card
 * ("Grades") and Transcript ("Form 137") routes.
 *
 * Hidden by default and enforced for EVERY student — basic ed or higher ed,
 * enrolled or not — so hiding the sidebar item can't be bypassed by typing the
 * URL. The registrar grants access per student on request. Non-students pass
 * through untouched.
 *
 * Usage: ->middleware('student.grade-view:grades')   // or :form137
 */
class EnsureStudentGradeViewEnabled
{
    public function handle(Request $request, Closure $next, string $view): Response
    {
        $user = $request->user();

        if ($user && strtolower((string) $user->role) === 'student') {
            $student = Student::where('user_id', $user->id)
                ->where('school_id', (int) ($user->school_id ?? 0))
                ->first(['show_grades', 'show_form137']);

            $enabled = match ($view) {
                'grades' => (bool) ($student->show_grades ?? false),
                'form137' => (bool) ($student->show_form137 ?? false),
                default => false,
            };

            abort_unless($enabled, 403, 'This page is not available for your account right now.');
        }

        return $next($request);
    }
}
