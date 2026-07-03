# ADR-0001 — Multi-tenancy via `BelongsToSchool` global scope + explicit checks

> **Status:** Accepted · **Date:** 2026-07-03 (retroactive) · **Deciders:** Sophentis team

## Context

Sophentis is a multi-school SaaS: one database, many schools, holding minors' PII, grades, and money.
Tenant data leaking across schools is the worst failure mode — and it has happened (a student reached
the admin User Management page; a `TestBuilder` IDOR is on record). Relying on developers to *remember*
a `where('school_id', …)` on every query does not scale to 131 models and 200+ controllers.

## Decision

Enforce tenant isolation in **two layers** ("belt and suspenders"):

1. **Global scope** — every model with a `school_id` column uses the `BelongsToSchool` trait
   (`app/Models/Traits/BelongsToSchool.php`), which filters by the authenticated user's `school_id`
   and auto-assigns it on create.
2. **Explicit object check** — route-model-bound records additionally get
   `abort_unless($record->school_id === auth()->user()->school_id, 404)`
   (canonical pattern: `InvoiceController::authorizeSchool`).

`superadmin` (spelled exactly that way) is the only cross-tenant actor and bypasses the scope.

## Consequences

- Isolation is the **default**, not something to remember; a new tenant model just adds the trait.
- **Known debt:** only ~23 of 131 models use the trait today (~18%). Reaching 100% is a tracked
  [`MODERNIZATION_ROADMAP.md`](../../MODERNIZATION_ROADMAP.md) item; until then, unscoped models rely on
  the explicit check alone — the priority-1 finance models (`Invoice`, `Payment`, `LedgerEntry`, …) are
  already covered.
- Any query that legitimately needs cross-tenant data must enter superadmin context deliberately.
- A change to auth or tenancy requires a second reviewer and a tenant-isolation test (see
  [`SECURITY_PRINCIPLES.md`](../../SECURITY_PRINCIPLES.md)).
