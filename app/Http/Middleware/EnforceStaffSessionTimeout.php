<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Staff session hardening (Roadmap D4). Privileged roles get a shorter idle
 * timeout than the global SESSION_LIFETIME: after `security.staff_idle_timeout`
 * minutes with no request, the session is invalidated server-side and the user
 * must sign in again. This is the real protection for shared school-office
 * machines — it does not rely on the browser honouring cookie expiry.
 *
 * Server-side by design: the last-activity timestamp lives in the session, so a
 * stale cookie replayed after the window is rejected. Non-staff roles and
 * guests are untouched.
 */
class EnforceStaffSessionTimeout
{
    private const KEY = 'staff_last_activity_at';

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        $timeout = (int) config('security.staff_idle_timeout', 0);

        if (! $user || $timeout <= 0 || ! $this->isStaff($user)) {
            return $next($request);
        }

        $now = now()->getTimestamp();
        $last = (int) $request->session()->get(self::KEY, $now);

        if ($now - $last > $timeout * 60) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = 'For your security, your session timed out after a period of inactivity. Please sign in again.';

            if ($request->expectsJson()) {
                abort(response()->json(['message' => $message], 401));
            }

            return redirect()->route('login')->with('warning', $message);
        }

        $request->session()->put(self::KEY, $now);

        return $next($request);
    }

    private function isStaff($user): bool
    {
        return in_array($user->role, (array) config('security.staff_session_roles', []), true);
    }
}
