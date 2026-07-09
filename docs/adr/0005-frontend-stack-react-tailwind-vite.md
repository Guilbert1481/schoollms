# ADR-0005 — Standardize the frontend on React + Tailwind + Vite (islands, not a big-bang SPA)

> **Status:** Accepted · **Date:** 2026-07-09 · **Deciders:** Jabhy (operator) + Sophentis team

## Context

Sophentis's frontend grew unevenly and is not uniform:

- **Styling was split** between the Tailwind Play CDN (`cdn.tailwindcss.com`) on ~16 pages and a
  compiled Tailwind-via-Vite pipeline on the app layouts. The CDN→Vite migration is now complete
  (16/16 pages), but nothing records that compiled Tailwind is the *only* sanctioned styling system
  going forward — leaving the door open to a third approach reappearing.
- **Interactivity is inconsistent** — a mix of Alpine.js, hand-written vanilla JS, and inline
  `<script>` blocks, plus an ~11k-line `public/js/app.js`. React 18 (+ `react-hook-form`,
  `react-select`) and `@vitejs/plugin-react` are already installed and Vite ^6 is already the bundler,
  but React is barely used: only `contact-address/main.jsx` is wired as an island; `app.jsx` and
  `components/TeacherDashboard.jsx` are dormant (not a Vite input, referenced by no Blade).
- **There is no API seam.** The app is a server-rendered Blade monolith with no `routes/api.php`; the
  two `Api\*` controllers return raw model JSON over web/session routes (see
  [`ARCHITECTURE_PRINCIPLES.md`](../../ARCHITECTURE_PRINCIPLES.md) §8). A stack audit concluded the app
  is **not** ready for a direct React SPA rewrite.

We want one predictable frontend vocabulary so new UI looks and behaves consistently and is
maintainable by the team — without violating the Constitution's **no big-bang rewrites** rule
([`ENGINEERING_CONSTITUTION.md`](../../ENGINEERING_CONSTITUTION.md) §7A).

## Decision

**We will standardize the target frontend stack on React + Tailwind + Vite, adopted incrementally as
React islands mounted into Blade — not as a big-bang single-page-application rewrite.**

Concretely, going forward:

1. **Vite is the only bundler.** All CSS/JS ships through Vite (`@vite([...])`). No page reintroduces the
   Tailwind CDN or any script-tag asset pipeline (Laravel Mix is retired).
2. **Tailwind (compiled) is the only styling system.** No new CSS framework, and no new bespoke global
   stylesheets for things Tailwind already expresses. Page-specific `<style>` is tolerated only for the
   handful of custom classes that already exist (`.glass`, `.bg-executive`, …); prefer moving shared
   ones into `app.css` `@layer components`.
3. **React is the standard for non-trivial interactivity**, built as **islands** — `createRoot` mounting
   a component into a Blade-rendered `<div>`, one Vite entry per island. New interactive UI is a React
   island, not a new Alpine/vanilla/inline-script widget. Trivial toggles may still use Alpine.
4. **Blade remains the page shell and router.** Routing, auth, layout, and server rendering stay in
   Laravel/Blade. We migrate the highest-interactivity screens to islands first; we do **not** rewrite
   low-interactivity pages for its own sake.
5. **Islands talk to the server through a real JSON seam.** As islands grow, they consume endpoints
   backed by **API Resources** (per `ARCHITECTURE_PRINCIPLES.md` §8), never ad-hoc raw-model JSON. The
   API seam grows to meet the islands — this is the enabling dependency for any deeper React adoption.

A future move to a full SPA (should it ever be justified) requires a **new ADR superseding this one**,
and would be gated on the API seam (item 5) being substantially complete.

## Consequences

- **Easier:** one styling system and one interactivity model; reusable React components; new UI is
  predictable to build and review; the already-installed React/Vite toolchain is finally used on
  purpose.
- **Harder / follow-ups:**
  - Requires building out the API-Resource seam incrementally (ties to §8 and roadmap L4).
  - Interim coexistence of Blade pages, Alpine, and React islands — accepted as the cost of evolution
    over revolution; tracked so it converges rather than sprawls.
  - The dormant `app.jsx` / `TeacherDashboard.jsx` must be **wired as real islands or deleted** — not
    left to rot (ties to the CONTINUOUS_MODERNIZATION Q1 "finish migrations / delete dead code" rule).
  - `public/js/app.js` (~11k lines) is legacy debt to be carved into islands by strangler steps, never
    rewritten in one PR.
- **Debt intentionally left:** low-interactivity Blade screens are not migrated on a schedule; they
  convert opportunistically when touched.
