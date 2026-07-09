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
- **Tenant coverage:** ~~unscoped finance models~~ *resolved 2026-06-30 (roadmap H4 — `Invoice`, `Payment`,
  `PaymentPlan`, `LedgerEntry`, `Scholarship`, `PenaltyRule` + 3 more now use `BelongsToSchool`)*; remaining
  models to be scoped opportunistically as they're touched.
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

## 7. Engineering-quality backlog (Q1–Q8)

> Added 2026-07-09 from the operator's goal review: beyond *secure* (the roadmap's security phases),
> the codebase must read as **senior-architect work, not vibe-coded**. These are quality items, not
> vulnerabilities — **security Phases 2/2.5 keep priority**; Q items interleave per the notes below.
> Baseline measured 2026-07-09: 12 test files · 2 factories · 1 FormRequest vs 97 controllers with
> inline `validate()` · 0 PHP enums · no dev strict-mode guards · 476 Blade views · 366 migrations.
> Already senior-grade (keep it that way): `decimal(12,2)` money, FKs in newer migrations, governance
> docs + ADRs, CI vs real MySQL, Larastan + Pint, verification evidence on every closed roadmap item.

- [ ] **Q1** Finish half-done migrations, delete dead code *(loudest "vibe-coder" tell; cheapest fix)* —
  complete the Tailwind CDN→Vite migration (10/16 pages remain), move the superadmin layout off Laravel
  Mix, delete dormant `resources/js/app.jsx` + `components/TeacherDashboard.jsx` and the orphaned
  `layouts/academics`, remove tracked junk (ties to roadmap L2), trim the over-broad Tailwind safelist
  (880 KB compiled CSS).
- [ ] **Q2** Test foundation *(the big rock)* — factories for every core model (`School`, `Student`/`User`,
  `Invoice`, `Payment`, `StudentEnrollment`, …) so a new test takes minutes, then feature tests over the
  existing money/grade paths. Standing rule: every bug fix ships with its regression test.
- [ ] **Q3** FormRequests as a touch-rule — every **new or edited** endpoint gets a dedicated FormRequest
  (no big-bang rewrite of the 97 inline-validate controllers).
- [ ] **Q4** Backed PHP enums for domain statuses — enrollment status, `billing_cleared_as`, upload/student
  types, payment states. One source of truth ends server↔client list drift (the `UPLOAD_TYPES` bug class).
  Adopt as a touch-rule like Q3.
- [ ] **Q5** Dev strict mode *(one-liner)* — `Model::shouldBeStrict()` (lazy-loading, silently-discarded
  attributes) in `AppServiceProvider` for non-production; every hidden N+1 becomes a loud dev exception.
- [ ] **Q6** Reproducible onboarding — README setup that takes a new machine from `git clone` to a working,
  demo-school-seeded app in ≤15 minutes (setup steps + seeders; builds on Q2's factories).
- [ ] **Q7** Release discipline on GitHub — branch protection on `main`, a PR template with the house
  checklist (tests / tenant scoping / Pint / finding ID), `CHANGELOG.md`.
- [ ] **Q8** Structured logging with tenant context — `school_id` + `user_id` on every log line
  (pairs with roadmap Phases 3–4 audit work).

**Sequencing:** Q1 + Q5 are cheap and interleave with security phases anytime; Q2 + Q6 form their own
workstream (and de-risk everything after them); Q3 / Q4 / Q8 are incremental house rules, enforced in
review from now on rather than executed as one project.

---

*Living document. The authoritative plan is [`MODERNIZATION_ROADMAP.md`](./MODERNIZATION_ROADMAP.md);
this file governs the discipline by which it is executed.*

**Amendment history:** v1.0 (2026-07-03) — initial ratification; policy layer over the existing roadmap/progress docs (which are retained unchanged). v1.1 (2026-07-09) — §7 engineering-quality backlog (Q1–Q8) added; §2 tenant-coverage headline updated to reflect roadmap H4 completion.
