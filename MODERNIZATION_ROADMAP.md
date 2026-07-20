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

- [x] **A1** Laravel Policies on user-owned models. Added 8 auto-discovered Policies (`app/Policies/`):
  `InvoicePolicy` (view/pay), `PaymentPolicy`, `StatementOfAccountPolicy`, `StudentEnrollmentPolicy`
  (view/update — student_id references students.id, resolved via student.user_id), `EnrollmentDocumentPolicy`,
  `StudentPolicy::viewDocuments`, `ChatThreadPolicy` (view/delete/manage), `ChatMessagePolicy`. The scattered
  `abort_unless` chokepoints (Student\FinanceController, CheckoutController::authorizeInvoice,
  ChatController::authorizeThread, SecureDocumentController) now delegate to them. Grades/test results have no
  student-facing ID-taking routes (report card/transcript are auth-scoped index pages); staff grade routes stay
  role+tenant gated. Parent portal keeps `ResolvesChildren` (already a single chokepoint). *Completed 2026-07-18.*
- [x] **A2** Route sweep of every user-owned-ID route. **Three live intra-school IDORs found & fixed** in
  `Public\EnrollmentController`: `confirmation`, `exam`, and `submitExam` loaded any `StudentEnrollment` by ID
  with no ownership check — `submitExam` let any student POST a pass/fail outcome for anyone's enrolment.
  All three now require the owning student (404 on foreign IDs). Verified guarded: student finance PDFs,
  checkout, chat (+attachments), secure documents, trainee training payments (trainee_id-scoped), parent portal,
  staff routes (role+tenant). *Completed 2026-07-18.*
- [x] **A3** Raw-query & mass-assignment sweep. All 37 files with `DB::raw`/`whereRaw`/`selectRaw` audited —
  every site is static SQL or `?`-bound; zero injection. `$guarded=[]` is unused anywhere. **One live
  mass-assignment hole fixed**: `Staff\ProgramHead\SubjectController::store` passed `$request->all()` into
  `Subject::create()`, letting a program head spoof `school_id` (cross-tenant write) and `created_by`; now an
  explicit whitelist with server-side `school_id`/`created_by`. Latent (documented, not exploitable today):
  `User.$fillable` still exposes `role`/`school_id` — safe while all writers use `only()`/`validated()`.
  *Completed 2026-07-18.*

**Safety:** policies are additive; legitimate owner/staff access keeps working.
**Verify:** ✅ `IntraSchoolAuthorizationTest` 6/6 (18 assertions): same-school peer → 404 on another's enrolment
confirmation / exam submit (status unchanged) / invoice PDF / SOA PDF, 403 on checkout & foreign chat thread;
owner + same-school finance staff allowed, cross-school finance denied; program head cannot spoof
`school_id`/`created_by` on subject create. Full suite: 213 passed — only the 4 `OmrGradingTest` failures
owned by the in-flight OMR session remain (choice-shuffle work, unrelated).

> **Phase 2.5 complete (2026-07-18)** — the multi-user intra-school security bar is now met.

---

## Phase 3 — Finance Auditability  ✅ *(completed & verified 2026-07-18; live-DB `php artisan migrate` pending — operator-gated)*

- [x] **H2** `audit_logs` table (append-only: INSERT-only, no `updated_at`, indexed plain columns — no FK
  constraints so pruning users/schools never cascades into history) + `App\Models\Traits\Auditable` concern
  (model events → actor/school/event/before/after; secrets `password`/`remember_token`/`api_key` always
  excluded; per-model `$auditOnly`/`$auditExclude`). Attached to: `Payment`, `Invoice`, `LedgerEntry`,
  `PaymentSubmission` (verify/reject decisions), `StatementOfAccount`, `FinanceDiscountType`,
  `ReportCardGrade`, `PermanentRecordGrade`, `ComponentScore`, `User` (`$auditOnly` = role/school_id/status —
  profile edits stay out), and `AiProvider` (**AI2 ✅** — api_key values never reach the log). Audit rows
  write inside the caller's transaction, so a money change and its audit row commit or roll back together.
  OMR models untouched (in-flight session). *Completed 2026-07-18.*
