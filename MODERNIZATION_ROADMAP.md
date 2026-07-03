# Sophentis / schoollms — Modernization & Security Roadmap

> Master tracker for hardening Sophentis from "advanced MVP" to "professional, production-grade Laravel."
> We resolve issues **one phase at a time**, never breaking working logic. Each item is additive,
> reversible, and verified before we move on.
>
> Created 2026-06-30. Source: the Engineering Audit + Attacker-Focused Security Audit in this repo's history.

## How to use this document

- Work **top to bottom**. A phase ships only when every item is `[x]` and verified.
- Each item carries its **finding ID** (from the audits) so it's traceable.
- Don't start a code-touching phase until the previous one is verified and committed.
- Status legend: `[ ]` not started · `[~]` in progress · `[x]` done & verified.

## The safety method (applied to every code change)

1. **Additive-first** — add new files/columns/middleware alongside existing behavior; no rename/delete in the same step.
2. **`php -l` + `php artisan route:list`** after every edit.
3. **Rolled-back smoke test** (`DB::beginTransaction()/rollBack()`) for any data path — never mutate live data.
4. **Exploit re-test** for security fixes — confirm the attack is blocked AND the legitimate flow still works.
5. **One finding ID per commit**, small diffs, easy rollback.

---

## Phase G — Governance Documents  ✅ (zero runtime risk)

Encode the rules before changing code. Pure markdown.

- [x] **G1** `ENGINEERING_PRINCIPLES.md` — coding philosophy, thin controllers, testing discipline, review process, anti-vibe rules.
- [x] **G2** `ARCHITECTURE_PRINCIPLES.md` — layering, services, repositories, events/jobs, policies, tenancy, separation of concerns.
- [x] **G3** `SECURITY_PRINCIPLES.md` — authN/authZ, RBAC, mandatory tenant scoping, uploads, secrets, audit logging (absorbs & promotes `ACCESS_CONTROL.md`).
- [x] **G4** `DEVELOPMENT_WORKFLOW.md` — RFC/ADR, branching/PR policy, design reviews, testing, release (folds in `DEPLOYMENT.md`).
- [x] Link all four + this roadmap from `README.md`.

*Completed 2026-06-30.*

---

## Phase 0 — Emergency (cheapest, safest, biggest risk drop)  ✅

- [x] **H1** Login rate-limiting. Named `login` limiter (`AppServiceProvider::configureRateLimiting`) — 6/min per email+IP, 20/min per IP — applied via `throttle:login` to `POST /login` and `POST /{slug}/login` (`routes/web.php:31,35`).
- [x] **M5** Production debug guard. `AppServiceProvider::enforceProductionSafety` logs critical + forces `app.debug` off at runtime if `APP_ENV=production` && `APP_DEBUG=true`. Inert locally.

**Safety:** throttle only fires after repeated failures; debug guard inert locally.
**Verify:** ✅ `route:list -v` shows `throttle:login` on both routes; limiter returns 6/min keyed on email|ip; guard inert in local env.

*Completed 2026-06-30.*

---

## Phase 1 — Access Control & Tenant Isolation  ✅ *(highest care — changes query behavior)*

- [x] **M4** Fix `super_admin` vs `superadmin` mismatch (`app/Models/Traits/BelongsToSchool.php`). **Done & verified.**
  - **DISCOVERY:** `$user->role('super_admin')` invoked the `role()` *relationship* (returns a truthy `BelongsTo`), so the superadmin bypass fired for **every** user — the global scope was a **complete no-op**. Isolation held only because controllers add `where('school_id')` manually.
  - **Fix:** use `$user->isSuperadmin()` in both the scope bypass and the `creating` auto-assign.
  - **Verified:** raw SQL as school user = `select * from students where students.school_id = 1`; as superadmin = `select * from students`. Live count match (scoped == raw school count); lint clean. Activates real tenant scoping on the 15 `BelongsToSchool` models. *Completed 2026-06-30.*
- [x] **C1** Test/quiz cross-tenant IDOR. Added `abort_unless((int) $test->school_id === (int) auth()->user()->school_id, 404)` to `index`, `edit`, `loadTest` in `TestBuilderController`. Verified: cross-school `edit()` → 404, same-school allowed, lint clean. *Completed 2026-06-30.*
- [x] **H4** Tenant-scope unscoped models: added `BelongsToSchool` to `Invoice`, `Payment`, `PaymentPlan`, `LedgerEntry`, `Scholarship`, `PenaltyRule`, `Test`, `Subject`, `EnrollmentDocument`. Consumer scan showed no cross-school queries (they use `findOrFail`/`where('name')` — now protected). Verified 18/18: school user → `school_id` filter active + counts match (Subject 241==241, etc.); superadmin → unfiltered + sees all. Subject's temp `school_id=1` creating-hook still works (trait boots first). Lint clean. *Completed 2026-06-30.*
- [x] **M1** Password-reset flow. Added `Auth\PasswordResetController` + `auth.forgot-password`/`auth.reset-password` views (CDN-Tailwind, matches login) + routes (`password.request/email/reset/update`, throttled). Wired the dead "Recover?" link + a reset-success status banner on login. Did NOT touch `LoginController`. Verified 7/7: send-link → `RESET_LINK_SENT` + notification; reset → password changed + verifies; reused token → `INVALID_TOKEN`; rolled back. *Completed 2026-06-30.*

