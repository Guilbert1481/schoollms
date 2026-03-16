<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = auth()->user();

        if (!$user) {
            abort(403, 'Not authenticated.');
        }

        // Role check
        if (!in_array($user->role, $roles)) {
            abort(403, 'Unauthorized role.');
        }

        // School isolation (except superadmin routes)
        if ($user->role !== 'superadmin' && !$user->school_id) {
            abort(403, 'No school assigned.');
        }

        return $next($request);
    }
}
