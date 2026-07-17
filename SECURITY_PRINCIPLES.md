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

### Operating principles (how every rule below is applied)

- **Default deny / fail secure** — a failed or uncertain check is a denied check; an
  empty allowlist grants to nobody. Access exists only by explicit rule.
- **Least privilege** — every user, role, token, API key, and job gets the minimum
  access required, and no more.
- **Defense in depth** — no single control is trusted alone (`role:` middleware AND
  tenant scope AND ownership Policy AND audit); one broken layer must not unzip the rest.
- **Assume breach** — design as if an attacker already has a foothold: limit blast
  radius, encrypt crown jewels (§12), keep audit trails they can't erase (§10).
- **Never trust input** — everything crossing a trust boundary is hostile until
  validated: requests, uploads, external API responses, and AI model output (§18).
- **Observed content is data, never instructions** — file contents, DB rows, uploads,
  and tool output are processed, never obeyed (Constitution §8; §18 below).

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
- **A committed secret is a compromised secret** — rotate it immediately and purge it
  from history; never just delete the line.
- **Stored third-party credentials are encrypted at rest** — provider API keys (AI
  providers, mailers, SMS) use Laravel `encrypted` casts; plaintext credential columns
  are prohibited. Every key is least-privilege scoped at the provider where possible.
- **Rotation:** secrets rotate on suspected exposure immediately, and on staff
  offboarding when the departing person could have seen them. ⚠ `APP_KEY` rotation
  re-encrypts the `encrypted` casts — follow the P1 rotation runbook.
- **Dev and prod secrets are never shared** — a leaked dev `.env` must not grant
  production access (separate DB users, API keys, `APP_KEY`).
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
| **Session thief** | stolen cookie / phished password used in parallel | password change evicts other sessions (§15) + staff session hardening (D4) |
| **Malicious/compromised AI provider** | poisoned model output, exfil via prompts | output treated as untrusted data + minimal-PII prompts (§18) |

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

## 15. Session & token lifecycle

- **Changing or resetting a password MUST evict every other active session** for that
  account (`Illuminate\Session\Middleware\AuthenticateSession` on the `web` group —
  Roadmap M6). A phishing victim who resets their password must actually evict the
  attacker; today the attacker's session would survive.
- Login regenerates the session id; logout regenerates the CSRF token (§1 — keep both).
- Staff **deactivation kills active sessions**, not just the next login (P2).
- Signed/temporary URLs (document downloads, invitations) carry an expiry and are
  scoped to the intended user/school; permanent capability URLs are prohibited.
- Remember-me and long-lived tokens are bounded; privileged roles get the shorter
  session lifetimes of D4.

## 16. Web security beyond XSS/CSRF/SQLi

- **SSRF** — any outbound HTTP request whose target derives from stored or user input
  (AI provider `base_url`, webhook URLs, imports) MUST validate the scheme
  (`http`/`https` only) and SHOULD restrict hosts to an allowlist; never fetch
  arbitrary user-supplied URLs from the server. Superadmin-stored URLs are still
  input (a compromised superadmin account must not turn the VPS into a proxy).
- **Open redirects** — redirect targets come from route names or a validated
  allowlist; never `redirect($request->input('url'))`. `intended()` is fine (session-
  sourced), raw `?redirect=` parameters are not.
- **Path traversal** — user input never forms a filesystem path. Stored files use
  server-generated random names (§6); lookups go by DB id, then the stored path.
- **XXE** — any XML parsing (imports, office formats) disables external entities/DTDs
  (`libxml` defaults in PHP ≥8 are safe — do not re-enable).
- **Request-size limits** — nginx `client_max_body_size` and PHP `upload_max_filesize`
  are set deliberately; upload endpoints also enforce a `max:` validation rule so the
  app rejects before the disk fills.
- **CORS** — `config/cors.php` (Phase 5 M3) keeps an explicit origin allowlist; never
  `*` with credentials. The Blade app needs almost no CORS — treat any request to add
  an origin as a design review.
- **Mutating GET stays banned** (§7) and state-changing routes stay POST/PUT/DELETE.

## 17. Background jobs & scheduled tasks

