<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Roadmap M3 — baseline security headers on every web response, with CSP in
 * report-only mode and HSTS only where TLS is real.
 */
class SecurityHeadersTest extends TestCase
{
    public function test_every_web_response_carries_the_baseline_headers(): void
    {
        $response = $this->get('/login');

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertNotEmpty($response->headers->get('Permissions-Policy'));

        $csp = (string) $response->headers->get('Content-Security-Policy-Report-Only');
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'self'", $csp);

        // Report-only phase: the ENFORCING header must not ship yet.
        $this->assertNull($response->headers->get('Content-Security-Policy'));
    }

    public function test_hsts_only_on_tls(): void
    {
        $this->get('/login')
            ->assertHeaderMissing('Strict-Transport-Security');

        $this->get('https://localhost/login')
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }
}