- [x] Idempotency / duplicate-payment guard, centralized in `PaymentService::assertNotDuplicate` — with an
  external reference: same student+amount+reference blocks at any age; without one: identical
  student+amount+method(+invoice) inside 2 minutes blocks (double-click). `LedgerController::recordPayment`
  refactored through `PaymentService::recordGeneralPayment` (custom `paid_at` supported), so ALL payment
  creation shares the one guarded path (finance store, proof verify, training, manual ledger entry).
  *Completed 2026-07-18.*

**Safety:** audit is a passive observer; idempotency only blocks exact duplicates.
**Verify:** ✅ `FinanceAuditTrailTest` 7/7 (35 assertions): payment → audit rows for Payment + LedgerEntry with
actor/school; same-reference resubmit → validation error, 1 payment/1 credit; no-reference identical resubmit
→ blocked; distinct payments (new amount / new reference) → all post; grade edit → before/after audited;
role change audited while profile rename is not; AiProvider create+update audited with zero api_key leakage.
Full suite 233 passed (only the 4 in-flight-OMR-session failures remain). **Deploy note:** needs
`php artisan migrate` on the VPS (creates `audit_logs` only) — operator runs it per the no-self-authorized-
migrations rule.

---

## Phase 4 — Logging & Monitoring  ✅ *(completed & verified 2026-07-18; live-DB `php artisan migrate` pending — operator-gated)*

