# ADR-0002 — Roles as a canonical string + `CheckRole` middleware

> **Status:** Accepted · **Date:** 2026-07-03 (retroactive) · **Deciders:** Sophentis team

## Context

We need role-based access across many staff / teacher / student route groups. An earlier attempt to
model roles via a `role_id` column was added and then dropped (`add_role_id` → `drop_role_id`) — churn
that an ADR would have prevented. We want coarse, predictable gating without premature RBAC machinery.

## Decision

- A user's role is a **single canonical snake_case string** (e.g. `registrar`, `finance_manager`,
  `teacher`, `superadmin`). The authoritative list lives in
  [`SECURITY_PRINCIPLES.md`](../../SECURITY_PRINCIPLES.md).
- The **`role:` middleware (`CheckRole`)** gates route groups — the coarse "which roles may reach this
  area" decision. It normalizes case / spaces / hyphens. `superadmin` is exactly `superadmin`
  everywhere (never `super_admin`).
- Record-level ("which records may this user act on") decisions belong to **Policies**, not the role
  string.

## Consequences

- Simple, readable, and stable — no join is needed to know a user's role.
- No per-permission granularity: when a role needs finer control, add a **Policy** — do not overload the
  role string or reintroduce a `role_id` table without a superseding ADR.
- Role strings are a **stable vocabulary**; renaming one is a coordinated data + code migration.
