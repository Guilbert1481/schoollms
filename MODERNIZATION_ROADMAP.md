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

## Phase 2 — File Upload & XSS Hardening  ✅ *(completed & verified 2026-07-09)*

- [x] **C2** Government IDs + enrollment documents moved off the public disk to the private `local` disk; gated serving via `Documents\SecureDocumentController` + `routes/documents.php` (owner / same-school staff / superadmin, else 404). Idempotent `documents:relocate-private` command moved existing files (1 relocated, public copy removed). **Verified:** old public `/storage/id_documents/...` URL now 403/404; 7 feature tests (owner+staff allowed, peer student & cross-school registrar 404, guest redirected, public disk clean). *Completed 2026-07-09.*
- [x] **H3** New `App\Services\Uploads\SecureUpload` — server-side `mimes` allow-list + image decode/re-encode (strips embedded scripts/polyglots; SVG/HTML rejected; random filenames). Wired into `EnrollmentController` (photo/ID/docs), `Communication\ChatController` (attachment allow-list + image re-encode), `FormController` (branding). **Verified:** 5 tests incl. appended-`<script>` payload stripped on re-encode, SVG + HTML-as-image rejected, PDF allowed. *Completed 2026-07-09.*
- [x] **H5** `admin/quotes/index.blade.php` `{!! toJson() !!}` → `@json()`; `components/table/table-body.blade.php` raw-column security contract documented (producers verified to `e()` user data). *Completed 2026-07-09.*

**Safety:** valid PNG/JPG/PDF keep working; only disallowed types rejected; re-encode is visually identical.
**Verify:** ✅ full suite green (59 passed); `evil.svg`/HTML rejected; re-encode strips payload; ID-doc URL no longer public; quote data embedded via `@json()`.

> **Milestone reached:** with Phase 2 done, Sophentis clears the single-school production-pilot security bar
> (pending Phase 2.5 intra-school authorization for the multi-user case).

---

## Phase 2.5 — Intra-School Authorization (user-to-user isolation)

> Added 2026-07-09 from the operator's goal review. `BelongsToSchool` stops School A reading School B,
> but does **nothing** between two users of the same school. C1 was one instance of this bug class fixed
> individually; this phase sweeps the whole class. Exploitable with any student login → same priority tier as Phase 2.

- [ ] **A1** Laravel Policies on user-owned models — `Invoice`, `Payment`, `StatementOfAccount`,
  grades/test results, `StudentEnrollment`, `EnrollmentDocument`, `ChatThread`/`ChatMessage`. One auditable
  ownership rule per model instead of scattered `abort_unless` lines. Generalize the parent portal's
  `ResolvesChildren` chokepoint pattern.
- [ ] **A2** Route sweep — audit every route that takes a user-owned ID (`/invoices/{id}`,
  `/grades/{student}`, …) and confirm it passes through a Policy/ownership check, not just the tenant scope.
- [ ] **A3** Raw-query & mass-assignment sweep — audit all `DB::raw`/`whereRaw` for injection and
  `$fillable` on sensitive models (extends the parent-portal `password_change_required` fix).

**Safety:** policies are additive; legitimate owner/staff access keeps working.
**Verify:** Student A requesting Student B's invoice/grade (same school) → 403/404; owner and authorized staff unaffected.

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
**Verify:** site renders with headers; 2FA challenge appears post-login. M2 note: 2FA **mandatory** for
staff roles (admin/finance/registrar), optional for students.

---

## Phase 6 — Data Protection  *(added 2026-07-09 — "assume breach" layer)*

- [ ] **D1** Backups: automated, **encrypted, off-site, restore-tested**. Nothing exists today. The single
  most important addition in Phases 6–8 — ransomware/disk failure is a bigger practical risk than any attacker,
  and data loss is irreversible.
- [ ] **D2** Encryption at rest for crown jewels: Laravel `encrypted` casts on government-ID numbers (and
  similarly sensitive columns); encrypted storage for uploaded ID files (builds on C2). ⚠ Plan together with
  the P1 key-rotation procedure — `APP_KEY` rotation re-encrypts these.
