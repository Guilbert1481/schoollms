# Codebase Audit — Sophentis / schoollms

> A senior-engineer read-through: *"Would an experienced engineer opening this repo conclude it was
> built by a professional team?"* Point-in-time assessment against 9 signals of professional code,
> with a prioritized remediation plan. **Status:** v1.0 · **Date:** 2026-07-03 · **Method:** read-only
> measurement (file/LOC counts, `grep` pattern counts, `git` history, targeted code reads) — no code
> changed during the audit.
>
> This is a **snapshot that feeds** [`MODERNIZATION_ROADMAP.md`](./MODERNIZATION_ROADMAP.md) (the
> authoritative backlog); it does not replace it. Grades are one reviewer's judgment on this date.

---

## Verdict

**A genuinely talented developer who *knows* what good looks like — the domain modeling, naming,
service layer, and governance docs prove it — but who is shipping faster than the codebase is being
hardened.** It reads as *"promising and thoughtfully intended,"* not yet *"engineered by an
experienced team."* The three tells a senior scans for first — **tests, enforced consistency, and a
clean tree** — were the weakest. The good news: those are the *cheapest* gaps to close, and the
codebase is on a visible upward trajectory (the priority-1 finance models are now tenant-scoped).

---

## Scorecard

| # | Signal | Grade | Evidence (measured 2026-07-03) |
|---|---|---|---|
| 1 | Consistency / coherence | **C+** | Strong shared patterns exist (`BaseCrudController`, `BelongsToSchool`, `CheckRole`), but **two competing styles**: 24 services vs **203 raw `DB::` calls in controllers** |
| 2 | Clean architecture / seams | **C−** | Layering is well-defined but leaky — only **2 repositories**; god-controllers do persistence, HTTP, and domain logic together |
| 3 | Naming / vocabulary | **B** | A real strength — `StudentLedger`, `TranscriptOfRecord`, `TuitionSetup`, `Curriculum`; descriptive commit subjects |
| 4 | Right abstractions | **C−** | Under-abstracted: **1 Form Request** app-wide, **0 API Resources** for **165 raw `->json()`** returns (across 45 controllers), `step7_review.blade` duplicated **×4** |
| 5 | Unhappy path / errors | **C−** | **0 Jobs / 0 Events / 0 Listeners** — side-effects (enrollment PDF + email) run synchronously in-request; raw `DB::` everywhere; tenancy gaps (a `TestBuilder` IDOR is cited in `SECURITY_PRINCIPLES.md`) |
| 6 | Tests as spec | **F** | **4 test files / 6 test methods** for 25,604 LOC of controllers + 131 models — including *money and grades* |
| 7 | No litter / rot | **C** *(was D; Tier 0 fixed)* | `.gitignore` already covered most junk; the remaining leaks (`tmp_*.php`, `download_test.bin`, empty `php`) are now addressed — see Remediation |
| 8 | The "why" captured | **D** | **No `adr/` or `rfc/` directory** despite `DEVELOPMENT_WORKFLOW.md` prescribing them; decisions unrecorded (the doc itself cites a `role_id` flip-flop an ADR would have prevented) |
| 9 | Professional history | **C** | 66 commits with genuinely good messages ✅ — but committed **directly to `main`**, and commits bundle **multiple concerns** (e.g. "sidebar reorg, drop Billing Queue, Billing→Invoices, Tuition rebuild" = 4 concerns, 1 commit) |

**The killer trio:** rows 6, 7, 8 — near-zero tests, no mechanical enforcement, and unrecorded decisions. Those are exactly what a senior checks first.

---

## Metrics snapshot

| Area | Count |
|---|---|
| Controllers / total controller LOC | **204 / 25,604** |
| Largest controllers | `StudentLedgerController` **1,557**, `EnrollmentController` **1,440**, `LedgerController` 848, `DriveController` 725, `TuitionSetupController` 695, `TranscriptOfRecordController` 604 |
| Models | 131 (**~23 tenant-scoped via `BelongsToSchool` ≈ 18%**) |
| Services / Repositories | 24 / **2** |
| Policies / Jobs / Events / Listeners | **1 / 0 / 0 / 0** |
| Form Requests / API Resources | **1 / 0** |
| Middleware / Modules / Migrations | 13 / 18 / 353 |
| Tests (files / methods) | **4 / 6** |
| Raw `DB::` in controllers | **203** |
| Raw `->json()` returns | **165** across 45 controllers |
| Raw Blade output `{!! !!}` | **31** across 17 views |
| `env()` outside `config/` | **0** ✅ |
| `dd()` / `dump()` / `ray()` in `app/` | **0** ✅ |

---

## What already reads professional (credit where due)

