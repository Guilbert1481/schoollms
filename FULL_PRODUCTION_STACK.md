# Full Production Stack — Sophentis Layer Checklist

> **Status:** Active — mandatory companion checklist (binding via
> [`ENGINEERING_CONSTITUTION.md`](./ENGINEERING_CONSTITUTION.md) §11A)
> **Version:** 1.0 · **Last updated:** 2026-07-17
> **Audience:** Every contributor, human or AI, before creating or editing anything in Sophentis.
> Adapted from the Argo platform's production-stack standard to this stack:
> **Laravel 12 · PHP 8.2 · Blade · MySQL · nginx VPS · GitHub.**

**Normative language.** MUST / MUST NOT / SHOULD / MAY per RFC 2119, exactly as in the Constitution.

---

## How to use this document (the rule)

Before **creating any new file or editing any existing one**, the contributor **MUST**:

1. Walk the 13 layers below and identify **which layers the change touches** (most changes touch 2–5).
2. For each touched layer, **follow that layer's "How it MUST be implemented" rules strictly**.
3. If the change would **degrade** any layer (an unscoped query, an unthrottled abuse-prone endpoint,
   an unaudited money/grade mutation, a new stateful store outside the backup scope), that is a
   **defect** — fix it as part of the change or record an explicit, owner-approved ADR exception.
4. If a layer the change depends on is still a known gap (see the status lines and
   [`MODERNIZATION_ROADMAP.md`](./MODERNIZATION_ROADMAP.md)), the change **MUST NOT** silently widen
   the gap — flag it in Discussion before coding.

### Quick pre-change checklist

| # | Layer | Ask before every change |
|---|-------|-------------------------|
| 1 | Frontend Foundation | Reusing shared Blade components? No logic in Blade? Build-independent styling? |
| 2 | APIs & Backend Logic | Route → Controller → Service → Repository? FormRequest validation? Thin controller? |
| 3 | Database & Storage | Additive migration? `school_id` + `BelongsToSchool` on tenant tables? Files via `SecureUpload` + private disk? |
| 4 | Auth & Permissions | Route gated with `role:` middleware? Ownership Policy for user-owned IDs? 2FA where privileged? |
| 5 | Hosting & Deployment | Deploys via `deploy.sh` + the runbook? Config change reflected in `.env.example` + runbook? |
| 6 | Cloud & Compute | Heavy work (OCR/AI/exports/mail) queued, not on the request path? Scheduler entry documented? |
| 7 | CI/CD & Version Control | Feature branch → PR? Scoped commit? CI green? Reversible? |
| 8 | Security & Tenant Isolation | Every query school-scoped? Ownership checked? Sensitive mutation audited? Injection-free? |
| 9 | Rate Limiting | Is the new endpoint abusable or expensive (auth, uploads, chat, AI/OCR)? Throttled? |
| 10 | Caching & CDN | Cached tenant data keyed by `school_id`? Invalidation defined? |
| 11 | Load Balancing & Scaling | Scheduler/job single-runner safe? Indexed on `school_id` + filters? Paginated? |
| 12 | Error Tracking & Logs | Failures observable? Money/grade/role mutations in the audit log? No secrets/PII in logs? |
| 13 | Availability & Recovery | Does this change what must be backed up or restored? Runbook still accurate? |

---

## The 13 layers

### 1. Frontend Foundation

**What it is:** everything users see — Blade pages, forms, tables, modals, sidebar, mobile UI,
loading and error states.

**How it MUST be implemented in Sophentis:**

- Blade + Tailwind (Vite build) is the base; **new interactive UI uses React + Tailwind + Vite
  islands** per [ADR-0005](./docs/adr/0005-frontend-stack-react-tailwind-vite.md) — no other
  framework, bundler, or ad-hoc CDN scripts on app pages.
- Compose from the shared Blade components (`resources/views/components/` — table stack,
  `level-tabs`, form partials) instead of bespoke markup where a primitive exists.
