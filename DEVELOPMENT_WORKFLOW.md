# Development Workflow — Sophentis / schoollms

> How work moves from idea to production. Lightweight on purpose — enough process
> to stop regressions and silent breakage, not so much that it slows shipping.

## 1. Change sizing — pick the right track

| Size | Examples | Process |
|---|---|---|
| **Trivial** | copy fix, style tweak, add an index | branch → PR → 1 review |
| **Standard** | new CRUD screen, a service method | branch → PR → 1 review + test |
| **Significant** | auth/tenancy/finance change, schema change, new module | **ADR** → branch → PR → 2 reviews + tenant-isolation test |
| **Cross-cutting** | new architecture, big refactor, external integration | **RFC** → design review → phased ADRs |

## 2. RFCs (Request for Comments) — for significant/cross-cutting work

- Write a short `rfc/NNNN-title.md`: problem, options considered, recommendation,
  risks, rollout/rollback.
- Circulate before building. The audits and this roadmap are effectively RFC-0001.

## 3. ADRs (Architecture Decision Records)

- For any decision that shapes structure (e.g. "tenancy via global scope vs manual
  filtering", "single `role` string vs roles table"), record an `adr/NNNN-title.md`:
  context, decision, consequences.
- ADRs are append-only; supersede, don't rewrite. (The `add_role_id` → `drop_role_id`
  flip-flop is exactly what an ADR would have prevented.)

## 4. Branching & PR policy

- **Never commit straight to `main`.** Feature branches → PR. (We have been
  committing large batches to `main` — stop.)
- One concern per PR; small diffs. Reference the roadmap finding ID (e.g. "H4").
- PR description states: what changed, why, how it was verified, rollback plan.
- Required before merge: Pint clean, `php artisan test` green, no `dd()`/scratch files.
- **Platform enforcement (Q7):** branch protection on `main` (no direct pushes —
  the platform enforces what convention already requires), a PR template carrying
  the house checklist (tests? tenant-scoped? Pint? finding ID?), and a
  `CHANGELOG.md` maintained per release.

## 5. Design reviews

- Significant changes get a 15-minute design review against
  `ARCHITECTURE_PRINCIPLES.md` and `SECURITY_PRINCIPLES.md` **before** coding.
- Checklist: Does it respect layering? Is tenant scoping enforced? Is it tested?
  Any new authz surface?

## 6. Implementation phases (how a feature is built)

1. **Plan** — confirm the approach (and ADR if significant).
2. **Schema** — migration first (additive; never edit a shipped migration).
3. **Domain** — service/model logic with tests.
4. **Edges** — controller + Form Request + Policy.
5. **UI** — Blade/JS using existing components.
6. **Verify** — see §7.

## 7. Testing & verification process

- **Automated:** Feature tests for finance/grade/auth paths (happy path + tenant
  isolation). Unit tests for service logic.
- **Local verification standard** (until coverage grows): `php -l`,
  `php artisan route:list`, and **rolled-back smoke tests**
  (`DB::beginTransaction()/rollBack()`) — never mutate live data during testing.
- **Security fixes** additionally re-run the attacker scenario to prove it's blocked.
- A change that touches money or grades **does not merge without a test.**

### 7a. Test database (MySQL, not sqlite)

The app targets MySQL and several migrations use MySQL-specific DDL (`ENUM`,
`ALTER … MODIFY`), so the automated suite runs against a **real, isolated MySQL
database** — never sqlite, and never the dev/prod database.

- **Connection:** `phpunit.xml` pins `DB_CONNECTION=mysql` and
  `DB_DATABASE=schoollms_test`. Host / user / password are inherited from your
  environment (`.env` locally, CI secrets in the pipeline).
- **One-time local setup:** create the empty database once —
  `CREATE DATABASE schoollms_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
  Your `.env` `DB_USERNAME` / `DB_PASSWORD` must be able to reach it. It is
  wiped and rebuilt by `RefreshDatabase` on every run, so keep it separate from
  your working database.
- **Schema squash (why migrate:fresh is fast and reliable):** instead of
  replaying every migration, `RefreshDatabase`/`migrate:fresh` loads the
  committed schema baseline `database/schema/mysql-schema.sql`. This keeps the
  test schema identical to production and avoids fresh-replay ordering issues.
- **Regenerate the baseline after adding migrations:** run
  `php artisan schema:dump` against a database that is fully migrated. It writes
  `database/schema/mysql-schema.sql` (committed — do not gitignore it). On
  Windows/Laragon, put the MySQL `bin/` on `PATH` first so `mysqldump`/`mysql`
  resolve; the `column-statistics` warning from MySQL 5.7's `mysqldump` is
  harmless.
- **Run the suite:** `php artisan test` (or `composer test`).

## 8. CI

- `.github/workflows/ci.yml` runs on every push to `main` and every PR:
  - **tests** — spins up a `mysql:8.0` service, creates `schoollms_test`, builds
    front-end assets (Vite manifest is needed by Blade-rendering Feature tests),
    then `composer test` against that database.
  - **code-style** — `vendor/bin/pint --test` (currently non-blocking).
  - **static-analysis** — Larastan (currently non-blocking until a baseline lands).
  - **dependency-audit** *(planned — Roadmap Phase 7 S1/S2)*: `composer audit` +
    `npm audit` (start non-blocking, promote to required), plus Dependabot on the repo.

## 9. Release process

- Releases follow `DEPLOYMENT.md` (GitHub → Contabo VPS). That document is the
  operational source of truth; this section governs *when* we release.
- **Pre-release checklist:**
  - [ ] All targeted roadmap items `[x]` and verified.
  - [ ] `php artisan test` green; Pint clean.
  - [ ] `APP_DEBUG=false`, `APP_ENV=production`, `SESSION_SECURE_COOKIE=true`.
  - [ ] `php artisan config:cache route:cache view:cache` on the server.
  - [ ] `php artisan migrate --force` reviewed (additive only).
  - [ ] Rollback plan noted (previous tag + DB backup).
- Tag releases; keep a short `CHANGELOG`.

## 10. Incident response

- On a suspected breach: rotate `APP_KEY`/session secret, invalidate sessions,
  consult the audit log (Roadmap Phase 3/4) for actor + timeline, patch on a
  hotfix branch, write a postmortem ADR.
- The full procedure lives in the **one-page IR plan** (Roadmap Phase 8 P1),
  written *before* it's needed: who is called, mass force-logout, rotation runbook
  (⚠ coordinated with the Phase 6 D2 encrypted casts), school notification steps.
- Breach handling must satisfy **RA 10173**: 72-hour notification to the NPC and
  affected data subjects (P3). The Phase 3–4 logs are what make "what leaked, when,
  to whom" answerable inside that window.
- Staff offboarding is part of incident *prevention*: deactivation **kills active
  sessions**, and roles get a periodic least-privilege review (P2).

## 11. Onboarding standard *(quality item Q6)*

- The professional test: a new developer (or a rebuilt machine) goes from
  `git clone` to a working, **demo-school-seeded** app in **≤15 minutes** following
  only the README — documented setup steps + seeders, powered by the Q2 factories.
  If a step lives only in someone's head or a chat log, it isn't done.
