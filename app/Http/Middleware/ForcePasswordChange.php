<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Users whose account was provisioned (or re-issued) with a generated
 * one-time password must pick their own before using the system. Applies to
 * any authenticated user with the flag set — today that's auto-provisioned
 * parent-portal accounts.
 */
class ForcePasswordChange
{
    /** Routes the flagged user may still reach (the escape hatch itself + logout). */
    protected const ALLOWED_ROUTES = [
        'settings.profile',
        'settings.profile.update',
        'settings.profile.password',
        'logout',
    ];

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user || ! $user->password_change_required) {
            return $next($request);
        }

        if (in_array($request->route()?->getName(), self::ALLOWED_ROUTES, true)) {
            return $next($request);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'redirect' => route('settings.profile', ['tab' => 'security']),
                'message'  => 'Please set your own password to continue.',
            ], 403);
        }

        return redirect()
            ->route('settings.profile', ['tab' => 'security'])
            ->with('warning', 'Please set your own password to continue.');
    }
}