- **No business logic in Blade** (Constitution §11); views render data prepared by controllers/services.
- New page containers use `<div class="w-full space-y-6">` (layout already pads).
- ⚠ **Stale-build caveat:** uncommon/arbitrary Tailwind utilities silently no-op unless the Vite
  build is rebuilt — style new UI build-independently (inline/scoped styles) or rebuild deliberately.
- Server data reaches JS only via `@json()` / `Js::from()` — never `{!! !!}`.

**Current status: ✅ Solid** — shared table/component stack in active use.

### 2. APIs & Backend Logic

**What it is:** the brain — CRUD, enrollment, grading, finance, approvals, module wiring.

**How it MUST be implemented in Sophentis:**

- Strict layering: **Route → Controller → Service → Repository → Model**; domain packaging under
  `app/Modules`. Thin controllers; no ORM queries in Blade; services own business rules.
- Validation via **FormRequests** (or inline `validate()` for trivial cases); `exists:` rules are
  tenant-scoped.
- State machines (invoice → paid/void, enrollment status) enforced in services with explicit
  transition guards; money writes use transactions and idempotency guards (Roadmap Phase 3).
- Cross-module access goes through the owning module's service — never by reaching into another
  module's tables.

**Current status: ✅ / ⚠️** — layering is the standard; legacy god-controllers (e.g. the ledger
controller) are decomposed by strangler steps when touched (Constitution §3).

### 3. Database & Storage

**What it is:** where data lives — students, grades, invoices, payments, uploads.

**How it MUST be implemented in Sophentis:**

- MySQL is the system of record. **Every schema change is a migration** — additive, never editing a
  shipped migration (Constitution §7A; memory rule: no raw DDL).
- Every tenant-owned table carries **`school_id`** and its model uses **`BelongsToSchool`** — no
  exceptions for tenant data. UNIQUE constraints include `school_id` where relevant.
- Uploads go through **`App\Services\Uploads\SecureUpload`** (allow-list + image re-encode + random
  names); sensitive documents live on the **private `local` disk**, served only by
  `SecureDocumentController` — never the `public` disk.
- Financial truth derives from ledger entries; never mutate derived balances without the source rows.

**Current status: ✅ / ⚠️** — tenancy trait fixed and extended (Roadmap Phase 1), private-disk
documents done (Phase 2); tenant coverage still expanding (~15/131 models → all, Constitution §13).

### 4. Auth & Permissions

**What it is:** who can log in and what they can reach.

**How it MUST be implemented in Sophentis:**

- Custom session login (`Auth/LoginController`) keeps `session()->regenerate()`, school
  approval/active checks, logout `regenerateToken()`; login + reset endpoints throttled.
- **Coarse:** `role:` middleware (`CheckRole`) on every non-public route group — sidebar hiding is
  never security. **Fine:** Policies for record-level ownership (intra-school IDOR — Roadmap
  Phase 2.5). Canonical snake_case role names; superadmin is exactly `superadmin`.
- Privileged surfaces (superadmin settings, finance) additionally carry `2fa`; staff 2FA becomes
  mandatory in Roadmap Phase 5 (M2).
- Password changes/resets evict other active sessions (`AuthenticateSession` — Roadmap M6).

**Current status: ✅ / ⚠️** — role gating + throttle + reset flow done; record-level ownership
Policies done (Phase 2.5, 2026-07-18: 8 Policies + route sweep, 3 IDORs fixed); staff-2FA
enforcement (M2) pending.

### 5. Hosting & Deployment

**What it is:** where the system is published.

**How it MUST be implemented in Sophentis:**

- Production: nginx VPS (Contabo, 207.180.239.10) — platform host `sophentis.philceb.ph`, schools at
  `<slug>.philceb.ph`; TLS via certbot per host. Runbook:
  [`docs/deploy/sophentis-philceb-ph.md`](./docs/deploy/sophentis-philceb-ph.md).
- Deploys run `deploy.sh` (git pull → composer → migrate → caches); **never** hand-edit code on the
  server. Server config changes (nginx site, `.env` keys) are mirrored into the runbook and
  `.env.example` **in the same change**.
