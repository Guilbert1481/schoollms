<?php

namespace App\Http\Middleware;

use App\Models\EnrollmentSetting;
use App\Models\SchoolProfile;
use App\Models\Term;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Locks the public enrolment form for everyone (guest or student) whenever the
 * enrolment session for the requested term is not currently Active — i.e. it
 * has Closed (past its end date) or is still Upcoming. Instead of the form, a
 * "session closed" notice is shown.
 *
 * The lock is fully dynamic: it reads the session's date-derived status, so the
 * Admission Manager can reopen access simply by editing the session (extending
 * the end date) — no code change needed.
 */
class EnsureEnrollmentOpen
{
    /**
     * Routes that stay reachable after a session closes — everything that
     * happens once a student has already submitted, plus the safe exit.
     */
    protected array $allowAfterClose = [
        'public.apply.confirmation',
        'public.apply.exam',
        'public.apply.exam.submit',
        'public.apply.exit',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->route()?->getName(), $this->allowAfterClose, true)) {
            return $next($request);
        }

        $termParam = $request->route('term');
        $term = $termParam instanceof Term ? $termParam : Term::find($termParam);

        // No resolvable term → leave behaviour unchanged.
        if (! $term) {
            return $next($request);
        }

        $setting = EnrollmentSetting::where('term_id', $term->id)
            ->orderByDesc('id')
            ->first();

        // No session configured for this term → don't lock anything.
        if (! $setting) {
            return $next($request);
        }

        // Active → allow. Anything else (Closed / Upcoming) → show the notice.
        if ($setting->status === 'Active') {
            return $next($request);
        }

        $schoolName = SchoolProfile::where('school_id', $term->school_id)->value('school_name');

        return response()->view('public.enrollment.closed', [
            'term'       => $term,
            'setting'    => $setting,
            'schoolName' => $schoolName,
        ]);
    }
}
