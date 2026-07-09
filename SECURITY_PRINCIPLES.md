# Security Principles — Sophentis / schoollms

> The non-negotiable security rules. This document **supersedes and incorporates**
> `ACCESS_CONTROL.md` (kept as the detailed route/table playbook). When in doubt,
> deny. Sophentis holds minors' PII, government IDs, grades, and money — treat it
> accordingly.

## 0. The #1 rule (from ACCESS_CONTROL.md)

> **Hiding a menu item is NOT security.** Any logged-in user can type a URL.
> Every non-public route MUST be gated by role at the route level, and every
> record access MUST be scoped to the user's school.

A student once reached the admin User Management page. That is the failure mode we
design against.

## 1. Authentication

- Login is custom (`Auth/LoginController`); it must keep: `session()->regenerate()`
  on success, school approval/active checks, and `regenerateToken()` on logout.
- **Login endpoints MUST be rate-limited** (`throttle`). Brute-force protection is
  mandatory (Roadmap Phase 0).
- **A password-reset flow MUST exist** (Roadmap Phase 1). Manual admin resets are a
  takeover vector.
- **2FA** (`pragmarx/google2fa` is installed) is enforced for privileged roles —
  **mandatory for staff roles** (admin, finance, registrar), optional for students
  (Roadmap Phase 5).
- Login error messages must not enable account/tenant enumeration.

## 2. Authorization & RBAC

- **Coarse:** `role:` middleware (`CheckRole`) on every non-public route group.
  Always include `admin,superadmin` on staff/management routes.
- **Fine:** Policies (`app/Policies`) for record-level decisions on finance,
  students, grades, enrollment.
