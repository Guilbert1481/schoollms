# Development Workflow — Sophentis / schoollms

> How work moves from idea to production. Lightweight on purpose — enough process
> to stop regressions and silent breakage, not so much that it slows shipping.

## 1. Change sizing — pick the right track

| Size | Examples | Process |
|---|---|---|
| **Trivial** | copy fix, style tweak, add an index | branch → PR → 1 review |
| **Standard** | new CRUD screen, a service method | branch → PR → 1 review + test |
| **Significant** | auth/tenancy/finance change, schema change, new module | **ADR** → branch → PR → 2 reviews + tenant-isolation test |
| **Cross-cutting** | new architecture, big refactor, external integration | **RFC** → design review → phased ADRs |

## 2. RFCs (Request for Comments) — for significant/cross-cutting work

- Write a short `rfc/NNNN-title.md`: problem, options considered, recommendation,
  risks, rollout/rollback.
- Circulate before building. The audits and this roadmap are effectively RFC-0001.

## 3. ADRs (Architecture Decision Records)

- For any decision that shapes structure (e.g. "tenancy via global scope vs manual
  filtering", "single `role` string vs roles table"), record an `adr/NNNN-title.md`:
  context, decision, consequences.
- ADRs are append-only; supersede, don't rewrite. (The `add_role_id` → `drop_role_id`
  flip-flop is exactly what an ADR would have prevented.)

## 4. Branching & PR policy

- **Never commit straight to `main`.** Feature branches → PR. (We have been
  committing large batches to `main` — stop.)
- One concern per PR; small diffs. Reference the roadmap finding ID (e.g. "H4").
- PR description states: what changed, why, how it was verified, rollback plan.
- Required before merge: Pint clean, `php artisan test` green, no `dd()`/scratch files.

## 5. Design reviews

- Significant changes get a 15-minute design review against
  `ARCHITECTURE_PRINCIPLES.md` and `SECURITY_PRINCIPLES.md` **before** coding.
- Checklist: Does it respect layering? Is tenant scoping enforced? Is it tested?
  Any new authz surface?

## 6. Implementation phases (how a feature is built)

1. **Plan** — confirm the approach (and ADR if significant).
2. **Schema** — migration first (additive; never edit a shipped migration).
3. **Domain** — service/model logic with tests.
4. **Edges** — controller + Form Request + Policy.
5. **UI** — Blade/JS using existing components.
6. **Verify** — see §7.

## 7. Testing & verification process

- **Automated:** Feature tests for finance/grade/auth paths (happy path + tenant
  isolation). Unit tests for service logic.
- **Local verification standard** (until coverage grows): `php -l`,
  `php artisan route:list`, and **rolled-back smoke tests**
  (`DB::beginTransaction()/rollBack()`) — never mutate live data during testing.
- **Security fixes** additionally re-run the attacker scenario to prove it's blocked.
- A change that touches money or grades **does not merge without a test.**

## 8. CI (to be added)

- A GitHub Actions workflow runs: `composer install`, Pint check, `php artisan test`,
  (later) Larastan. `.github/` exists but has no workflows yet — Roadmap follow-up.

## 9. Release process

- Releases follow `DEPLOYMENT.md` (GitHub → Contabo VPS). That document is the
  operational source of truth; this section governs *when* we release.
- **Pre-release checklist:**
  - [ ] All targeted roadmap items `[x]` and verified.
  - [ ] `php artisan test` green; Pint clean.
  - [ ] `APP_DEBUG=false`, `APP_ENV=production`, `SESSION_SECURE_COOKIE=true`.
  - [ ] `php artisan config:cache route:cache view:cache` on the server.
  - [ ] `php artisan migrate --force` reviewed (additive only).
  - [ ] Rollback plan noted (previous tag + DB backup).
- Tag releases; keep a short `CHANGELOG`.

## 10. Incident response

- On a suspected breach: rotate `APP_KEY`/session secret, invalidate sessions,
  consult the audit log (Roadmap Phase 3/4) for actor + timeline, patch on a
  hotfix branch, write a postmortem ADR.