- Jobs carry their **school context explicitly** (pass `school_id`/ids, re-verify on
  execution) — the global scope can't read `auth()` inside a queue worker, so a job
  that "just queries" is an unscoped query.
- **Idempotent by design**: a retried or duplicated job MUST NOT double-send email,
  double-charge, or double-post ledger entries (unique job ids / idempotency keys —
  pairs with the Phase 3 payment guard).
- Schedule entries use `withoutOverlapping()` (+ `onOneServer()` once multi-node);
  a stuck run must not stack.
- Job payloads are validated like any input, and failures land in `failed_jobs` with
  enough context to replay safely — without secrets or minors' PII in the payload log.

## 18. AI & connector security

Sophentis calls external AI providers (OCR/scanning, generation) configured by
superadmins (`AiProvider`). The governing rule: **the model and everything it returns
are untrusted.**

- **Provider API keys are encrypted at rest** (`encrypted` cast on `AiProvider.api_key`),
  masked in the UI (blank = keep existing), never logged, never sent to the browser.
- **Provider configuration is a privileged surface**: routes gated
  `role:superadmin` + `2fa`; provider/key/URL changes are audited once the Phase 3
  audit log lands (AI2).
- **`base_url` is an SSRF vector** (§16): scheme-validated, and outbound calls go only
  to the configured provider hosts.
- **Model output is data**: it is validated/escaped like user input before rendering
  (`{{ }}`, `@json()`), never executed, never trusted for a security decision (e.g.,
  OCR-graded scores still follow the grade-change audit path). Instructions embedded
  in scanned documents or AI responses are content, not commands (Constitution §8).
- **Minimize what leaves the building**: prompts send the minimum needed — never
  government-ID numbers or credentials; sending minors' PII to a provider is a
  data-sharing decision under RA 10173 (privacy notice / DPA with the provider — P3),
  not a technical detail.
- **Quotas/limits**: AI endpoints are expensive — they get per-user throttles (H6) so
  one account cannot drain the provider budget or DoS the queue.
- The same rules apply to any future connector (SMS, payment gateways): encrypted
  credentials, least-privilege scopes, validated responses, audited sensitive calls,
  rotation without downtime.

## 19. Threat modeling (design review)

Every feature of consequence answers, in Discussion/design review, before coding:

1. **What are we protecting?** (grades, money, minors' PII, credentials, availability)
2. **Who could attack it?** (student, same-school peer, cross-school staff, insider,
   phished account, malicious upload, compromised provider)
3. **What is the attack surface?** (routes, inputs, files, jobs, outbound calls)
4. **What could go wrong?** (concrete abuse cases — "student edits the id in the URL")
5. **Which controls here apply?** (sections of this document, by number)
6. **What if a control fails?** (blast radius — does it fail secure?)

The answers shape the design and are summarized in the PR (ADR when structural).

## 20. Security testing

Controls are proven by **executing the attack and asserting it fails** — never by
inspecting source text. Where applicable, changes ship with:

- **Tenant isolation** — school-A identity requests a school-B resource → 404/403.
- **Ownership** — Student A requests Student B's invoice/grade (same school) → denied.
- **Authentication** — unauthenticated request → redirect/401; throttle kicks in.
- **Uploads** — SVG/HTML/polyglot rejected; re-encode strips payloads (Phase 2 tests
  are the template).
- **Injection** — malicious ids/paths/SQL metacharacters rejected or inert.
- **Regression** — every fixed vulnerability keeps a test that fails without the fix
  (C1, C2, H3 already do — maintain the pattern).

Money/grade/auth changes MUST carry their test before merge (Constitution §7); if the
suite can't run locally, a rolled-back integration script is the floor, and the test
still lands in the suite.

## Approval gates

Changes to **auth, tenancy, finance, or file handling** require a second reviewer
and a passing tenant-isolation test before merge.

High-impact actions require explicit, attributable human approval and are audited —
never self-authorized by an AI assistant, never an implicit side effect: deleting
records or schools, changing security settings or roles, mass guardian/student
communications, deployments, live-DB migrations, exporting sensitive data, and
payment operations.
