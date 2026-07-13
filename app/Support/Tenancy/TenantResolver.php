<?php

namespace App\Support\Tenancy;

use App\Models\School;
use App\Models\SchoolDomain;

/**
 * Resolves the tenant (School) for a request from its Host header.
 *
 * Resolution order for a host:
 *   1. Exact match in `school_domains` (custom domains, subdomains, aliases).
 *   2. Legacy single custom-domain column `schools.domain` (back-compat with
 *      Website\FileController, which matches the Host to that column).
 *   3. "<school-slug>.<base-domain>" convention — resolve by the school slug.
 *   4. Otherwise null (the platform/central context).
 *
 * Pure and side-effect free so it can back both the request middleware and the
 * on-demand-TLS "ask" endpoint, and be unit-tested in isolation.
 */
class TenantResolver
{
    public function resolve(?string $host): ?School
    {
        $host = $this->normalizeHost($host);

        if ($host === '') {
            return null;
        }

        // 1. Explicit host mapping.
        $schoolId = SchoolDomain::query()->where('host', $host)->value('school_id');
        if ($schoolId) {
            return School::query()->whereKey($schoolId)->first();
        }

        // 2. Legacy single custom-domain column.
        $school = School::query()
            ->where('domain', $host)
            ->orWhere('domain', 'www.'.$host)
            ->first();
        if ($school) {
            return $school;
        }

        // 3. Base-domain subdomain -> school slug.
        $label = $this->subdomainLabelFor($host);
        if ($label !== null && ! $this->isReservedLabel($label)) {
            $school = School::query()->where('slug', $label)->first();
            if ($school) {
                return $school;
            }
        }

        // 4. Central / unknown host.
        return null;
    }

    /**
     * Whether a host is allowed to be served — and therefore have a TLS
     * certificate issued on demand. Stricter than resolve(): a school's own
     * custom domain must be VERIFIED before we will issue a cert for it, so
     * that pointing an arbitrary domain at our IP does not mint certificates.
     * Base-domain subdomains we control are always issuable.
     */
    public function isIssuableHost(?string $host): bool
    {
        $host = $this->normalizeHost($host);

        if ($host === '') {
            return false;
        }

        // Base-domain subdomain backed by a real school slug — we own the base.
        $label = $this->subdomainLabelFor($host);
        if ($label !== null && ! $this->isReservedLabel($label)
            && School::query()->where('slug', $label)->exists()) {
            return true;
        }

        // Verified custom domain row.
        if (SchoolDomain::query()->where('host', $host)->where('is_verified', true)->exists()) {
            return true;
        }

        // Legacy custom-domain column (treated as verified).
        return School::query()
            ->where('domain', $host)
            ->orWhere('domain', 'www.'.$host)
            ->exists();
    }

    /**
     * Lowercase, strip any port, and trim a trailing dot.
     */
    public function normalizeHost(?string $host): string
    {
        $host = strtolower(trim((string) $host));

        if ($host === '') {
            return '';
        }

        // Drop a :port if a raw value slipped through (e.g. from the ask endpoint).
        if (str_contains($host, ':')) {
            $host = explode(':', $host, 2)[0];
        }

        return rtrim($host, '.');
    }

    /**
     * The single subdomain label for a host under one of our base domains,
     * or null when the host is not a single-label subdomain of a base domain
     * (bare base domain, deeper nesting, or an unrelated host).
     */
    public function subdomainLabelFor(string $host): ?string
    {
        foreach ($this->baseDomains() as $base) {
            $base = strtolower($base);

            if ($host === $base) {
                return null; // bare base domain -> central, not a school
            }

            $suffix = '.'.$base;
            if (str_ends_with($host, $suffix)) {
                $label = substr($host, 0, -strlen($suffix));

                // Only a single label is a slug; deeper nesting is not.
                if ($label !== '' && ! str_contains($label, '.')) {
                    return $label;
                }
            }
        }

        return null;
    }

    protected function isReservedLabel(string $label): bool
    {
        return in_array($label, (array) config('tenancy.reserved_labels', []), true);
    }

    /**
     * @return array<int, string>
     */
    protected function baseDomains(): array
    {
        return (array) config('tenancy.base_domains', []);
    }
}
