# Education-Level Tabs Pattern

> **What this term means:** when the operator (Jabhy) says *"add the education-level tabs
> pattern to page X"*, he means the **4-part reusable package** documented here — the
> tab bar ("All Levels / Basic Education / Undergraduate / …") driven by the education
> structure tree, plus the level-aware filter row that changes per tab. It first shipped
> on the registrar **Student Ledgers** page (the component docblock still calls it the
> "Student Ledgers pattern") and is now the house style for every staff-facing list page
> that spans multiple education levels.

Reference implementations: `finance/ledger`, `finance/payment` (payments + verification
tabs), `registrar/student_ledgers`, `registrar/enrollments`, `registrar/transcripts`,
`registrar/settings/documents`, `course-architect/lesson-studio` (per-user scoping +
per-root joins — see parts 3b and 5).

---

## The 4 parts

### 1. `<x-table.level-tabs>` — the education-level tab bar (view)

File: `resources/views/components/table/level-tabs.blade.php`

Renders an **"All Levels"** tab plus one tab per offered top-level node of the education
structure tree (`education_nodes` roots: Basic Education, Undergraduate, Graduate School,
…). Navigation is a full page load via a `?level=` query param (`all` or a node id).
**Hides itself entirely when the school offers only one level.**

```blade
<x-table.level-tabs route="finance.payments.index"
                    :levels="$levels"
                    :activeLevelId="$activeLevelId"
                    :showAll="$showAll"
                    :params="['tab' => 'payments']" />
```

| Prop | Meaning |
|---|---|
| `route` | Named route each tab links to. |
| `levels` | Iterable of objects with `->id` and `->name` (offered roots — see part 3). |
| `activeLevelId` | Currently selected level id (ignored when `showAll`). |
| `showAll` | Whether the "All Levels" tab is active. |
| `params` | Extra query params carried on **every** tab URL (e.g. which page pane to return to). Level tabs intentionally do **not** carry the other filter params. |
| `counts` | Optional `[levelId => int]` red badge counts per tab. |
| `accent` | `'indigo'` (default) or `'emerald'`. |
| `allLabel` | Label of the first tab. Default `'All Levels'`; e.g. `'All Grade Levels'` for the basic-ed variant below. |

### 2. `<x-tabs.count-tabs>` — the generic tab bar underneath (view)

File: `resources/views/components/tabs/count-tabs.blade.php`

Generic tab bar (label + url + optional red count superscript + active state) that
`level-tabs` delegates to. Collapses into a hamburger dropdown below 768px using real
`@media` CSS, so it works even with a stale Tailwind build. You normally never call it
directly — only when your tabs are *not* education levels.

### 3. The offered-roots query — `$levels` (controller)

The tabs are fed from the **top-level roots of the education structure tree**:

```php
$levels = DB::table('education_nodes')
    ->whereNull('parent_id')          // top-level roots only
    ->where('is_offered', 1)
    ->where('is_active', 1)
    ->orderBy('order_index')
    ->get(['id', 'name']);

// Resolve the ?level= param (Student Ledgers convention):
$requested      = $request->query('level');          // null | 'all' | id
$showAll        = $requested === 'all' || $requested === null;
$activeLevelId  = $showAll ? 0 : (int) $requested;
$activeLevel    = $levels->firstWhere('id', $activeLevelId);
$singleLevel    = $levels->count() === 1 ? $levels->first() : null;
$effectiveLevel = $activeLevel ?? $singleLevel;      // what filters key off
```

(`App\Support\EducationLevels::offeredRoots()` returns the same set if you prefer the
helper over an inline query.)

