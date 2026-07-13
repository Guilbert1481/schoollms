# Architecture Decision Records (ADRs)

Short, append-only records of decisions that shape Sophentis's structure. Per
[`DEVELOPMENT_WORKFLOW.md`](../../DEVELOPMENT_WORKFLOW.md) §3, any decision that shapes structure —
tenancy, the auth model, module boundaries, schema strategy — gets an ADR here.

**Rules**
- One decision per file: `NNNN-short-title.md` (copy [`0000-adr-template.md`](0000-adr-template.md)).
- **Append-only.** To change a decision, write a *new* ADR that supersedes the old one — never rewrite history.
- Status lifecycle: `Proposed` → `Accepted` → (`Superseded by ADR-XXXX` | `Deprecated`).

## Index

| ADR | Title | Status |
|---|---|---|
| [0001](0001-multi-tenancy-via-belongs-to-school.md) | Multi-tenancy via `BelongsToSchool` global scope + explicit checks | Accepted |
| [0002](0002-roles-as-string-with-checkrole.md) | Roles as a canonical string + `CheckRole` middleware | Accepted |
| [0003](0003-service-layer-and-thin-controllers.md) | Business logic in services; controllers are a thin HTTP boundary | Accepted |
| [0004](0004-claude-operational-guidelines.md) | Ratify `CLAUDE_OPERATIONAL_GUIDELINES.md` into the governance set | Accepted |
| [0005](0005-frontend-stack-react-tailwind-vite.md) | Standardize the frontend on React + Tailwind + Vite (islands, not a big-bang SPA) | Accepted |
| [0006](0006-year-level-ui-academic-levels-backend.md) | "Year Level" in the UI; `academic_levels` stays as seeded backend plumbing | Accepted |
| [0007](0007-host-based-tenant-resolution.md) | Host-based tenant resolution (subdomains + custom domains) | Accepted |

> ADRs 0001–0003 are **retroactive** — they record decisions already embodied in the code so the "why"
> is legible. Written 2026-07-03 as part of Tier 1 hardening (see [`CODEBASE_AUDIT.md`](../../CODEBASE_AUDIT.md)).
> ADR-0005 (2026-07-09) is **forward-looking** — it sets the target frontend stack for new UI.
