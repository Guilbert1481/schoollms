# ADR-0008 — Production-stack checklist + security-principles uplift (Argo parity)

> **Status:** Accepted · **Date:** 2026-07-17 · **Deciders:** Operator (Jabhy)

## Context

The sibling Argo platform's governance advanced past Sophentis's: its Constitution v1.3 ratified a
13-layer `full_production_stack.md` pre-flight checklist, and its `SECURITY_PRINCIPLES.md` covers
threat classes Sophentis's leaner security doc did not — AI/connector security, session-lifecycle
eviction, SSRF/open-redirect/path-traversal/XXE, background-job idempotency, secrets rotation, a
threat-model questionnaire, and an attempted-violation test catalog. The gap became acute when the
in-flight AI-provider feature (superadmin-managed API keys, outbound base URLs) landed in the
working tree with no governing security rules, and Sophentis still has no home for platform-layer
concerns (rate limiting beyond login, caching tenancy rules, uptime, backups dead-man alerting, DR).

## Decision

- **`FULL_PRODUCTION_STACK.md` is created at the repo root** — the 13 production layers translated
  to the Sophentis stack (Laravel 12/Blade/MySQL/nginx VPS), each with binding implementation rules
  and an honest current-status line cross-referenced to `MODERNIZATION_ROADMAP.md`.
- **`ENGINEERING_CONSTITUTION.md` is amended to v1.2**: new §11A makes the checklist a mandatory
  pre-flight gate before creating or editing any file; §14 reading protocol gains the matching
  bullet. Precedence is unchanged — the checklist *indexes* `SECURITY_PRINCIPLES.md` for its
  security layer, never overrides it.
- **`SECURITY_PRINCIPLES.md` is extended** with the missing sections (operating principles, session
  & token lifecycle, secrets management, extended web security, background-job security, AI &
  connector security, threat modeling, security testing), keeping its practical Laravel voice.
- **`MODERNIZATION_ROADMAP.md` absorbs the new findings** (AI1–AI3, M6, H6, D5, S5, P5) so no
  security work exists outside the tracker.

## Consequences

- Every change now has a defined pre-flight against all 13 layers; degrading a layer is a defect by
  definition, matching Argo's discipline.
- The AI-provider surface is governed from its first release (encrypted keys, superadmin+2FA-gated
  routes, outbound-URL rules) instead of retrofitted.
- The roadmap gains a handful of items but no new parallel plan — the single-tracker rule holds.
- Doc-only change apart from the two smallest gated code items (session eviction on password change,
  AI-provider base-URL validation), which follow the normal auth-change review rules.