**Basic-ed-only variant** — for pages whose role genuinely only touches Basic Education
(e.g. the Principal's Grading Scheme page), the tabs are the offered **stage groups**
under the Basic Education root (Preschool / Elementary / Junior High / Senior High)
instead of the top-level roots, and a selected tab matches data mapped anywhere in its
subtree:

> Be sure the page really is basic-ed-only before reaching for this. The Course Architect
> Lesson Studio used this variant on that assumption and it was wrong — the architect also
> authors undergraduate material, which the stage-group tabs made unreachable. It now uses
> the standard roots variant plus part 3b.

```php
$levels  = EducationLevels::basicStageGroups();          // ->id / ->name, tree order
$nodeIds = EducationLevels::descendantIds($activeLevelId); // group + whole subtree
// e.g. subjects: whereIn('grade_level_subjects.education_node_id', $nodeIds)
```

Don't tab on the raw grade/stage leaves — a K-12 tree with SHS strands yields 30+ stage
nodes, which is unusable as a tab bar. Use `allLabel` if "All Levels" reads wrong.

### 3b. Per-user level scoping — `offeredRootsFor()`

When two staff share a page but own different levels (the Course Architect case: one
authors Basic Education, another the higher-ed programs), assign each user their roots in
`user_education_scopes` and build the tabs with:

```php
$levels = EducationLevels::offeredRootsFor($request->user()->id);
```

**No rows for a user means UNRESTRICTED** — every existing user keeps seeing everything,
so scoping can ship before anyone is assigned. Scope "All Levels" to `$levels` too, not to
all offered roots, or a scoped user reaches other levels' data just by dropping `?level=`.

### 4. `App\Support\EducationLevels` — level-aware filters + data scoping (controller)

File: `app/Support/EducationLevels.php`

This is what makes the **filter row change depending on the active tab** and lets you
scope the actual query rows to the selected level:

| Helper | Use |
|---|---|
| `EducationLevels::isBasic($rootName)` | Is the active tab Basic Education? |
| `EducationLevels::basicGradeOptions()` | Kinder–Grade 12 options for the grade filter (Basic Ed tab). |
| `EducationLevels::yearLevelOptions($levelId)` | Year-level options for a higher-ed tab. |
| `EducationLevels::offeredRoots()` | Offered top-level roots (same as part 3). |
| `EducationLevels::nodeRootMap()` | `[education_node_id => root_id]` map — resolve any row's node to its root to filter the dataset by the selected tab. |
| `EducationLevels::offeredRootsFor($userId)` | Offered roots this user may see (all when unscoped) — see part 3b. |
| `EducationLevels::scopeRootIds($userId)` | Raw scoped root ids; `[]` means unrestricted. |

### 5. A tab may select a different JOIN, not just a different filter value

Do not assume every level reaches your rows the same way. In Lesson Studio, subjects
belong to Basic Education via the `subjects.is_basic_ed` flag, but to any higher-ed root
only through `program_subjects → programs.education_node_id` — the two sets are disjoint
and higher-ed subjects have **no** `grade_level_subjects` rows at all. Give each root its
own predicate and `orWhere` them together for "All Levels"
(`LessonStudioController::subjectsInRoot()` is the reference).

Also prefer whatever key the page already ships with: Basic Ed there keys on the
`is_basic_ed` flag rather than the tree, because 156 subjects carry the flag while only
107 have a tree row — switching to the tree would have silently dropped 49 subjects.

**House filter conventions:**
- Grade/Year filter: `basicGradeOptions()` on Basic Ed, `yearLevelOptions()` on higher ed.
- **Program** dropdown: higher-ed tabs only.
- **Section** dropdown: Basic Ed tab only.
- Dataset scoping under "All Levels" shows everything; a specific tab filters rows whose
  education node resolves (via `nodeRootMap()`) to that root.

Reference controllers: `App\Http\Controllers\Finance\PaymentController@index`,
`App\Http\Controllers\Finance\LedgerController@index`.

---

## When to use which subset

| Need | Use |
|---|---|
| Just the level tabs on a list page | Parts 1–3 |
| Tabs **plus** filters that adapt per level (grade vs year level, program vs section) | Parts 1–4 (the full pattern) |
| Tabs that aren't education levels at all | Part 2 only (`<x-tabs.count-tabs>`) |

## Gotchas

- The tab links intentionally drop the other filter params (search, status, etc.) —
  switching level resets filters. Only what you pass in `params` survives.
- `level-tabs` renders nothing when only one level is offered — controllers must still
  work with `$singleLevel` as the effective level in that case.
- Tab navigation is server-side (full request), not Alpine/JS state — each tab must be a
  real route hit that re-derives `$levels`/filters.
- `count-tabs` uses literal accent class strings and its own `@media` CSS on purpose:
  do not refactor to dynamic Tailwind class names (purge/stale-build risk).
- Multi-tenant: `education_nodes` itself is a global tree (no `school_id` column) — the
  tenant scoping belongs on the **data** you list (subjects, enrollments, payments, …),
  which the surrounding controller must always filter by `school_id`.
