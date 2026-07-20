<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline security headers on every web response (Roadmap M3,
 * SECURITY_PRINCIPLES §16). CSP ships REPORT-ONLY first: it must observe a
 * full release cycle without console violations before being switched to
 * enforcing — flip the header name, not the policy.
 */
class SecurityHeaders
{
    /**
     * Report-only CSP. Deliberately tolerant of the current codebase
     * (inline scripts/styles, CDN assets, data-URI images, websockets)
     * so the report phase measures real violations, not known debt.
     */
    private const CSP_REPORT_ONLY = "default-src 'self'; "
        ."script-src 'self' 'unsafe-inline' 'unsafe-eval' https:; "
        ."style-src 'self' 'unsafe-inline' https:; "
        ."img-src 'self' data: blob: https:; "
        ."font-src 'self' data: https:; "
        ."connect-src 'self' ws: wss: https:; "
        ."frame-ancestors 'self'; "
        ."base-uri 'self'; "
        ."form-action 'self'";

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = $response->headers;

        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        // camera stays same-origin-allowed: the OMR scanner and QR attendance
        // pages use getUserMedia on our own pages.
        $headers->set('Permissions-Policy', 'camera=(self), microphone=(), geolocation=()');
        $headers->set('Content-Security-Policy-Report-Only', self::CSP_REPORT_ONLY);

        // HSTS only where TLS is real — never on plain-HTTP local dev, or the
        // browser would refuse http://localhost for six months.
        if ($request->secure() || app()->environment('production')) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
