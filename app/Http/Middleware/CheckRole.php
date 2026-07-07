<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    /**
     * Verify the authenticated user has one of the required roles.
     *
     * Role values are normalized (lowercased, hyphens/spaces → underscores)
     * so "Course Architect", "course-architect", and "course_architect"
     * all match the same allowed role.
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $normalize = fn ($r) => str_replace(['-', ' '], '_', strtolower((string) $r));

        $userRole = $normalize($user->role);
        $allowed  = array_map($normalize, $roles);

        if (! in_array($userRole, $allowed, true)) {
            abort(403, 'You do not have the correct permissions to access this page.');
        }

        // Superadmins and parent-portal users are the two school-less account
        // kinds: parents are cross-school (children may attend different
        // schools), so their scoping runs through parent_student links —
        // enforced per child in the parent controllers — not users.school_id.
        if (! in_array($userRole, ['superadmin', 'parent'], true) && ! $user->school_id) {
            abort(403, 'No school assigned.');
        }

        return $next($request);
    }
}
