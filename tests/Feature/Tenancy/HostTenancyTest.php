<?php

namespace Tests\Feature\Tenancy;

use App\Http\Middleware\ResolveSchoolFromHost;
use App\Models\School;
use App\Models\SchoolDomain;
use App\Models\User;
use App\Support\Tenancy\CurrentSchool;
use App\Support\Tenancy\TenantResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * Host-based multi-tenancy: a school is resolved from the request Host header
 * (base-domain subdomain by slug, custom domain row, or the legacy
 * schools.domain column), and — the security-critical part — an authenticated
 * user bound to one school can never render another school's host; they are
 * bounced back to their own. Superadmins and school-less users (parents) are
 * exempt. The TLS "ask" endpoint only greenlights hosts we actually serve.
 */
class HostTenancyTest extends TestCase
{
    use RefreshDatabase;

    private School $alpha;

    private School $beta;

    protected function setUp(): void
    {
        parent::setUp();

        // Deterministic domains regardless of the environment's .env.
        config([
            'tenancy.primary_base_domain' => 'localhost',
            'tenancy.base_domains' => ['localhost'],
            'tenancy.reserved_labels' => ['www', 'app', 'admin'],
        ]);

        $this->alpha = School::factory()->create(['slug' => 'alpha']);
        $this->beta = School::factory()->create(['slug' => 'beta']);
    }

    private function resolver(): TenantResolver
    {
        return app(TenantResolver::class);
    }

    /** Run the host middleware for a URL as an optional user; return the response. */
    private function runMiddleware(string $url, ?User $user = null): Response
    {
        $request = Request::create($url);

        if ($user) {
            $request->setUserResolver(fn () => $user);
        }

        return app(ResolveSchoolFromHost::class)
            ->handle($request, fn ($req) => response('OK', 200));
    }

    // ---- Resolution -------------------------------------------------------

    public function test_subdomain_resolves_to_school_by_slug(): void
    {
        $this->assertSame($this->alpha->id, $this->resolver()->resolve('alpha.localhost')?->id);
    }

    public function test_custom_domain_row_resolves(): void
    {
        SchoolDomain::create([
            'school_id' => $this->alpha->id,
            'host' => 'ALPHA.edu',   // stored lowercased by the model
            'type' => 'custom',
            'is_verified' => true,
        ]);

        $this->assertSame($this->alpha->id, $this->resolver()->resolve('alpha.edu')?->id);
    }

    public function test_legacy_schools_domain_column_resolves(): void
    {
        $this->alpha->update(['domain' => 'legacy.test']);

        $this->assertSame($this->alpha->id, $this->resolver()->resolve('legacy.test')?->id);
    }

    public function test_reserved_label_and_bare_base_and_unknown_resolve_to_null(): void
    {
        $this->assertNull($this->resolver()->resolve('www.localhost'));   // reserved label
        $this->assertNull($this->resolver()->resolve('localhost'));       // bare base domain
        $this->assertNull($this->resolver()->resolve('nope.localhost'));  // no such slug
    }

    // ---- Isolation guard --------------------------------------------------

    public function test_user_on_foreign_school_host_is_bounced_to_own_host(): void
    {
        $user = User::factory()->create(['school_id' => $this->alpha->id, 'role' => 'student']);

        $response = $this->runMiddleware('http://beta.localhost/student/dashboard', $user);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringContainsString('alpha.localhost', (string) $response->headers->get('Location'));
        $this->assertStringContainsString('/student/dashboard', (string) $response->headers->get('Location'));
    }

    public function test_user_on_own_school_host_passes_through(): void
    {
        $user = User::factory()->create(['school_id' => $this->alpha->id, 'role' => 'student']);

        $response = $this->runMiddleware('http://alpha.localhost/student/dashboard', $user);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($this->alpha->id, app(CurrentSchool::class)->id());
    }

    public function test_superadmin_passes_through_on_any_host(): void
    {
        $super = User::factory()->create(['school_id' => null, 'role' => 'superadmin']);

        $response = $this->runMiddleware('http://beta.localhost/dashboard', $super);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_guest_gets_current_school_set_for_branding(): void
    {
        $response = $this->runMiddleware('http://alpha.localhost/login');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($this->alpha->id, app(CurrentSchool::class)->id());
    }

    // ---- TLS ask endpoint -------------------------------------------------

    public function test_tls_gate_greenlights_served_hosts_only(): void
    {
        SchoolDomain::create([
            'school_id' => $this->beta->id,
            'host' => 'beta.edu',
            'type' => 'custom',
            'is_verified' => true,
        ]);
        SchoolDomain::create([
            'school_id' => $this->beta->id,
            'host' => 'unverified.test',
            'type' => 'custom',
            'is_verified' => false,
        ]);

        $resolver = $this->resolver();

        $this->assertTrue($resolver->isIssuableHost('alpha.localhost'));   // real slug subdomain
        $this->assertTrue($resolver->isIssuableHost('beta.edu'));          // verified custom domain
        $this->assertFalse($resolver->isIssuableHost('unverified.test'));  // unverified custom domain
        $this->assertFalse($resolver->isIssuableHost('nope.localhost'));   // no such school
    }
}
