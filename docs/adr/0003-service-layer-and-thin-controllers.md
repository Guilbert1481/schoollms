# ADR-0003 — Business logic in services; controllers are a thin HTTP boundary

> **Status:** Accepted · **Date:** 2026-07-03 (retroactive) · **Deciders:** Sophentis team

## Context

Some controllers grew into "god" classes doing HTTP handling, raw persistence, and domain logic at once
(e.g. `StudentLedgerController` ~1,557 LOC; ~203 raw `DB::` calls sit in controllers today). This
couples concerns, blocks testing, and makes behavior hard to find or reuse.

## Decision

- **Controllers are the HTTP boundary only:** validate (Form Request) → call a service → return a
  response. No domain logic, no raw SQL.
- **Business logic lives in `app/Services`** (e.g. `Finance/InvoiceService`, `PaymentScheduleService`).
- **Persistence and complex queries** move to repositories / query objects; raw `DB::` does not belong
  in a controller.
- Self-contained domains may be packaged under `app/Modules`, owning their tables and exposing services;
  other modules call those services, not each other's models.

## Consequences

- Logic becomes testable and reusable across HTTP, queued jobs, and console commands.
- **Known debt:** existing god-controllers are decomposed **incrementally** (strangler pattern, per
  [`CONTINUOUS_MODERNIZATION.md`](../../CONTINUOUS_MODERNIZATION.md)) — never rewritten in one PR.
- New controllers that bypass the service layer, or add raw `DB::` calls, are rejected in review.