- [x] Login success/failure log: append-only `login_logs` (INSERT-only, no FKs) +
  `App\Listeners\LogAuthenticationActivity` on Laravel's native `Login`/`Failed` auth events (registered via
  event auto-discovery — NOT also `Event::listen`, which double-fires; test pins exactly-one-row).
  Discovery finding: `routes/superadmin/logs.php` was DEAD (never registered in bootstrap, controller
  didn't exist, and it declared no auth middleware). Rebuilt: registered in `bootstrap/app.php`, gated
  `auth`+`role:superadmin`+`2fa`, new `Superadmin\LogController::logins` (search + outcome filter,
  paginated) + `superadmin/logs/logins` view; the old dead `/activity`, `/errors`, `/clear` entries were
  dropped — no clear action by design, logs are append-only. *Completed 2026-07-18.*
- [x] Admin-action log: `Auditable` (Phase 3) extended to `SchoolSetting`, `SystemSetting`,
  `FinanceSetting`, `EnrollmentSetting`, `GradingSystem`, with `$auditExclude` keeping
  `smtp_password`/`imap_password`/`sms_api_key`/`sms_api_secret` out of audit rows. Grade-change log was
  already delivered by Phase 3 (grade models carry the trait). Failed-auth threshold alert:
  ≥10 failures from one IP or for one email within 15 min → searchable `Log::warning('auth.failed.threshold')`
  on every attempt past the threshold (the Phase 0 login throttle does the actual slowing). *Completed 2026-07-18.*

**Safety:** observers only. **Verify:** ✅ `LoginActivityLogTest` 6/6 (24 assertions): success login → exactly
one row (email/user/school/IP); failed login → failed row, no success row; 10th failure fires the warning;
superadmin page 200 with data + outcome filter, school admin 403; `GradingSystem` change audited before/after;
`FinanceSetting.smtp_password` change leaves no secret in any audit row. Full suite **252 passed, 0 failed**
(the 4 OMR failures were fixed by the in-flight OMR session). **Deploy note:** VPS `php artisan migrate`
creates `login_logs` (+ `audit_logs` from Phase 3) — operator-run.

---

## Phase 5 — Production Hardening

- [x] **M3** `SecurityHeaders` middleware on the `web` group: CSP **report-only** (tolerant of current
  inline/CDN/ws debt so the report phase measures real violations — flip to enforcing after a clean
  release), `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Referrer-Policy`,
  `Permissions-Policy` (camera=self for OMR/QR), HSTS only on TLS/prod. Test `SecurityHeadersTest`.
  *Completed 2026-07-20.*
- [x] **M3** `SESSION_SECURE_COOKIE` now defaults ON in production (`config/session.php` — explicit env
  still wins); added `config/cors.php` (empty allow-list — same-origin Blade app, nothing cross-origin).
  *Completed 2026-07-20.*
- [x] **M2** 2FA enforced via `TwoFactorMiddleware` on the `web` group (not just superadmin): enrolled
  users of any role face the once-per-session challenge; `security.two_factor_mandatory_roles`
  (superadmin/admin/finance_manager/registrar) are locked to `/2fa/setup` until enrolled, gated by
  `security.enforce_2fa` (off for the suite via phpunit, on in prod). New `Auth\TwoFactorController`
  (setup → confirm w/ 8 single-use recovery keys → challenge → recovery) + `/2fa/*` routes + views;
  replaced the dead `route('2fa.verify')` the old middleware pointed at with nothing. Test
  `TwoFactorEnforcementTest` 6/6. *Completed 2026-07-20.*

- [x] **M6** Password-change/reset session eviction — `AuthenticateSession` appended to the `web`
  group (`bootstrap/app.php`) so changing/resetting a password logs out every other session for that
  account (`SECURITY_PRINCIPLES.md` §15). **Verified:** `PasswordChangeSessionEvictionTest` (2 tests —
  session survives unchanged password; stale session evicted after change); full suite run, only
  pre-existing OMR decimal failure unrelated. *(Added & completed 2026-07-17 — ADR-0008.)*
- [x] **H6** Rate-limit sweep beyond login. Named limiters (`chat` 30/min/user, `uploads`
  20/min/user, `ai` 10/min/user, `public-apply` 10/min/IP) in
  `AppServiceProvider::configureRateLimiting`; attached to chat `message.store`, `form.save`,
  enrollment `store`/`pathway.store` (the file-bearing steps), and the public QR `login`/`register`
  POSTs. Password reset was already `throttle:6,1`; upload size capped by `SecureUpload` (`max:5120`)
  and chat (`max:20480`). **Verified:** `RateLimitSweepTest` — real 429 on the 31st chat message +
  route-table wiring assertions; Enrollment suites green. Remaining: attach `throttle:ai` to the
  AI/OCR endpoints (tracked as AI3 — those routes belong to the in-flight OMR work); note nginx
  `client_max_body_size` in the deploy runbook at VPS setup. *(Added & completed 2026-07-18.)*
- [x] **AI1** AiProvider hardening at release: `encrypted` cast on `api_key` ✅ (shipped with the
  feature), masked round-trip ✅, routes `role:superadmin`+`2fa` ✅, `base_url` scheme validation
  `url:http,https` ✅ (SSRF — `SECURITY_PRINCIPLES.md` §16/§18). *(Added & completed 2026-07-17.)*
- [x] **AI2** Audit AiProvider changes (provider/key/URL/default) — done with Phase 3 (2026-07-18):
  `Auditable` on `AiProvider`; the trait hard-excludes `api_key`, so key *changes* are visible as
  events while key *values* never reach the log. Test: `FinanceAuditTrailTest`.
- [ ] **AI3** AI usage guardrails: attach the ready-made `throttle:ai` limiter (H6) to AI/OCR
  endpoints when that in-flight work lands; prompts carry minimum PII (no government-ID numbers);
  model output treated as untrusted input at every consumer (`SECURITY_PRINCIPLES.md` §18).

**Safety:** CSP starts in report-only so inline scripts/styles aren't broken; headers append-only.
M6 is additive middleware — existing sessions self-heal (hash stored on next request).
**Verify:** site renders with headers; 2FA challenge appears post-login; after a password reset the
old session's next request is logged out. M2 note: 2FA **mandatory** for
staff roles (admin/finance/registrar), optional for students.

---

## Phase 6 — Data Protection  *(added 2026-07-09 — "assume breach" layer)*

- [~] **D1** Backups: automated, **encrypted, off-site, restore-tested**. **Scripts drafted 2026-07-20**
  (`scripts/backup/db-backup.sh` — mysqldump → gzip → AES-256, local rotation, off-site rclone, reads DB
  creds from `.env`; `db-restore.sh` — scratch-DB by default, explicit confirm to overwrite live;
  `backup.env.example`). **Operator must still:** create `/etc/sophentis-backup.env` (passphrase +
  healthcheck + rclone remote), run a manual backup, run a restore-test, and cron it. Not "done" until a
  restore is proven on the VPS. Still the single most important remaining item — data loss is irreversible.
- [~] **D2** Encryption at rest for crown jewels. **Column encryption DONE (2026-07-20):** `encrypted` cast on
  `students.government_id_number` (a minor's government-ID number — the crown-jewel PII), column widened to TEXT
  (migration `2026_07_20_090000`), idempotent backfill command `students:encrypt-government-ids` (dry-run by
  default, `--encrypt` to write; reads raw, skips already-ciphertext rows). Verified `StudentIdEncryptionTest`
  3/3 — ciphertext at rest, plaintext on the model, idempotent backfill. LRN deliberately left plaintext (it is
  a functional identifier / ID-card barcode source, and encrypted columns can't be queried). ⚠ `APP_KEY`
  rotation must re-encrypt these — fold into the P1 key-rotation procedure. **Operator (live):** `php artisan
  migrate` then a backup, then `students:encrypt-government-ids --encrypt`.
- [x] **D2b** Encryption at rest for uploaded ID **files** — ✅ **DONE (2026-07-20) — ready for commit.**
  `SecureUpload::storeImage/storeDocument/storeImageOrDocument` gained an `encrypt` flag (wraps bytes in
  `Crypt::encryptString` before `Storage::put`); wired `encrypt: true` for the government-ID file and
  registrar-required enrolment documents in `EnrollmentController` (profile avatars stay plaintext — public).
  `SecureDocumentController::serve` now decrypts on the way out (try-decrypt with legacy-plaintext fallback, so
  serving stays backward-compatible mid-migration) and sets Content-Type from the extension. Idempotent backfill
  `documents:encrypt-id-files` (dry-run default, `--encrypt`) covers `students.photo_id` +
  `enrollment_documents.file_path`. Verified `StudentIdFileEncryptionTest` 4/4 (ciphertext at rest; serve
  decrypts; legacy plaintext still served; backfill idempotent) + C2 `SecureDocumentAccessTest` 7/7 unchanged.
  **Operator (live):** after deploy + backup, run `php artisan documents:encrypt-id-files --encrypt`.
- [ ] **D3** PII retention/deletion policy — how long ID docs and PII of rejected/never-enrolled applicants
  are kept; delete on schedule (minors' data — also an RA 10173 concern, see P3).
- [ ] **D4** Staff session hardening — shorter lifetime / idle timeout for admin/finance/registrar (currently
  480 min, `config/session.php:35`); `expire_on_close` for shared school computers.
- [~] **D5** Backup dead-man switch — **built into `db-backup.sh` (2026-07-20)**: pings `HEALTHCHECK_URL`
  only after a fully successful run, and pings `/fail` on error, so a missed/failed backup alerts the
  operator. Inert until the operator sets `HEALTHCHECK_URL` (healthchecks.io-class) in the backup env.
  *(Added 2026-07-17, Argo-parity — proven pattern there.)*

**Safety:** casts are per-column and reversible; backups/retention are additive jobs.
**Verify:** DB dump shows ciphertext for ID columns; a backup restore is actually performed on a scratch DB; expired staff session forces re-login.

---

## Phase 7 — Supply Chain & Monitoring  *(added 2026-07-09)*

- [x] **S1** `composer audit` + `npm audit` steps — ✅ **DONE (2026-07-20) — ready for commit.** New
  `security-audit` job in `.github/workflows/ci.yml` (runs `composer audit` + `npm audit --audit-level=high`).
  Non-blocking (`continue-on-error: true`) to start, matching the code-style/static-analysis jobs — surfaces
  CVEs on every PR now; drop that line to make a vulnerable dependency block once current advisories are
  triaged. Note: `composer audit` already flags a real one locally (symfony/yaml CVE-2026-45133) — worth a
  patch bump. YAML validated.
- [x] **S2** Dependabot — ✅ **DONE (2026-07-20) — ready for commit.** Added `.github/dependabot.yml`
  (weekly composer + npm + github-actions updates, grouped so routine bumps are one PR each; GitHub still
  raises security updates immediately). Activates automatically once merged to the default branch. YAML validated.
- [ ] **S3** Production error monitoring (Sentry/Flare-class) — probing attempts surface as exceptions first.
- [ ] **S4** Ship/back up audit logs off the app database so a DB-level attacker can't erase their trail
  (builds on Phase 3 H2 / Phase 4).
- [ ] **S5** External uptime monitoring on the framework `/up` health endpoint for
  `sophentis.philceb.ph` (+ one school host), alerting the operator — downtime is noticed by us,
  not by a school. *(Added 2026-07-17.)*

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
- [x] **P5** DR runbook — **drafted 2026-07-20**: `docs/deploy/disaster-recovery-runbook.md` (what's
  backed up + where, RPO/RTO targets, prerequisites-off-server, DB-rollback + full-host-rebuild scenarios,
  quarterly restore-drill procedure + log, known gaps: uploaded files + `.env` not yet auto-backed-up).
  Superseded line below kept for provenance.
- [ ] **P5 (original)** DR runbook — extend `docs/deploy/` with: what is backed up and where, encryption keys
  (⚠ `APP_KEY` ↔ D2 casts), RTO/RPO targets, exact restore commands, VPS rebuild-from-zero path.
  Kept accurate: any change adding a stateful store updates it in the same change
  (`FULL_PRODUCTION_STACK.md` layer 13). *(Added 2026-07-17.)*

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
| M6 | Password change doesn't evict other sessions | 5 |
| H6 | Rate limits only on login; no request-size rules | 5 |
| AI1 | AiProvider base_url unvalidated (SSRF); key/gating shipped ✅ | 5 |
| AI2 | AiProvider changes unaudited (needs H2 table) | 5 |
| AI3 | No AI throttle / PII-minimization / output-trust rules wired | 5 |
| D1 | No backups | 6 |
| D2 | No encryption at rest for gov IDs | 6 |
| D3 | No PII retention policy | 6 |
| D4 | 8h staff sessions | 6 |
| D5 | No alert on silently-failed backups (dead-man switch) | 6 |
| S1 | No dependency audit in CI | 7 |
| S2 | No Dependabot | 7 |
| S3 | No error monitoring | 7 |
| S4 | Audit logs erasable with the DB | 7 |
| S5 | No uptime monitoring on production hosts | 7 |
| P1 | No incident-response plan | 8 |
| P2 | No offboarding/session-kill procedure | 8 |
| P3 | RA 10173 readiness (DPO, 72h breach notice) | 8 |
| P4 | No external pen test | 8 |
| P5 | No DR runbook (restore commands, RTO/RPO, rebuild path) | 8 |
| L1–L4 | Low / cleanup | Deferred |

> **Milestone:** after **Phases 2 + 2.5**, Sophentis is safe for a single-school production pilot.
> After **Phase 4**, it is credible for multi-school SaaS (pending a real test suite).
> After **Phases 6–8**, it is a complete security *program* (prevention + detection + recovery + process),
> validated by the P4 pen test.
>
> **Priority within the 2026-07-09 additions:** D1 backups → D2 encrypted casts → S1 CI audit steps; the rest
> follow phase order.
