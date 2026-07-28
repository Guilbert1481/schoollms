<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Forbid caching of the response. Applied to guest auth forms (login, 2FA,
 * password reset): each embeds a one-time CSRF token, and a cached copy —
 * mobile back/forward cache especially — resubmits a dead token, 419s, and
 * traps the user in an "expired session" retry loop until the login rate
 * limiter locks them out.
 */
class NoStore
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-store, no-cache, max-age=0, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }
}