- Production boot guards stay on: `APP_DEBUG` forced off, `SESSION_SECURE_COOKIE=true`, config cached.
- The AI assistant has **no self-authorized deploy** — server steps are run by the operator
  (Constitution §8).

**Current status: ⚠️ In progress** — staging bring-up on philceb.ph underway (see
`session_history_log.md`); runbook exists and is current.

### 6. Cloud & Compute

**What it is:** the server power — queue workers, scheduler, websockets, AI/OCR processing.

**How it MUST be implemented in Sophentis:**

- Anything heavier than a fast request (bulk mail, exports, OCR/AI scanning, report generation)
  runs as a **queued job** (`QUEUE_CONNECTION=database` today) — never on the request path.
- Scheduled tasks live in `routes/console.php`; every new entry is documented and idempotent.
- Realtime uses **Reverb** (`php artisan reverb:start`); if websocket errors appear, fix the server,
  not the JS build.
- Before adding a workload, state the expected VPS impact; disk-hungry artifacts (exports, scans,
  logs) get a retention policy.

**Current status: ✅ / ⚠️** — queue + scheduler + Reverb wired locally; production worker/units to be
codified during the philceb.ph bring-up.

### 7. CI/CD & Version Control

**What it is:** safe code change — GitHub, branches, PRs, tests, rollback.

**How it MUST be implemented in Sophentis:**

- **Never commit straight to `main`** — feature branch → PR → review (Constitution §7). Auth,
  tenancy, finance, and file-handling changes need a **second reviewer + passing tenant-isolation
  test** before merge.
- Small, scoped, incremental commits; one concern per PR; every merge reversible; `main` deployable.
- CI (`.github/workflows/ci.yml`) gates the merge; `composer audit` + `npm audit` steps join it in
  Roadmap Phase 7 (S1) with Dependabot (S2).
- Run Pint before committing; no scratch files.

**Current status: ✅ / ⚠️** — branch/PR discipline + CI tests in place; dependency-audit steps (S1/S2)
pending.

### 8. Security & Tenant Isolation

