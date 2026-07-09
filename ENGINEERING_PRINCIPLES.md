# Engineering Principles — Sophentis / schoollms

> How we write code here. These rules exist to keep a large, fast-moving Laravel
> codebase maintainable by a team. They are enforced in code review (see
> `DEVELOPMENT_WORKFLOW.md`). When a rule conflicts with shipping, raise it in the
> PR — don't silently break it.

## 1. Coding philosophy

- **Boring, explicit, consistent beats clever.** A new engineer should be able to
  predict where code lives and how it behaves.
- **Follow the framework.** Use Laravel's conventions (Eloquent, Form Requests,
  Policies, Events, Jobs) instead of re-inventing them. If you're writing
  `DB::table()` in a controller, stop and ask why.
- **Match the surrounding code.** Naming, structure, and comment density should
  look like the file you're editing.

## 2. Thin controllers

A controller method should: validate input → call a service → return a response.
That's it.

- **No business logic in controllers.** Move it to `app/Services`.
- **No raw query building in controllers.** Move it to a service or a query/repository object.
- **Ceiling: ~150 lines per controller, ~30 lines per action.** Existing offenders
  (`StudentLedgerController` ~1,779 LOC, `EnrollmentController` ~1,454 LOC) are
  technical debt to be decomposed, not a pattern to copy.

## 3. Validation

- **Every write action validates input.** Prefer **Form Requests**
  (`app/Http/Requests`) over inline `$request->validate()` once a rule set is
  reused or non-trivial.
- **Touch-rule (Q3, adopted 2026-07-09):** every **new or edited** endpoint ships
  with a dedicated Form Request. No big-bang rewrite of the ~97 existing
  inline-validate controllers — they convert as they're touched.
- Never trust `request()->all()` into `fill()`/`create()`/`update()`. Models must
  declare `$fillable` (we do — keep it that way); the generic CRUD additionally
  enforces a column allowlist (`BaseCrudController::$protectedColumns`).

## 4. No business logic in Blade

- Blade renders data; it does not compute it. No DB queries, no money math, no
  authorization decisions inside `.blade.php`.
- Output is escaped by default (`{{ }}`). `{!! !!}` is **forbidden for any
  value that could contain user input** — see `SECURITY_PRINCIPLES.md`.

## 5. File-size ceilings (red flags, not hard fails)

| Artifact | Soft ceiling | Action if exceeded |
|---|---|---|
| Controller | 150 LOC | extract to service |
| Model | 250 LOC | extract scopes/concerns |
| Blade view | 300 LOC | split into partials/components |
| JS file | 400 LOC | modularize (the 11k-line `public/js/app.js` is the cautionary tale) |

## 6. Reuse over duplication

- Before adding a Blade page, check for an existing component/partial
  (`resources/views/components`, `@include` partials).
- Duplicated flows (e.g. the per-track `step7_review.blade.php` files vs the
  unified `acad_enrolment/shared/review.blade.php`) get **consolidated, then the
  dead copy deleted** — not left to rot.

## 7. Testing discipline

- **Money and grades are non-negotiable.** Any change to finance (invoices, payment
  plans, ledgers, SOA, payments) or grades requires a Feature test that exercises
  the happy path **and** a tenant-isolation case.
- New bug fix → a regression test that fails before the fix and passes after.
- We are starting from ~zero real tests. The standard going forward: **no new
  finance/grade/auth logic merges without a test.**
- **Factories are the foundation (Q2):** every core model (`School`, `User`,
  `Student`, `Invoice`, `Payment`, `StudentEnrollment`, …) gets a model factory so
  writing a new test takes minutes, not hours. A test that needs a model without a
  factory adds the factory first.
- Verification during development uses rolled-back smoke tests
  (`DB::beginTransaction()/rollBack()`) so we never mutate live data.

## 8. Repository hygiene

- **No scratch files in the repo.** `tmp_*.php`, `diag_*.php`, `*.sql` dumps,
  `cookies.txt` belong in your scratchpad, never the working tree.
- Run **Laravel Pint** before committing (`./vendor/bin/pint`). Formatting is not
  a matter of taste here.
- Keep commits small and single-purpose. End commit messages with the
  `Co-Authored-By` trailer per repo convention.

## 9. Code ownership

- Code is owned by the team, not the author. Anyone may fix anything, but
  **non-trivial changes to finance, auth, or tenancy require a second reviewer.**
- Leave the campsite cleaner: if you touch a god-controller, extract at least the
  method you came for into a service.

## 10. Anti-vibe-coding rules (hard stops)

1. No copy-paste of a controller/view to make a variant — extract the shared part.
2. No new global helper for a one-off — put it where it belongs.
3. No magic numbers/strings — use config, class constants (e.g. `Payment::TYPES`),
   or better, **backed PHP enums** for domain statuses (enrollment status,
   `billing_cleared_as`, upload/student types, payment states). One source of truth
   ends server↔client list drift — the `UPLOAD_TYPES` bug (server allow-list and a
   hardcoded JS copy drifted apart) is the exact failure this prevents. Touch-rule
   like Q3: adopt on new/edited status fields (Q4).
4. No `dd()`/`dump()`/`ray()`/`console.log` left in committed code.
5. No "I'll add the test later." Later is now.
6. **No half-finished migrations (Q1).** When replacing a system (CSS pipeline,
   build tool, layout, component), finish the migration or revert it — two systems
   doing one job (Tailwind CDN + compiled, Mix + Vite, dormant React files) is the
   loudest vibe-coder tell in a codebase. Dead code is deleted, not left "just in case".
