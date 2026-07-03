# Modernization Progress — Status & Resume Snapshot

> At-a-glance status for the hardening work. The detailed plan + per-item checkboxes
> live in **[MODERNIZATION_ROADMAP.md](MODERNIZATION_ROADMAP.md)**. This file is the
> quick "where we are / what's next" handoff.
>
> **Last updated:** 2026-07-01

## Status overview

| Phase | Scope | Status |
|---|---|---|
| **G** | Governance documents | ✅ DONE |
| **0** | Emergency (login throttle, prod-debug guard) | ✅ DONE |
| **1** | Access control & tenant isolation | ✅ DONE |
| **2** | File upload & XSS hardening | ⏳ NOT STARTED ← **resume here** |
| **3** | Finance auditability | ⏳ NOT STARTED |
| **4** | Logging & monitoring | ⏳ NOT STARTED |
| **5** | Production hardening | ⏳ NOT STARTED |
| Deferred | Low / cleanup (L1–L4) | ⏳ NOT STARTED |

---

## ✅ COMPLETED

### Phase G — Governance docs (zero runtime risk)
- `MODERNIZATION_ROADMAP.md`, `ENGINEERING_PRINCIPLES.md`, `ARCHITECTURE_PRINCIPLES.md`,
  `SECURITY_PRINCIPLES.md`, `DEVELOPMENT_WORKFLOW.md` — all created.
- `README.md` — added a docs index (was stock Laravel boilerplate).

### Phase 0 — Emergency
- **H1 login rate-limit** — `AppServiceProvider::configureRateLimiting()` (named `login` limiter,
  6/min per email+IP, 20/min per IP) applied via `throttle:login` on `routes/web.php` login POSTs.
- **M5 prod-debug guard** — `AppServiceProvider::enforceProductionSafety()` forces `app.debug` off
  in production. Inert locally. *Verified.*

### Phase 1 — Access control & tenant isolation
- **M4** — `app/Models/Traits/BelongsToSchool.php`: fixed superadmin bypass to use `isSuperadmin()`.
  **Key finding:** the global scope had been a *complete no-op* (the bypass fired for every user).
  Now actually filters by `school_id`. *Verified: school user `where school_id=1`, superadmin unfiltered.*
- **C1** — `TestBuilderController.php` `index`/`edit`/`loadTest`: added
  `abort_unless((int)$test->school_id === (int)auth()->user()->school_id, 404)`. *Verified: cross-school → 404.*
- **H4** — added `BelongsToSchool` to: `Invoice`, `Payment`, `PaymentPlan`, `LedgerEntry`,
  `Scholarship`, `PenaltyRule`, `Test`, `Subject`, `EnrollmentDocument`. *Verified 18/18; 7 finance/registrar
  pages still render.*
- **M1** — password reset: `app/Http/Controllers/Auth/PasswordResetController.php`,
  `resources/views/auth/forgot-password.blade.php`, `resources/views/auth/reset-password.blade.php`,
  routes (`password.request/email/reset/update`, throttled), login "Recover?" link + status banner.
  *Verified 7/7 incl. token replay protection.*

---

## ⏳ NOT COMPLETED (do later)

### Phase 2 — File upload & XSS hardening  ← **START HERE NEXT**
- **C2** — move `id_documents` off the **public** disk (`EnrollmentController.php:190-191`) to a private
  disk + a gated download route (same-school owner/staff check). Government IDs are currently
  publicly reachable by URL.
- **H3** — add server-side `mimes` validation + image re-encode to uploaders that lack it:
  `FormController` (branding logos/header/footer/background), `ChatController.php:258` (attachments),
  `EnrollmentController.php:185-191` (photo/ID). Kills SVG/HTML stored-XSS.
- **H5** — fix unescaped Blade: `resources/views/components/table/table-body.blade.php:24`
  (`{!! data_get(...) !!}` → `{{ }}`), `resources/views/admin/quotes/index.blade.php:71`
  (`{!! $quoteRows->toJson() !!}` → `@json()`).
- ⚠️ Behavior change: H3 will reject non-image uploads; C2 changes ID-doc serving to a route
  (any hard-coded `/storage/id_documents/...` links must use the new route).

### Phase 3 — Finance auditability
- **H2** — `audit_logs` table + `Auditable` concern (payment/invoice/ledger/discount/penalty/grade/role
  changes, append-only). Idempotency/duplicate-payment guard on `PaymentController` + `LedgerController::recordPayment`.

### Phase 4 — Logging & monitoring
- Login success/failure log (backs the existing `/superadmin/logins` route — no table yet);
  admin-action log; grade-change log; failed-auth alert.

### Phase 5 — Production hardening
- **M3** — security-header middleware (CSP report-only first, `X-Frame-Options`, HSTS, `X-Content-Type-Options`);
  force `SESSION_SECURE_COOKIE=true` in prod (`config/session.php:172`); add `config/cors.php`.
- **M2** — enforce 2FA (`pragmarx/google2fa` already installed) in `LoginController::handlePostLogin`.

### Deferred / Low (L1–L4)
- **L1** unify login error messages (enumeration) — `LoginController.php:64,82`.
- **L2** remove tracked junk (`download_test.bin`, review `deploy.sh`); clear working-tree scratch files.
- **L3** chat attachment public-disk copy bypasses the gated serve route (`ChatController.php:471,500`).
- **L4** when an API is added: `routes/api.php` + API Resources for the 164 raw-model JSON returns.

---

## 🔖 Resume instructions

1. Ensure **MySQL is running** (Laragon) — needed for the DB smoke tests on each item.
2. Next action: **Phase 2 → C2** (move ID docs to a private disk + gated download route).
3. Workflow per item: additive edit → `php -l` + `php artisan route:list` → rolled-back smoke test →
   for security fixes, re-run the exploit to confirm it's blocked → mark `[x]` in the roadmap.

## ⚠️ Environment / state notes
- **Nothing committed yet.** Phase G/0/1 changes (plus an earlier large uncommitted batch: branding
  uploads, enrollment consent, etc.) are all in the working tree. Per `DEVELOPMENT_WORKFLOW.md`,
  commit on a feature branch, not straight to `main`.
- **Mail = `log`** in dev: password-reset links go to `storage/logs/laravel.log`, not real email.
  Configure SMTP for production.
- **Reverb** (websockets, `:8080`) must be running (`php artisan reverb:start`) or the browser console
  shows WebSocket errors — not related to this work.

## Files changed in Phases G/0/1
**New:** the 5 governance `.md` files, `MODERNIZATION_PROGRESS.md`,
`app/Http/Controllers/Auth/PasswordResetController.php`,
`resources/views/auth/forgot-password.blade.php`, `resources/views/auth/reset-password.blade.php`.
**Edited:** `README.md`, `app/Providers/AppServiceProvider.php`, `routes/web.php`,
`app/Models/Traits/BelongsToSchool.php`,
`app/Http/Controllers/Teacher/Test/TestBuilder/TestBuilderController.php`,
`app/Models/{Invoice,Payment,PaymentPlan,LedgerEntry,Scholarship,PenaltyRule,Test,Subject,EnrollmentDocument}.php`,
`resources/views/auth/login.blade.php`.