**What it is:** protecting data — a user from School A must never see School B, and Student A must
never see Student B. **This layer's authority is [`SECURITY_PRINCIPLES.md`](./SECURITY_PRINCIPLES.md)
(#2 in precedence) — this checklist only indexes it.**

**How it MUST be implemented in Sophentis:**

- `BelongsToSchool` global scope on every tenant model **plus** the explicit
  `abort_unless($record->school_id === auth()->user()->school_id, 404)` on route-model-bound records.
- **Ownership is not tenancy:** user-owned IDs pass a Policy/ownership chokepoint (Phase 2.5).
- All writes validated; `{!! !!}` banned for user-influenced values; uploads via `SecureUpload`.
- Money/grade/role/enrollment mutations write the **audit log** (Phase 3).
- Gated actions (deploys, live-DB migrations, deletions, mass guardian email, auth/role/tenancy
  changes) require explicit human approval — never self-authorized.

**Current status: ✅ / ⚠️** — Phases 0–2.5 done (2026-07-18: intra-school authorization Policies,
IDOR route sweep, raw-query/mass-assignment sweep all clear); audit log (Phase 3) is the next gap.

### 9. Rate Limiting

**What it is:** preventing abuse and overuse.

**How it MUST be implemented in Sophentis:**

- **Auth endpoints** (login, password reset, 2FA) and **all public/unauthenticated endpoints** MUST
  be throttled (named limiters in `AppServiceProvider::configureRateLimiting`).
- **Expensive or abuse-prone authenticated endpoints** — uploads, chat sends, AI/OCR scans, exports,
  bulk mail — get per-user (and where relevant per-school) limiters (Roadmap H6).
- Limiters MUST use a **shared cache store** (database/Redis), never an in-process counter, so they
  hold across php-fpm workers and restarts. Laravel's `RateLimiter` on the default cache store
  satisfies this — do not hand-roll.
- Limits return 429; request-size limits complement them (nginx `client_max_body_size` + validation).

**Current status: ✅ / ⚠️** — login, reset, chat, uploads, and public-apply throttled (H6,
2026-07-18); `throttle:ai` limiter defined and waiting to be attached to the AI/OCR endpoints (AI3).

### 10. Caching & CDN

**What it is:** making the system faster without leaking between tenants.

**How it MUST be implemented in Sophentis:**

- Static assets: Vite hashed filenames + long-cache headers via nginx — nothing to hand-roll.
- Expensive, frequently-read aggregates (dashboards, report roll-ups) MAY be cached with
  **`school_id` in the cache key** (`cache:{school_id}:{name}`), short TTL, and explicit
  invalidation on the writes that change them.
- **Never** cache tenant data under a key that omits `school_id`; never cache per-user auth results.
- Correct indexes + query shape come first; caching is a multiplier, not a fix for a bad query.

**Current status: ✅ acceptable** — no backend cache layer yet, none needed at current scale; the
tenant-key rule binds any future use.

### 11. Load Balancing & Scaling

**What it is:** handling more schools and users.

**How it MUST be implemented in Sophentis:**

- PHP-FPM request path is stateless — keep it that way (no file-based per-process state for
  app logic; sessions/cache/queue in shared stores).
- Scheduled tasks and queue jobs MUST be **single-runner safe**: `withoutOverlapping()` /
  `onOneServer()` on schedule entries; jobs idempotent so a retry or a second worker cannot
  double-send or double-charge.
- Hot query paths carry indexes on `school_id` + filter columns; list endpoints paginate;
  unbounded result sets are a defect.
- Scale ladder (only when measured load demands): tune MySQL/indexes → more fpm workers/queue
  workers → second app node (requires shared sessions/cache) → managed/replicated MySQL.
  **Backups and monitoring outrank load balancing.**

**Current status: ✅ acceptable** — single VPS is right for current scale; the rules above keep the
path open.

### 12. Error Tracking & Logs

**What it is:** knowing what is wrong — errors, failed logins, failed payments, slow requests.

**How it MUST be implemented in Sophentis:**

- Logs are structured with `school_id` + `user_id` context (quality item Q8); **no secrets, tokens,
  passwords, or minors' PII in logs** — ever.
- Operator logs and the **audit trail are different things**: logs for diagnosis, audit rows
  (Phase 3) for accountability; sensitive mutations need both.
- Unhandled exceptions surface in an **error monitor** (Sentry/Flare-class — Roadmap S3); until
  then, failures must log enough context to reproduce (school, route, ids — not payloads).
- A cheap, honest **`/up` health endpoint** stays available for uptime monitoring (Roadmap S5);
  new critical dependencies surface in it.

**Current status: ⚠️ Partial** — file logs only today; login/action logs Phase 4, Sentry S3,
uptime S5.

### 13. Availability & Recovery

**What it is:** if the server dies or data is lost, can we recover? Data loss is the one
irreversible failure.

**How it MUST be implemented in Sophentis:**

- **Automated nightly MySQL dumps, encrypted, shipped off-box**, with tiered retention — Roadmap D1.
- **A backup that has never been restored is not a backup:** scheduled restore drills into a
  scratch database, results recorded.
- A **dead-man's switch** alerts the operator when a backup silently fails (Roadmap D5) — a missed
  backup is an incident, not a surprise at restore time.
- A **DR runbook** (Roadmap P5, extending `docs/deploy/`) records what is backed up, where, keys,
  RTO/RPO, exact restore commands, and the VPS rebuild-from-zero path.
- Any change adding a **new stateful store** (new disk path, bucket, database) MUST update the
  backup scope and runbook **in the same change**. ⚠ `APP_KEY` is itself crown-jewel state once
  `encrypted` casts land (D2) — losing it loses the encrypted columns.

**Current status: ❌ Gap (top priority in Phases 6–8)** — no backups exist today; D1 is the single
most important pending item in the entire roadmap.

---

## Maintenance of this document

- Update a layer's **Current status** in the same commit that lands a roadmap phase affecting it.
- New platform capabilities (new store, new public surface, new worker) MUST be reflected in the
  relevant layer's rules if they establish a new pattern.
- Amendments follow governance discipline: deliberate, reviewed, versioned (ADR for substantive
  changes).
