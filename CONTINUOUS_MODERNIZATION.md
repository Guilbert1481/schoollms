# Continuous Modernization — Sophentis / schoollms

> How Sophentis pays down debt and evolves **without breaking a live, multi-school system.** This is
> the *policy*; the *plan* is [`MODERNIZATION_ROADMAP.md`](./MODERNIZATION_ROADMAP.md) — the
> authoritative, phased backlog, resolved one item at a time — with progress tracked in
> [`MODERNIZATION_PROGRESS.md`](./MODERNIZATION_PROGRESS.md). **This document does not replace the
> roadmap; it governs how the roadmap is executed.** Status: Active · v1.0 · 2026-07-03.

## 1. Evolution over revolution

- **No big-bang rewrites.** Debt is retired in small, behavior-preserving steps, each shippable and reversible.
- **`main` always deployable.** Each step passes `php artisan test` + Pint and leaves the app working.
- Prefer the **strangler pattern** for god-code: route new/extracted logic through a service while the
  old path shrinks — never rewrite `StudentLedgerController` (~1,779 LOC) or `EnrollmentController`
  (~1,454 LOC) in a single PR.

## 2. The debt register

The roadmap **is** the debt register. Findings carry IDs (e.g. "H4"); PRs reference them. New debt
discovered mid-work is *added to the roadmap*, not silently fixed in an unrelated PR. Headline debt
(cross-referenced from the architecture/engineering/security principles):

- **God-controllers** doing raw `DB::` calls → decompose into `app/Services`.
- **Tenant coverage:** only ~15/131 models use `BelongsToSchool`; the unscoped finance models
  (`Invoice`, `Payment`, `PaymentPlan`, `LedgerEntry`, `Scholarship`, `PenaltyRule`) are priority-1 risk.
- **Missing seams:** Policies (1 today), Jobs/Events/Listeners (0 — enrollment PDF/email run
  synchronously), API Resources (0; ~164 raw model-JSON returns).
- **Front-end:** the ~11k-line `public/js/app.js`; duplicated per-track Blade review steps; `{!! !!}` XSS offenders.

## 3. How a modernization item is executed

1. Pick the next roadmap item — **respect its phase order** (security/tenancy first).
2. If it's structural, record an **ADR** (per `DEVELOPMENT_WORKFLOW.md` §3).
3. **Characterization test first** for finance/grade/auth paths — pin current behavior, then refactor under it.
4. **One concern per PR**, small diff, referencing the finding ID.
5. Verify with rolled-back smoke tests (`DB::beginTransaction()/rollBack()`); for security items, re-run the attacker scenario to prove it's blocked.
6. Mark the item done in the roadmap/progress; update the ADR if the decision changed.

## 4. Refactoring rules

- Behavior-preserving steps only; **no feature change mixed into a refactor PR.**
- "Leave the campsite cleaner" — extract at least the method you came for.
- Delete dead code once its replacement is in (don't leave both copies to rot).
- **Never edit a shipped migration**; correct with a new additive one.

## 5. Dependency & framework modernization

- **One category per PR** — dependency bumps stay separate from features/refactors; commit lockfiles (`composer.lock`, `package-lock.json`).
- Keep **Laravel 12 / PHP 8.2** patched; read the upgrade guide before a major bump; run the full suite after.
- Front-end (Vite/Tailwind) upgrades isolated and smoke-tested.
- Add static analysis (**Larastan**) and **CI** (GitHub Actions: install → Pint → `php artisan test`)
  as roadmap follow-ups — raise the bar incrementally; never block all work on a big-bang cleanup.

## 6. Contract & schema evolution

- **Additive migrations**; deprecate before removing a column/route that holds data.
- When extracting a domain into `app/Modules`, other code calls its **services**, not its models —
  evolve that seam deliberately, with an ADR.

---

*Living document. The authoritative plan is [`MODERNIZATION_ROADMAP.md`](./MODERNIZATION_ROADMAP.md);
this file governs the discipline by which it is executed.*

**Amendment history:** v1.0 (2026-07-03) — initial ratification; policy layer over the existing roadmap/progress docs (which are retained unchanged).
