# Claude Operational Guidelines — Sophentis / schoollms

> **Status:** Active — binding standard
> **Version:** 1.0
> **Last updated:** 2026-07-05
> **Audience:** Claude Code and every AI coding assistant operating inside this repository; also informative for the operator supervising AI-assisted work.
> **Authority:** This is a governance document, not user documentation. It defines **how the AI assistant itself must operate** — how it reads, reasons, delegates, and consumes computation — while working in this repository. It is subordinate to [`ENGINEERING_CONSTITUTION.md`](./ENGINEERING_CONSTITUTION.md) and sits alongside the other mandatory governance documents. It is written to be **portable**: it may be adopted by other repositories with only its examples changed.

**Normative language.** **MUST**, **MUST NOT**, **SHOULD**, **SHOULD NOT**, and **MAY** follow RFC 2119. "MUST" rules are non-negotiable for the AI assistant; deviating requires an explicit, documented reason stated to the operator at the time of deviation.

**Precedence note.** Nothing here authorizes sacrificing correctness, security, tenant isolation, architecture, or maintainability to save computation. Where efficiency and quality conflict, **quality wins** and the Constitution's precedence order (§6) applies — this document ranks last by design. Efficiency governs *how* quality is achieved — never *whether* it is achieved.

---

## 1. Mission

The objective of this standard is to **maximize engineering quality while minimizing unnecessary AI computation** — specifically:

- **Token consumption** — input and output tokens spent per task.
- **Context growth** — the accumulation of file contents, tool outputs, and repeated analysis in the conversation window.
- **Duplicate work** — re-reading, re-scanning, re-analyzing, or re-deriving what is already known.
- **Latency** — wall-clock time the operator waits for results.
- **Operational cost** — the monetary cost of model calls, subagents, and tool usage.

The governing rule:

> **The AI assistant MUST always seek the most efficient path to the correct result — and MUST NEVER trade away correctness, maintainability, architecture, tenant safety, or security to obtain efficiency.**

Efficiency and quality are not in tension when work is disciplined: understanding the problem before acting, reading only what is needed, and reusing what is already known produce *both* better engineering and lower cost. Waste is almost always a symptom of an undisciplined process, not a price of quality.

---

## 2. General Operating Principles

The assistant **MUST** operate by the following principles at all times:

1. **Think before acting.** Form a plan before invoking tools. A minute of reasoning is cheaper than ten exploratory tool calls.
2. **Read before writing.** Never modify a file whose current content and role are not understood.
3. **Never assume.** If behavior, structure, or intent is uncertain, verify it — in the smallest way possible.
4. **Prefer evidence over assumptions.** Claims about the codebase are grounded in what was actually read or executed, not in what is "usually" true.
5. **Reuse existing context.** Information already in the conversation — file contents, prior analysis, prior tool output — is used, not re-fetched.
6. **Minimize AI calls.** Every subagent spawn, model call, and tool invocation must earn its cost.
7. **Minimize repository scanning.** Targeted retrieval over broad exploration (see §3).
8. **Avoid unnecessary complexity.** In both engineering output and operational strategy, the simplest sufficient approach wins.
9. **Preserve engineering quality.** No efficiency measure may reduce correctness, security, tenant isolation, test honesty, or architectural integrity.
10. **Avoid duplicate analysis.** An artifact analyzed once is not analyzed again unless it has changed.
11. **Avoid repeated reasoning.** Conclusions already reached and stated are referenced, not re-derived.
12. **Never redo completed work unless something has changed.** A changed file, requirement, or decision justifies re-work; nothing else does.

> **Note.** These principles extend the values in [`ENGINEERING_PRINCIPLES.md`](./ENGINEERING_PRINCIPLES.md): *every line of code is future maintenance cost* — and every token spent is present operational cost. The best tool call is often the one that never needed to be made.

---

## 3. Repository Context Management

### 3.1 Reading rules

When retrieving information from the repository, the assistant **MUST**:

- **Read only necessary files** — those directly implicated by the task.
- **Read the smallest possible scope** — a method or line range in preference to a whole file; a whole file in preference to a directory.
- **Prefer targeted searches** — symbol/route/column/keyword searches (grep by `function name`, route name, table column) that return locations, over sequential reading to "find" something.
- **Avoid scanning the entire repository.** Full traversal is exceptional (§3.2), never a default.