**Safety:** trait skips when unauthenticated (seeders/CLI safe), bypasses superadmin, auto-assigns only when empty.
**Verify:** finance manager sees only own school; superadmin sees all; exports still work; foreign `testId` → 404.

---

## Phase 2 — File Upload & XSS Hardening

- [ ] **C2** Move `id_documents` off the public disk (`EnrollmentController.php:190-191`) → private `local` disk + gated download route (`abort_unless` same-school owner/staff).
- [ ] **H3** Server-side `mimes` validation + image re-encode on `FormController` branding uploads, `ChatController.php:258`, `EnrollmentController` photo/ID. Re-encoding strips embedded scripts (kills SVG/HTML XSS).
- [ ] **H5** Fix unescaped Blade: `components/table/table-body.blade.php:24` → `{{ }}` (or per-column allow-HTML flag); `admin/quotes/index.blade.php:71` → `@json()`.

**Safety:** valid PNG/JPG/PDF keep working; only disallowed types rejected; re-encode is visually identical.
**Verify:** normal logo uploads; `evil.svg` rejected/neutralized; ID-doc URL 403s without auth; `<script>` in a name renders escaped.

---

## Phase 3 — Finance Auditability

- [ ] **H2** `audit_logs` table + `Auditable` concern; record actor/school/before/after/timestamp on payment, invoice, ledger, discount, penalty, grade, role changes. Append-only, passive (model events).
- [ ] Idempotency / duplicate-payment guard on `PaymentController` + `LedgerController::recordPayment`.

**Safety:** audit is a passive observer; idempotency only blocks exact duplicates.
**Verify:** payment → audit row; resubmit → blocked; distinct payments unaffected.

---

## Phase 4 — Logging & Monitoring

- [ ] Login success/failure log (backs the existing `/superadmin/logins` route which has no table).
- [ ] Admin-action log; grade-change log (builds on Phase 3); failed-auth threshold alert.

**Safety:** observers only. **Verify:** rows written on login/failed login/grade edit.

---

## Phase 5 — Production Hardening

- [ ] **M3** Security-header middleware (CSP **report-only first**, `X-Frame-Options`, HSTS, `X-Content-Type-Options`).
- [ ] **M3** Force `SESSION_SECURE_COOKIE=true` in prod (`config/session.php:172`); add `config/cors.php`.
- [ ] **M2** Enforce 2FA (`pragmarx/google2fa` already installed) in `LoginController::handlePostLogin`.

**Safety:** CSP starts in report-only so inline scripts/styles aren't broken; headers append-only.
**Verify:** site renders with headers; 2FA challenge appears post-login.

---

## Deferred / Low (cleanup, schedule opportunistically)

- [ ] **L1** Login error-message enumeration (`LoginController.php:64,82`) — unify messages.
- [ ] **L2** Remove tracked junk (`download_test.bin`, `deploy.sh` review); clear working-tree scratch files.
- [ ] **L3** Chat attachment public-disk copy (`ChatController.php:471,500` serve gated, but public copy bypasses it).
- [ ] **L4** When an API is added: `routes/api.php` + API Resources for the 164 raw-model JSON returns.

---

## Finding → Phase index

| ID | Finding | Phase |
|----|---------|-------|
| G1–G4 | Governance docs | G ✅ |
| H1 | No login rate-limit | 0 |
| M5 | APP_DEBUG prod guard | 0 |
| M4 | superadmin role mismatch | 1 |
| C1 | Test/quiz cross-tenant IDOR | 1 |
| H4 | Unscoped tenant models | 1 |
| M1 | No password reset | 1 |
| C2 | ID docs on public disk | 2 |
| H3 | Unrestricted upload MIME | 2 |
| H5 | Unescaped Blade output | 2 |
| H2 | No finance/grade audit log | 3 |
| M3 | No CSP/headers, secure cookie | 5 |
| M2 | 2FA installed but unused | 5 |
| L1–L4 | Low / cleanup | Deferred |

> **Milestone:** after **Phase 2**, Sophentis is safe for a single-school production pilot.
> After **Phase 4**, it is credible for multi-school SaaS (pending a real test suite).