- **Domain modeling and naming** — the hardest thing to fake, and it's good.
- **The instincts are all present**: a service layer, a tenant-isolation trait, role middleware, a
  generic CRUD base, 18 module files, 353 migrations — and, unusually, an **excellent written
  governance set**. `SECURITY_PRINCIPLES.md` naming its own IDOR incident is senior behavior.
- **Two config-hygiene rules are actually honored**: `env()` is never called outside `config/`, and
  there is **no** `dd()`/`dump()`/`ray()` left in `app/`.
- **The roadmap is being executed** — the finance models (`Invoice`, `Payment`, `LedgerEntry`,
  `PaymentPlan`, `Scholarship`, `PenaltyRule`) now carry `BelongsToSchool`; that was the #1 risk.

## What reads "solo, fast, un-hardened"

- 25,604 LOC across 204 controllers, with a **1,557-line** `StudentLedgerController` and **203** raw
  `DB::` calls sitting in controllers.
- **~18% tenant coverage** — the majority of models still rely on remembering the manual check.
- **Near-zero tests, no CI, no formatter/static-analysis enforcement** → nothing stopped the next
  change from drifting (running Pint `--test` on 2026-07-03 reported the *whole tree* drifts from the
  Laravel preset).
- Decisions aren't recorded (no ADRs), and history is direct-to-`main` with multi-concern commits.

---

## Remediation plan (cheapest / highest-perception-jump first)

### Tier 0 — hygiene + enforcement floor · **✅ DONE 2026-07-03**
- **`.gitignore`** tightened (`tmp_*.php`, `download_test.bin`) — scratch files no longer leak.
- **`pint.json`** (Laravel preset) added — the style standard is now explicit.
- **`.github/workflows/ci.yml`** added — a **tests** job (PHP 8.2, builds Vite assets, runs
  `composer test` against sqlite `:memory:` per `phpunit.xml`) and a **Pint** job (non-blocking for
  now — see follow-ups).

**Operator follow-ups to finish Tier 0:**
1. `git rm --cached download_test.bin php` — untrack the 2 committed junk files (kept on disk).
2. `vendor/bin/pint` as its **own dedicated commit** (formats the whole tree), then delete
   `continue-on-error: true` in `ci.yml` so code style becomes a **blocking** check.
3. Commit the Tier 0 files on a `chore/ci-and-hygiene` branch (per "never straight to `main`") and push
   so CI runs for the first time.

### Tier 1 — days
4. **Larastan** at a low baseline level → a structural safety net you ratchet upward.
5. **Start the ADR habit** — even retroactively record the big calls (tenancy-via-global-scope,
   role-as-string) under `docs/adr/`. Makes the "why" legible.
6. **Stop committing to `main`** — branch → PR → self-review, one concern per PR.

### Tier 2 — ongoing (tracked in `MODERNIZATION_ROADMAP.md`)
7. **Backfill tests** on money / grade / auth (happy path + tenant isolation) — enforce the codebase's
   own "no finance/grade change without a test" rule.
8. **Strangle the god-controllers** — extract to services one method at a time, whenever you touch one.
9. **Close the real XSS surface** — `components/table/table-body.blade.php` renders arbitrary cell
   values raw; `admin/quotes/index.blade.php` puts model JSON in a `<script>` → use `@json()`.
10. **Grow the missing seams** — a Form Request per write action, an API Resource per JSON endpoint,
    Policies for finance/students/grades/enrollment, Jobs/Events for off-thread side-effects.

---

## Bottom line

This is not a talent or taste problem — the docs and naming prove that. It is an **enforcement,
hygiene, and testing problem**, and **Tier 0 (now in place) already moves a senior's first impression
from "vibe-coded" toward "professionally maintained"** by making the tree clean and giving the
excellent governance docs their first mechanical teeth. Tier 1 makes it *stay* that way; Tier 2 is the
real depth, and it already lives in the roadmap.

*Re-audit suggestion: revisit this scorecard each time a `MODERNIZATION_ROADMAP` phase closes.*

---

*Companion to the governance set: [`ENGINEERING_CONSTITUTION.md`](./ENGINEERING_CONSTITUTION.md) ·
[`ENGINEERING_PRINCIPLES.md`](./ENGINEERING_PRINCIPLES.md) ·
[`ARCHITECTURE_PRINCIPLES.md`](./ARCHITECTURE_PRINCIPLES.md) ·
[`SECURITY_PRINCIPLES.md`](./SECURITY_PRINCIPLES.md) ·
[`DEVELOPMENT_WORKFLOW.md`](./DEVELOPMENT_WORKFLOW.md) ·
[`CONTINUOUS_MODERNIZATION.md`](./CONTINUOUS_MODERNIZATION.md) ·
[`MODERNIZATION_ROADMAP.md`](./MODERNIZATION_ROADMAP.md).*