The assistant **MUST NOT** read, except when the task explicitly concerns them:

- **`vendor/` and `node_modules/`** — dependency trees (the repo's largest directories).
- **Generated artifacts** — `public/build/`, `bootstrap/cache/`, `storage/framework/` (compiled Blade views), coverage output.
- **Data dumps** — `lms_db.sql`, `db_backups/`, `storage/app/` uploads.
- **`database/schema/mysql-schema.sql` in full** — it folds every migration into one large file; grep it for the specific `CREATE TABLE` needed instead of reading it whole.

And the assistant **MUST NOT** perform **repeated repository indexing** — re-listing directory trees or re-summarizing project structure already established earlier in the conversation, in `CODEBASE_AUDIT.md`, or in the continuity log.

### 3.2 When a broad scan *is* justified

A wide or full-repository scan **MAY** be performed only when at least one holds:

| # | Justification | Example |
|---|---|---|
| 1 | The task is inherently repository-wide | A security sweep ("find every unguarded route"), a secret scan, a dependency-usage inventory |
| 2 | A symbol/pattern must be found and targeted search has failed | Renaming a concept whose spellings vary; dead-code confirmation |
| 3 | Impact analysis of a cross-cutting change | Changing a shared component contract, middleware, or the tenancy trait |
| 4 | First orientation in an unfamiliar area **and** no prior map exists in context or the docs | A subsystem with no prior discussion |

Even then, the scan **SHOULD** be delegated to a search-only subagent (§4) so raw file dumps do not enter the primary context — only the conclusions do.

---

## 4. Subagent Governance

### 4.1 Rules

- Subagents **MUST NOT** be spawned unless they provide **measurable value**: parallelism the primary session cannot achieve, context isolation for bulk reading, or genuinely specialized review focus.
- **One responsibility per subagent.** A single, clearly stated mission and a defined deliverable.
- **No overlapping responsibilities.** Two concurrent subagents never investigate the same question.
- **No duplicate investigations.** A subagent is never dispatched to answer a question already answered in the conversation.
- **Use the primary session whenever possible.** Delegation is the exception that must be justified.
- **Spawn subagents only for specialized work** — bounded missions of the kinds tabulated below.

### 4.2 Recognized subagent missions

| Subagent | Purpose | When to Use | When NOT to Use |
|---|---|---|---|
| **Architecture Review** | Assess a design against [`ARCHITECTURE_PRINCIPLES.md`](./ARCHITECTURE_PRINCIPLES.md) (layering, services, tenancy boundary) | Before a structurally significant change | Small, module-local changes with obvious placement |
| **Security Review** | Assess a change against [`SECURITY_PRINCIPLES.md`](./SECURITY_PRINCIPLES.md) / [`ACCESS_CONTROL.md`](./ACCESS_CONTROL.md) | Auth, tenancy, money, grades, uploads, new routes | Changes with no security-relevant surface |
| **Performance Review** | Identify N+1s, unbounded queries, hot paths | Measured regression; new list/report endpoints | Premature optimization of unmeasured code |
| **Documentation Audit** | Verify docs match current behavior | After a feature lands; before a checkpoint | While the implementation is still in flux |
| **Testing Audit** | Verify tests are behavioral and the suite is honest | After significant test additions; money/grades coverage claims | As a substitute for actually running the suite |
| **Complex Debugging** | Isolate a defect requiring broad, deep tracing | Multi-module failures; reproductions needing many reads | Single-file bugs the primary session can read directly |
| **Large Refactoring Planning** | Produce an incremental, reversible step plan | God-controller decomposition (per [`CONTINUOUS_MODERNIZATION.md`](./CONTINUOUS_MODERNIZATION.md)) | Renames and local cleanups |
| **Search / Exploration** | Sweep many files and return *conclusions only* | Justified broad scans (§3.2) | When one targeted search would answer it |
| **Adversarial Review** | Refute findings before a checkpoint commit | Verifying money/tenancy changes pre-commit | Trivial changes already covered by the suite |

### 4.3 Model selection

Where the platform supports choosing a model per subagent, route work by difficulty:

- **Cheaper / faster model:** routine analysis, formatting, documentation drafting, summaries, mechanical search-and-report missions.
- **Most capable model:** architecture decisions, security/tenancy reasoning, complex debugging, implementation planning — anything where a wrong conclusion is expensive.

Where model selection is **unavailable**, **prompt optimization (§5) is the primary efficiency strategy**: tighter missions, smaller contexts, and structured deliverables substitute for cheaper models.

### 4.4 Multi-agent fan-outs (workflows) — the expensive habit

A **fan-out** — several subagents launched in parallel over one request — multiplies cost by the agent count: **every subagent starts from zero and re-reads the repository for itself.** Measured in this repository, a routine 3–5-agent recon fan-out costs **130k–230k tokens**; the same questions answered in the primary session with targeted greps and scoped reads typically cost **under 20k**.

Therefore:

- A fan-out **MUST NOT** be the default for recon, audits, or "where does X live?" questions. The default is: one targeted search, then the smallest read that answers it, in the primary session.
- A fan-out **MAY** be used only when at least one §3.2 justification holds **per agent**, or for adversarial verification of money/grades/tenancy changes before a checkpoint, or when the operator explicitly asks for an exhaustive multi-angle audit.
- When a fan-out *is* justified, each subagent **MUST** return **conclusions + `file:line` evidence only — never file dumps** — and the agent count is the minimum that covers the independent questions (two agents that could be one prompt are one agent).
- **Rough budget expectation:** a routine command (question, small feature, single-page change) should complete within roughly **20–40k tokens**. Crossing ~100k on a routine task is a signal the strategy is wrong — stop and simplify, don't push through.

---

## 5. Prompt Optimization

The assistant — and **every subagent prompt it writes** — **MUST**: avoid repeated context (reference, don't restate); avoid narrating mechanics; avoid verbose output (length justified by information, not thoroughness theater); produce concise, structured responses (table/checklist over prose when enumerable); reuse previous analysis; never re-analyze unchanged files; avoid repeating repository summaries; avoid reading files twice; and prefer incremental (delta) analysis.

### 5.1 Examples

**Bad subagent prompt (unbounded, duplicative):**

> "Explore the finance module, understand the architecture, and tell me everything about how payments work, including all related files and their full contents."

**Good subagent prompt (bounded mission, conclusions-only deliverable):**

> "In `app/Http/Controllers/Finance/PaymentController.php`, report what `verify()` writes inside its transaction and which columns of `payment_submissions` it updates. Deliverable: method + line ranges and a one-line answer per column. Do not paste full file contents."

**Bad in-session behavior:** re-reading a 1,000-line controller in full to change one method already read earlier in the session.

**Good in-session behavior:** quoting the earlier read, re-reading only the ~30-line method being changed to confirm it is unchanged, then editing.

---

## 6. Background Sessions

Governs background agents, watchers, loops, continuous monitoring, and scheduled execution.

### 6.1 Core rule

> **Continuous AI execution is prohibited unless explicitly justified and approved by the operator.**

An always-running or frequently-polling AI process consumes tokens indefinitely and MUST clear a high bar: a stated purpose, a stated frequency, a stated stop condition, and operator approval. (The application's own scheduler — `routes/console.php`, e.g. `finance:generate-soas` — is deterministic product code governed separately; this section governs the *assistant's* use of background AI while engineering.)

### 6.2 Preferred execution models (in order)

1. **Event-driven** — run when something actually happens (a PR, a merge, a failing CI run).
2. **Manual** — run when the operator asks.
3. **Scheduled** — run on a deliberate, low-frequency schedule.
4. **Polling / continuous loops** — last resort; requires justification, the longest acceptable interval, and a defined end.

### 6.3 Recommended cadences

| Activity | Recommended cadence | Trigger preference |
|---|---|---|
| Security review | Per change touching auth/tenancy/money/grades/uploads; full pass monthly | Event-driven (PR) |
| Architecture review | Per Significant/Cross-cutting change (`DEVELOPMENT_WORKFLOW.md` §1); health check quarterly | Event-driven (RFC/change) |
| Documentation generation/audit | Per feature completion; sweep monthly | Event-driven (checkpoint) |
| Dependency audit | `composer audit` / `npm audit` weekly in CI; AI-assisted review monthly | Scheduled |
| Performance audit | Per release, or on a regression signal | Event-driven (metrics) |
| Knowledge sync (docs ↔ code) | Per completed feature; monthly sweep | Event-driven (checkpoint) |
| Risk assessment | Per significant change; quarterly, and after any incident | Event-driven (RFC) |

These are defaults, not entitlements: a scheduled activity that repeatedly finds nothing **SHOULD** have its frequency reduced.

---

## 7. MCP Governance

Model Context Protocol (MCP) servers extend the assistant's reach — and every tool result they return **remains in the conversation context**. Large MCP responses are paid for again on **every subsequent model call** in the session. MCP discipline is therefore context discipline.

### 7.1 Rules

- **Only required MCP servers are enabled**; unused servers are disabled (their tool schemas alone consume context).
- **Queries request only the required information** — filtered, projected, and limited.
- **No full database dumps.** Query for the rows and columns needed; always use `LIMIT`. Never `SELECT *` on production-sized tables.
- **No full repository listings** via MCP filesystem tools; use targeted globs.
- **No excessive filesystem traversal.**
- **SQL result sizes are bounded** (row limits, column projection, aggregates over raw rows); schema questions go to `information_schema`, filtered.
- **API responses are limited** (pagination, field selection, count caps).
- **Reusable results are cached** — written to the scratchpad or summarized once in context — rather than re-fetched.

### 7.2 Per-server recommendations

| MCP server | Guidance |
|---|---|
| **Filesystem** | Targeted paths and globs only; never recursive dumps of large trees; respect §3 exclusions (`vendor/`, `node_modules/`, builds, dumps) |
| **Git** | Prefer `log`/`diff` with path and count limits; never full-history dumps; diffs over whole-file snapshots |
| **GitHub** | Fetch single PRs/issues by number; paginate lists; request only needed fields |
| **Database** | Read-only by default; `LIMIT` always; schema introspection once per session, then reuse; aggregates over row dumps |
| **Browser** | Extract the relevant page section, not full-page HTML; avoid screenshot loops; close what is opened |
| **Docker** | Query specific containers/images; tail logs with line limits; never full log dumps |
| **Playwright** | Scripted, deterministic flows; assert on targeted selectors; avoid full-DOM snapshots and repeated screenshots |
| **Future servers** | Apply the same doctrine by analogy: *smallest useful response, no standing dumps, cache what will be reused* |

---

## 8. Context Management

### 8.1 Why context size matters

Large contexts **reduce efficiency** (relevant facts drown in stale tool output), **increase latency** (every call reprocesses the window), and **increase cost** (input tokens are re-billed on every call). A long-running session with an ever-growing window makes *every* subsequent command expensive, no matter how small the command itself is.

### 8.2 When to recommend `/compact`

The assistant **SHOULD** proactively recommend `/compact` at natural boundaries: after completing a feature; after an architecture or security review concludes; after heavy MCP or subagent usage; after long conversations with many resolved threads; before switching modules; before beginning a major implementation; and after reviewing many files whose contents are no longer needed verbatim.

### 8.3 Decision flow

```
Has a unit of work just completed (feature / review / audit)?
   ├─ Yes → Is its raw detail (file dumps, tool output) still needed verbatim?
   │           ├─ No  → RECOMMEND /compact
   │           └─ Yes → Continue; revisit at the next boundary
   └─ No → Is a major new phase about to begin (new module, big implementation)?
              ├─ Yes → RECOMMEND /compact before starting
              └─ No  → Is the context dominated by stale tool output or repeated analysis?
                         ├─ Yes → RECOMMEND /compact
                         └─ No  → Continue
```

> **Note.** Compaction is recommended, never self-authorized destructively: the assistant surfaces it and the operator decides. Before recommending, ensure durable conclusions (decisions, plans, open items) are recorded where they survive compaction — a document, an ADR, or the continuity log.

---

## 9. Token Efficiency — Pre-Work Evaluation

Before **every implementation**, the assistant **MUST** internally evaluate:

- [ ] Can fewer files be read?
- [ ] Can previous analysis be reused?
- [ ] Is a repository scan necessary — does it meet a §3.2 justification?
- [ ] Is a subagent actually needed — does it meet §4.1?
- [ ] Is a multi-agent fan-out actually justified — does it meet §4.4?
- [ ] Can the prompt (to any subagent or tool) be shorter?
- [ ] Can MCP usage be reduced or bounded further?
- [ ] Is the current context already too large for efficient work?
- [ ] Should `/compact` be recommended first (§8.3)?
- [ ] Can duplicate work be avoided — has any of this been done already?
- [ ] Is there a cheaper operational strategy that produces the same engineering quality?

This evaluation is internal and silent by default; surface it only when it changes the plan (e.g., "I'll reuse the earlier analysis rather than re-read the module").

---

## 10. Implementation Pre-Flight Checklist

Before writing code (i.e., once the Constitution §9 approval gate — **"Proceed to coding."** — has been passed), the assistant **MUST** verify:

- [ ] **Repository understood** — the relevant area's structure and conventions are known, not guessed.
- [ ] **Architecture understood** — placement, layering (Route → Controller → Service → Repository → Model), and the tenancy boundary are settled.
- [ ] **Requirements understood** — what is being built, for whom, and what "done" means.
- [ ] **Governance documents consulted** — the change complies with the mandatory set, in precedence order.
- [ ] **Minimal files identified** — the exact files to touch are enumerated; nothing extra.
- [ ] **Existing implementation reviewed** — current behavior is known; reuse before rebuild.
- [ ] **Risks identified** — failure modes, blast radius, migration hazards, rollback path.
- [ ] **Security considered** — tenancy scoping, role gates, input handling, per [`SECURITY_PRINCIPLES.md`](./SECURITY_PRINCIPLES.md).
- [ ] **Performance considered** — queries, N+1s, payload sizes.
- [ ] **Maintainability considered** — the next engineer can understand and change this.
- [ ] **Context size acceptable** — or `/compact` recommended before starting (§8).

An unchecked item is a reason to pause and resolve it — not a formality to skip. Money and grades changes additionally **do not merge without a test** (Constitution, [`DEVELOPMENT_WORKFLOW.md`](./DEVELOPMENT_WORKFLOW.md)).

---

## 11. Post-Implementation Responsibilities

After implementation, the assistant **MUST** assess — and recommend to the operator where applicable — each of:

| Responsibility | Recommend when… |
|---|---|
| **Documentation updates** | Behavior, interfaces, configuration, or run steps changed |
| **Knowledge / continuity updates** | A durable decision or gotcha emerged |
| **Risk updates** | A new risk was introduced, or an existing one changed |
| **Architecture updates (ADR/RFC)** | The change altered boundaries or contracts (`DEVELOPMENT_WORKFLOW.md` §3) |
| **Deployment updates** | New env vars, migrations, scheduler/cron, or run steps exist (`DEPLOYMENT.md`) |
| **Testing** | Any behavior changed — state what is covered and what is not |
| **Validation** | The change should be observed working end-to-end, not just compiling |
| **`/compact`** | The conversation has accumulated large stale context (§8) |

Recommendations are made honestly and specifically — never as a boilerplate list. "Nothing needed" is itself a finding to state.

---

## 12. Engineering Philosophy

**Excellent software engineering is not measured by the number of AI requests made.** It is measured by producing the **highest-quality engineering outcome with the minimum necessary computation**. Every token spent, every file read, and every subagent spawned is an engineering resource — spent deliberately or wasted.

The mandated workflow:

```
Think
  ↓
Understand
  ↓
Plan
  ↓
Reuse Existing Knowledge
  ↓
Read Minimal Context
  ↓
Implement
  ↓
Validate
  ↓
Document
  ↓
Compact Context
  ↓
Continue
```

Discipline, not volume, is what compounds. An assistant that thinks first, reads narrowly, reuses what it knows, and validates what it ships will — over the life of Sophentis — be *both* the cheapest and the best engineer in the room. That is the standard this document holds it to.

---

*This document is part of the mandatory governance set of this repository, subordinate to [`ENGINEERING_CONSTITUTION.md`](./ENGINEERING_CONSTITUTION.md) and read alongside [`ENGINEERING_PRINCIPLES.md`](./ENGINEERING_PRINCIPLES.md), [`ARCHITECTURE_PRINCIPLES.md`](./ARCHITECTURE_PRINCIPLES.md), [`SECURITY_PRINCIPLES.md`](./SECURITY_PRINCIPLES.md), [`DEVELOPMENT_WORKFLOW.md`](./DEVELOPMENT_WORKFLOW.md), and [`CONTINUOUS_MODERNIZATION.md`](./CONTINUOUS_MODERNIZATION.md). It ranks last in the Constitution §6 precedence order by design, and is portable across repositories.*

**Amendment history:** v1.0 (2026-07-05) — initial ratification as the seventh member of the Sophentis governance set (Constitution v1.1, §5/§6/§8/§14); §4.4 fan-out discipline codified from measured in-repo token costs.
