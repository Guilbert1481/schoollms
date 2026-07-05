# ADR-0004 — CLAUDE_OPERATIONAL_GUIDELINES.md is a mandatory governance document

> **Status:** Accepted · **Date:** 2026-07-05 · **Deciders:** Operator (Jabhy)

## Context

AI-assisted engineering sessions in this repository were consuming 100k–300k tokens per command,
versus under 20k for comparable work in a sibling project. The dominant cause was operational, not
engineering: multi-agent recon fan-outs (3–5 parallel subagents, each re-reading the repository from
zero, 130k–230k tokens per fan-out) used as the default for routine questions, plus long-lived
conversation context re-billed on every call. Quality did not require this — the same answers come
from targeted searches and scoped reads in the primary session. The sibling project (Gideon) already
governs this with a `CLAUDE_OPERATIONAL_GUIDELINES.md`; Sophentis had no equivalent standard.

## Decision

- **`CLAUDE_OPERATIONAL_GUIDELINES.md` is created at the repo root** — a binding standard for how AI
  assistants operate here: minimal-context reading rules, subagent governance, an explicit
  **multi-agent fan-out discipline** (§4.4: fan-outs are never the default; conclusions-only
  deliverables; ~20–40k token expectation for routine commands), MCP discipline, `/compact`
  guidance, and mandatory pre-flight / post-implementation checklists.
- **`ENGINEERING_CONSTITUTION.md` is amended to v1.1**: the new document joins the governance set
  (§5, item 6), the precedence order (§6, deliberately **last** — efficiency never overrides
  security, tenancy, architecture, or workflow), the AI collaboration policy (§8, new MUST bullet),
  and the per-session reading protocol (§14).

## Consequences

- Routine AI commands are expected to run at a fraction of prior cost with unchanged engineering
  quality; crossing ~100k tokens on a routine task is now a defined stop-and-simplify signal.
- Multi-agent fan-outs remain available where they genuinely pay: exhaustive security sweeps and
  adversarial verification of money/grades/tenancy changes before a checkpoint.
- The document is written portable, so other projects can adopt it with only examples changed.