- [ ] **D3** PII retention/deletion policy — how long ID docs and PII of rejected/never-enrolled applicants
  are kept; delete on schedule (minors' data — also an RA 10173 concern, see P3).
- [ ] **D4** Staff session hardening — shorter lifetime / idle timeout for admin/finance/registrar (currently
  480 min, `config/session.php:35`); `expire_on_close` for shared school computers.

**Safety:** casts are per-column and reversible; backups/retention are additive jobs.
**Verify:** DB dump shows ciphertext for ID columns; a backup restore is actually performed on a scratch DB; expired staff session forces re-login.

---

## Phase 7 — Supply Chain & Monitoring  *(added 2026-07-09)*

- [ ] **S1** `composer audit` + `npm audit` steps in `.github/workflows/ci.yml` (CI currently tests only —
  known CVEs in dependencies are the most common real-world entry point).
- [ ] **S2** Enable Dependabot (or Renovate) on the GitHub repo.
- [ ] **S3** Production error monitoring (Sentry/Flare-class) — probing attempts surface as exceptions first.
- [ ] **S4** Ship/back up audit logs off the app database so a DB-level attacker can't erase their trail
  (builds on Phase 3 H2 / Phase 4).

**Safety:** all additive/observability-only; CI audit can start non-blocking then be promoted to required.
**Verify:** CI fails on a known-vulnerable package pin; test exception appears in the monitor; audit rows exist off-box.

---

## Phase 8 — Process & Compliance  *(added 2026-07-09 — people layer)*

- [ ] **P1** One-page incident-response plan: who is called, mass force-logout procedure, key/credential
  rotation runbook (coordinate with D2), school notification steps.
- [ ] **P2** Offboarding/account lifecycle: staff deactivation procedure that also **kills active sessions**;
  periodic least-privilege role review (readable via the Phase 3 audit log).
- [ ] **P3** RA 10173 (PH Data Privacy Act) items: designated DPO, privacy notice + consent (enrollment
  consent already started), **72-hour NPC + data-subject breach notification** readiness (depends on
  Phases 3–4 logs to know what leaked).
- [ ] **P4** External penetration test — the graduation exam, after Phases 2–6 are done and before
  multi-school SaaS is marketed as production-grade.

**Safety:** process/documentation only; no runtime changes except session-kill on deactivation.
**Verify:** tabletop walkthrough of the IR plan; deactivated staff user's open session is dead on next request.

---

## Deferred / Low (cleanup, schedule opportunistically)

- [ ] **L1** Login error-message enumeration (`LoginController.php:63,99,123,139`) — unify messages.
- [ ] **L2** Remove tracked junk (`download_test.bin`, `deploy.sh` review); clear working-tree scratch files. *(Both still tracked as of 2026-07-09.)*
- [ ] **L3** Chat attachment public-disk copy (`Communication/ChatController.php:471,500` serve gated, but the public-disk copy at `:294` bypasses it).
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
| A1 | No Policies on user-owned models (intra-school IDOR class) | 2.5 |
| A2 | Routes trust user-owned IDs (ownership unchecked) | 2.5 |
| A3 | Raw-query / mass-assignment sweep | 2.5 |
| H2 | No finance/grade audit log | 3 |
| M3 | No CSP/headers, secure cookie | 5 |
| M2 | 2FA installed but unused (staff-mandatory) | 5 |
| D1 | No backups | 6 |
| D2 | No encryption at rest for gov IDs | 6 |
| D3 | No PII retention policy | 6 |
| D4 | 8h staff sessions | 6 |
| S1 | No dependency audit in CI | 7 |
| S2 | No Dependabot | 7 |
| S3 | No error monitoring | 7 |
| S4 | Audit logs erasable with the DB | 7 |
| P1 | No incident-response plan | 8 |
| P2 | No offboarding/session-kill procedure | 8 |
| P3 | RA 10173 readiness (DPO, 72h breach notice) | 8 |
| P4 | No external pen test | 8 |
| L1–L4 | Low / cleanup | Deferred |

> **Milestone:** after **Phases 2 + 2.5**, Sophentis is safe for a single-school production pilot.
> After **Phase 4**, it is credible for multi-school SaaS (pending a real test suite).
> After **Phases 6–8**, it is a complete security *program* (prevention + detection + recovery + process),
> validated by the P4 pen test.
>
> **Priority within the 2026-07-09 additions:** D1 backups → D2 encrypted casts → S1 CI audit steps; the rest
> follow phase order.
