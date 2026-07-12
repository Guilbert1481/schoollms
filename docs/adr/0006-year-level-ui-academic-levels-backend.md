# ADR-0006 — "Year Level" in the UI, `academic_levels` as backend plumbing

- **Status:** Accepted (operator-ratified 2026-07-12)
- **Context:** Gamified Quiz question supply; applies to all future features touching levels.

## Context

The schema carries three overlapping "level" concepts:

| Concept | Where | Values | Meaning |
|---|---|---|---|
| `education_level` | `student_enrollments`, `terms`, `academic_years` (string) | `basic_ed`/`higher_ed`; `kinder, elementary, junior_high, senior_high, undergraduate, graduate` | Coarse segment — splits the system (Principal basic-ed vs Admin higher-ed consoles, wizards, finance) |
| `year_level` | `student_enrollments`, `sections`, `curriculum_subjects` (integer) | 1–12, 1–5 | Raw grade/year number **within** a segment — ambiguous alone (`2` = Grade 2 *or* Year 2) |
| `academic_levels` | its own table; FK'd by `questions.academic_level_id` (NOT NULL) and `tests.academic_levels` | rows like "Grade 7" (`basic`), "Year 2" (`higher`), plus `training`/`review` types | The question-bank tagging vocabulary — one addressable row per (segment, year_level) pair |

`academic_levels` encodes the same information as the pair `education_level + year_level`.
Nobody asks "what is your academic level?" — people ask "what is your **year level**?" —
so the term must not leak into the UI. But removing the table (storing the pair on
`questions` directly) would be a wide refactor across every teacher question builder
(MCQ/TF/Identification/Matching/Essay/Enumeration/FIB share the metadata flow), the test
builder (`tests.academic_levels` JSON + level-filtered queries), and print/answer-key
controllers — high risk for zero user-visible gain.

## Decision (Option A)

1. **UI language:** always "Year Level" (higher ed) / "Grade Level" (basic ed) — labels
   like "Grade 7", "Year 2". The phrase "academic level" never appears on a screen.
2. **Backend:** `academic_levels` stays as the internal FK vocabulary, **seeded once**
   with the standard rows: Kinder + Grade 1–12 (`basic`), Year 1–5 (`higher`), one
   `training`, one `review` (see `database/seeders/AcademicLevelSeeder.php`).
3. **The bridge:** a person's level is *derived*, never asked —
   `education_level + year_level` (from their enrollment, or a teacher's sections)
   → the matching `academic_levels` row. Example: `junior_high` + `7` → "Grade 7".

## Consequences

- New features filter/label by **year level** in the UI while persisting
  `academic_level_id` where the schema demands it.
- Question/test creation is unblocked (the empty `academic_levels` table previously made
  `questions.academic_level_id` unsatisfiable — even the teacher builders could not save).
- If the table is ever retired (Option B), it is a self-contained refactor: replace the
  FK with the pair, migrate the seeded rows' meanings, and update the builders — this ADR
  is the map for that work.