- **Ownership is not tenancy.** The tenant scope passes for *any* two users of the
  same school — it does NOT stop Student A reading Student B's invoice or grade.
  Every route that takes a **user-owned** ID (`/invoices/{id}`, `/grades/{student}`, …)
  MUST also pass an ownership check via a Policy (or an equivalent single
  chokepoint — canonical pattern: the parent portal's `ResolvesChildren` concern).
  The C1 test-IDOR was one instance of this bug class; Roadmap **Phase 2.5**
  (A1–A3) sweeps the rest.
- **Canonical role names** (snake_case; the middleware normalizes case/spaces/hyphens):

  ```
  superadmin   admin        academics            admission_manager
  registrar    finance_manager                   dean
  principal    program_head course_architect     teacher
  guidance_counselor        student              trainee
  trainor      training_program_head
  ```

  The superadmin value is exactly `superadmin` everywhere (no `super_admin`).

## 3. Multi-tenant isolation (the core SaaS guarantee)

- **Every `school_id` model uses `BelongsToSchool`.** No exceptions for tenant data.
- **Route-model-bound records get an explicit check too:**
  `abort_unless($record->school_id === auth()->user()->school_id, 404)`.
  Pattern: `InvoiceController::authorizeSchool`, `LedgerController::drawer/entries`.
- Never load a tenant record by a raw request ID without scoping. The Test/quiz
  IDOR (`TestBuilderController`) is the exact mistake to never repeat.
- The generic CRUD (`BaseCrudController`) shows the gold standard: table allowlist +
  tenant scope + column allowlist.

## 4. Input validation & injection

- Validate every write (Form Requests preferred). `exists:` rules must be combined
  with a tenant scope (e.g. `User::where('school_id', $actor->school_id)->findOrFail($id)`).
- Models declare `$fillable`; never `$guarded = []` for user-facing models.
- Prefer Eloquent/parameter binding. Raw SQL must use bindings and literal column
  expressions only — never interpolate request data into a query string.

## 5. XSS & output encoding

- Default to `{{ }}`. **`{!! !!}` is banned for any value that could contain user
  input.** Current offenders to fix: `components/table/table-body.blade.php`
  (renders arbitrary cell values raw) and `admin/quotes/index.blade.php` (model
  JSON into `<script>` — use `@json()` / `Js::from()`).
- Inject server data into JS only via `@json()`.

## 6. File uploads

- **Validate `mimes`/`mimetypes` server-side.** `accept="image/*"` is a UI hint, not
  security.
- **Re-encode uploaded images** (strips embedded scripts — neutralizes SVG/HTML XSS).
- **Sensitive files (ID documents) go on a PRIVATE disk**, served only through an
  authorized download route with a same-school ownership check. Never the `public`
  disk. (`EnrollmentController` currently stores `id_documents` publicly — Roadmap
  Phase 2.)
- Generated filenames are random; never trust the client filename for the stored path.

## 7. CSRF & requests

- Keep Laravel CSRF on all state-changing forms (`@csrf`). State changes use
  POST/PUT/PATCH/DELETE — **never a GET route that mutates data.**
- AJAX sends the CSRF token header.

## 8. Secrets & configuration

- `.env`, `.env.backup`, `.env.production`, `*.sql`, `cookies.txt` stay gitignored
  (they are — keep it). No secrets in code or committed files.
- **Production:** `APP_DEBUG=false` (enforced at boot — Roadmap Phase 0),
  `SESSION_SECURE_COOKIE=true`, config cached.

## 9. Session, cookies, headers

- `http_only=true`, `same_site=lax` (current), `secure=true` in production.
- Add security headers (CSP report-only → enforced, `X-Frame-Options`, HSTS,
  `X-Content-Type-Options`) — Roadmap Phase 5.

## 10. Audit logging & incident response

- **Every money/grade/role/enrollment mutation is audited** (actor, school,
  before/after, timestamp) in an append-only `audit_logs` table — Roadmap Phase 3.
- Login success/failure is logged — Roadmap Phase 4.
- **Audit logs are shipped/backed up off the app database** so a DB-level attacker
  cannot erase their own trail — Roadmap Phase 7 (S4). Application log lines carry
  `school_id` + `user_id` context (structured logging, quality item Q8).
- A **one-page incident-response plan** exists before it is needed: who is called,
  mass force-logout, key/credential rotation runbook, school notification —
  Roadmap Phase 8 (P1). Breach handling must satisfy **RA 10173** (PH Data Privacy
  Act): 72-hour notification to the NPC and affected data subjects (P3).
- Staff offboarding **kills active sessions**, not just the login (P2).
- The test of success: after an incident we can answer *who changed what, when.*

## 11. Threat model (who we defend against)

| Actor | Capability | Defense |
|---|---|---|
| Student/parent | guesses URLs, edits IDs | `role:` gate + `abort_unless` school check |
| **Same-school user** | edits IDs of a peer's records (intra-school IDOR) | Policy / ownership chokepoint (Phase 2.5) |
| Cross-school staff | uses valid login on another tenant's IDs | global scope + object check |
| Malicious uploader | SVG/HTML/script files | `mimes` + re-encode + private disk |
| Brute-forcer | credential stuffing | login throttle + 2FA |
| Insider | edits payment/grade silently | append-only audit log (shipped off-box) |
| **DB-dump thief** | exfiltrates the database | encryption at rest on crown-jewel columns (Phase 6 D2) |
| **Ransomware / disk failure** | destroys or encrypts the data | encrypted, off-site, restore-tested backups (Phase 6 D1) |
| **Vulnerable dependency** | known CVE in a package | `composer`/`npm audit` in CI + Dependabot (Phase 7) |

## 12. Data protection at rest & recovery *(Roadmap Phase 6)*

- **Crown jewels are encrypted at rest:** government-ID numbers (and similarly
  sensitive columns) use Laravel `encrypted` casts; uploaded ID files are stored
  encrypted on the private disk. ⚠ `APP_KEY` rotation re-encrypts these — the
  rotation runbook (P1) and the casts (D2) are planned together.
- **Backups are automated, encrypted, off-site, and restore-tested** (D1). An
  untested backup is a hope, not a control. Data loss is the one irreversible failure.
- **PII is retained only as long as needed** (D3): documented retention/deletion
  schedule, especially for rejected / never-enrolled applicants — minors' data is
  also an RA 10173 obligation.
- **Staff sessions are hardened** (D4): shorter lifetime / idle timeout for
  admin/finance/registrar; `expire_on_close` for shared school computers.

## 13. Supply chain *(Roadmap Phase 7)*

- CI runs `composer audit` + `npm audit`; Dependabot (or Renovate) is enabled on
  the repo. A known CVE in a dependency is the most common real-world entry point.
- Production runs error monitoring (S3) — probing surfaces as exceptions first.

## 14. Standing risks — security is never *done*

Four risks survive every completed roadmap phase. They are managed, never closed:

1. **Configuration drift.** Prod settings decay between releases (server moves,
   hotfixes, `.env` edits). Defenses: the boot-time guards (§8) catch the worst;
   the §9 pre-release checklist catches releases; and **production configuration
   is re-verified periodically — at minimum quarterly and after any server change**
   (`APP_DEBUG`, `SESSION_SECURE_COOKIE`, disk visibility, header middleware active).
2. **New code is new attack surface.** The system is only as secure as the last PR.
   Defenses: the approval gates below, the design-review checklist
   (`DEVELOPMENT_WORKFLOW.md` §5), and the touch-rules (FormRequests, enums,
   ownership checks) — applied on *every* change, forever.
3. **The dependency window.** A zero-day in Laravel/PHP/a package is always
   possible. Defenses: CI dependency audits + Dependabot (§13), prompt patching,
   and defense-in-depth so one broken layer doesn't unzip the rest.
4. **The human factor.** A phished or careless staff member bypasses every code
   control. Defenses: mandatory staff 2FA (§1), least-privilege reviews and
   offboarding session-kill (§10), the append-only audit trail — and telling
   school staff plainly, at onboarding: *no one legitimate will ever ask for your
   password or 2FA code.*

## Approval gates

Changes to **auth, tenancy, finance, or file handling** require a second reviewer
and a passing tenant-isolation test before merge.
