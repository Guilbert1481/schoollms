# Architecture Principles — Sophentis / schoollms

> How the system is structured. The goal is clear layers, predictable boundaries,
> and tenant safety by construction (not by remembering).

## 1. Layering (dependency direction)

```
Route ──> Controller ──> Service ──> Repository/Query ──> Model ──> DB
                │                                   ▲
                └──> Form Request (validation)      │
                └──> Policy (authorization)         │
              Events ──> Listeners ──> Jobs (async) ┘
```

- **Dependencies point downward only.** A Model never calls a Service; a Service
  never builds an HTTP response.
- **Controllers are the HTTP boundary.** They translate request → service call →
  response. No domain logic, no raw SQL.
- **Services hold business logic** (`app/Services`, e.g. `Finance/InvoiceService`,
  `PaymentScheduleService`, `FormService`). This layer already exists — use it
  consistently. Controllers that bypass it (e.g. `StudentLedgerController` doing
  36 raw `DB::` calls) are debt.
- **Repositories / query objects** isolate persistence and complex queries. We have
  only 2 today (`app/Repositories`); grow this for any query that appears in more
  than one place or uses raw SQL.

## 2. Multi-tenancy is a first-class boundary

This is a **multi-school SaaS**. Tenant isolation is architecture, not an afterthought.

- **Every model with a `school_id` column MUST use the `BelongsToSchool` trait**
  (`app/Models/Traits/BelongsToSchool.php`). The trait adds a global scope that
  filters by the authenticated user's `school_id` and auto-assigns it on create.
- Today only 15 of ~131 models use it. The unscoped finance models (`Invoice`,
  `Payment`, `PaymentPlan`, `LedgerEntry`, `Scholarship`, `PenaltyRule`) are a
  standing risk — see `MODERNIZATION_ROADMAP.md` Phase 1.
- **Object-level checks are still required** for route-model-bound records:
  `abort_unless($model->school_id === auth()->user()->school_id, 404)`
  (canonical pattern: `InvoiceController::authorizeSchool`,
  `LedgerController::drawer`). The global scope and the explicit check are
  belt-and-suspenders — use both.
- Superadmin is the only cross-tenant actor; the trait bypasses scoping for it.
  Keep the superadmin role value canonical and consistent (`superadmin`).

## 3. Authorization

- **Coarse gate:** the `role:` middleware (`CheckRole`) on route groups decides
  *which roles* may reach an area. Canonical role names live in
  `SECURITY_PRINCIPLES.md` / `ACCESS_CONTROL.md`.
- **Fine gate:** **Policies** (`app/Policies`) decide *which records* a user may
  act on. We have only 1 today (`ChatPolicy`). Finance, student records, grades,
  and enrollment need policies.
- The generic master-data CRUD (`BaseCrudController`) is the model to emulate:
  table allowlist + tenant scope + column allowlist.

## 4. Asynchronous work (Events, Listeners, Jobs, Queues)

- **Side effects belong off the request thread.** Emailing guardians, generating
  PDFs, sending notifications must be **Jobs on a queue**, fired via **Events**.
- Today there are **0 Jobs/Events/Listeners**; enrollment PDF + email run
  synchronously inside `EnrollmentController::submit`. This is the canonical thing
  to decouple: `EnrollmentSubmitted` event → `SendEnrollmentPdf` listener → queued job.
- Use queues for anything that can fail, retry, or take >200ms.

## 5. Modularity

- `app/Modules` is our domain-packaging seam (currently only `AcadEnrolment`).
  New self-contained domains (Finance, Admissions, Scheduling) may be packaged as
  modules with their own controllers/services/views.
- A module owns its tables and exposes services; other modules call those services,
  not each other's models directly.

## 6. Separation of concerns (cohesion & coupling)

- One class, one reason to change. The 1,779-line ledger controller violates this;
  the small focused `CheckRole` middleware exemplifies it.
- **Blade ↔ logic:** views never decide business rules.
- **JS ↔ server:** front-end talks to the server through well-defined endpoints,
  not by embedding server state in giant inline scripts.

## 7. Configuration & environment

- Read configuration through `config()`, **never `env()` outside `config/`**
  (we're clean here — only 2 stray calls; keep it that way).
- Secrets live in `.env` (gitignored) and are referenced via config keys.
- MySQL-specific raw SQL (`DB::raw("CONCAT(...)")`) is tolerated in query objects
  but discouraged in controllers; prefer Eloquent for portability and testability.

## 8. API surface (when it grows)

- A real API gets its own `routes/api.php`, Sanctum auth, **API Resources**
  (transformers) — never raw `response()->json($model)`. Today there are 164 raw
  model JSON returns and 0 Resources; new JSON endpoints must use Resources.
