<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\CurrentSchool;
use App\Support\Tenancy\TenantResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the current school from the request Host header and enforces
 * host-level tenant isolation.
 *
 * The school is resolved for EVERY request (so guests get correct per-host
 * branding) and stored in the CurrentSchool singleton. When an authenticated,
 * school-bound user is on a host that belongs to a DIFFERENT school, they are
 * bounced back to their own school's host — never allowed to render another
 * tenant's host. Superadmins (platform) and users with no school (e.g. parents,
 * whose access is guarded per-child elsewhere) are exempt.
 *
 * This is a host-level guard that sits ON TOP OF the per-model BelongsToSchool
 * scoping; it does not replace it.
 */
class ResolveSchoolFromHost
{
    public function __construct(
        protected TenantResolver $resolver,
        protected CurrentSchool $current,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $school = $this->resolver->resolve($request->getHost());
        $this->current->set($school);

        $user = $request->user();

        if ($school
            && $user
            && $user->role !== 'superadmin'
            && $user->school_id
            && (int) $user->school_id !== (int) $school->id
        ) {
            return $this->rejectCrossTenant($request, $user);
        }

        return $next($request);
    }

    /**
     * A school-bound user reached a host that isn't theirs. Bounce them to
     * their own school's host (same path) rather than leak another tenant's
     * page. If we cannot determine their host, deny without leaking existence.
     */
    protected function rejectCrossTenant(Request $request, $user): Response
    {
        // API/XHR callers get a flat 404 (no cross-origin redirect, no leak).
        if ($request->expectsJson()) {
            abort(404);
        }

        $ownHost = optional($user->school)->primaryHost();

        if (! $ownHost) {
            abort(404);
        }

        // Preserve a non-standard port so the bounce works in local dev too
        // (in production the standard 80/443 port is simply omitted).
        $scheme = $request->getScheme();
        $port = $request->getPort();
        $portPart = (($scheme === 'http' && $port && $port !== 80)
            || ($scheme === 'https' && $port && $port !== 443))
            ? ':'.$port
            : '';

        $target = $scheme.'://'.$ownHost.$portPart.$request->getRequestUri();

        return redirect()->away($target);
    }
}
