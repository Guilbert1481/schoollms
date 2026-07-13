# ADR-0007 — Host-based tenant resolution (subdomains + custom domains)

> **Status:** Accepted · **Date:** 2026-07-13 · **Deciders:** Sophentis team

## Context

Until now a user's school was known only from their authenticated session
(`auth()->user()->school_id`, see [ADR-0001](0001-multi-tenancy-via-belongs-to-school.md)); the URL
carried no school identity (`localhost/student/dashboard` looks identical for every school). We want
each school reachable on its **own host** — a subdomain of our base domain
(`memory-ridge.philceb.ph`) now, and each school's **own custom domain** later
(`memoryridge.edu.ph`) — for branding, shareable per-school links, and a clean white-label story.

The alternative, a path prefix (`/memory-ridge/…`), would have required rewriting ~100 route files.
Resolving the tenant from the **Host header** leaves the route files untouched, because Laravel builds
URLs against the current request host.

## Decision

Resolve the current school from the request **Host header**, in one global middleware, on top of the
existing per-model scoping — never replacing it.

1. **`school_domains` table** maps `host → school_id` (a school may own many hosts: its subdomain, one
   or more custom domains, and aliases). `is_verified` gates on-demand TLS issuance for custom domains.
2. **`App\Support\Tenancy\TenantResolver`** resolves a host in order: explicit `school_domains` row →
   legacy `schools.domain` column → `"<slug>.<base-domain>"` convention → otherwise null (the platform
   / central host). Base domains are configured in [`config/tenancy.php`](../../config/tenancy.php).
3. **`ResolveSchoolFromHost` middleware** (appended to the `web` group) sets the resolved school in the
   `CurrentSchool` singleton — read via the `current_school()` helper — for every request, so even the
   pre-auth login page brands to the right tenant.
4. **Host-level isolation guard:** an authenticated, school-bound user on a host that belongs to a
   *different* school is bounced to their own school's host; superadmins and school-less users (parents,
   guarded per-child elsewhere) are exempt.
5. **Session cookies stay host-only** (`SESSION_DOMAIN=null`) — a login on one school's host is never
   sent to another host, so the primary isolation is at the cookie layer and the guard is the backstop.
6. **`/tenancy/tls-check`** answers the reverse proxy's on-demand-TLS "ask" only for hosts we actually
   serve, so certificates are not minted for arbitrary domains pointed at our IP.

## Consequences

- Route files, controllers, and `route()` calls are **unchanged** — the host does the work.
- The long-standing "always the first school" bug in the global `View::composer` branding is fixed:
  branding now follows the resolved host, falling back to the previous behaviour on the central host.
- Login is host-scoped: on `<slug>.<domain>/login`, credentials are matched only against that school.
- **New convention:** every school gets a canonical host via `School::primaryHost()`
  (`<slug>.<base-domain>` by default); custom domains are added as `school_domains` rows and verified
  before they are served.
- This is an **auth/tenancy change** — it shipped on a branch with a tenant-isolation test and requires
  a second reviewer per [`SECURITY_PRINCIPLES.md`](../../SECURITY_PRINCIPLES.md); it is **not** the
  deployment (VPS packaging + DNS + TLS) — that is a separate, operator-run phase.
- **Not yet done (follow-ups):** a `tenancy:sync-domains` command to backfill primary subdomain rows;
  a registrar/superadmin UI to add + verify custom domains; making `applyDynamicMailConfig` per-tenant.
