<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Two-factor gate (Roadmap M2). Runs globally on the web group:
 *
 * 1. Any user who has enrolled an authenticator (google2fa_secret) must pass
 *    the once-per-session challenge before reaching anything else.
 * 2. Users in config('security.two_factor_mandatory_roles') who have NOT
 *    enrolled are locked to the setup flow until they do (only when
 *    config('security.enforce_2fa') is on — the test suite runs with it off
 *    and the dedicated 2FA tests turn it back on).
 *
 * The `/2fa/*` routes and logout stay reachable so neither branch can loop.
 */
class TwoFactorMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Belt-and-suspenders kept from the original middleware: superadmin
        // URLs are for superadmins, whatever else is attached to the route.
        if ($request->is('superadmin/*') && $user->role !== 'superadmin') {
            abort(403, 'Access Denied: Insufficient Clearance.');
        }

        // Escape hatches — the 2FA flow itself and logout must stay reachable.
        if ($request->is('2fa/*') || $request->routeIs('logout')) {
            return $next($request);
        }

        // Enrolled users: challenge once per session.
        if ($user->google2fa_secret && ! session('2fa_verified')) {
            return $this->deny($request, route('2fa.verify'), 'Please confirm your identity to continue.');
        }

        // Mandatory roles without an authenticator: lock to setup.
        if (
            ! $user->google2fa_secret
            && config('security.enforce_2fa')
            && in_array($user->role, config('security.two_factor_mandatory_roles', []), true)
        ) {
            return $this->deny($request, route('2fa.setup'), 'Two-factor authentication is required for your role.');
        }

        return $next($request);
    }

    private function deny(Request $request, string $target, string $message): Response
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['redirect' => $target, 'message' => $message], 403);
        }

        return redirect($target)->with('warning', $message);
    }
}
