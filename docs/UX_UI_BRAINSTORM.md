# UX/UI stretch-goal brainstorm

**Status: implemented and verified, 2026-08-08 — this file is now
historical**, kept as the planning record (see
`docs/UX_UI_SPECIFICATION.md`'s status note for where the living version
of its conclusions now lives).

**Status when approved, 2026-08-08:** The project owner
reviewed this alongside [UX_UI_SPECIFICATION.md](UX_UI_SPECIFICATION.md)
and made one scope call, recorded here rather than only in chat history:
**no new case narrative/content or data-model change** is the sole
remaining hard non-goal. Everything else this document originally flagged
as scope-creep risk — a client-side router, autocomplete-style search
polish, a UI framework/library, and a *lightweight* gamification layer —
is explicitly in scope, on the condition that it stays frontend-only (the
backend, rule engine, and data layer remain a hard limit; the E2E test
suite may be extended/updated as needed). Gamification specifically was
upgraded from "flag for veto" to "regarded as essential to the
implementation succeeding," deliberately kept unelaborate rather than a
full points/leaderboard system. See
[UX_UI_SPECIFICATION.md §1](UX_UI_SPECIFICATION.md#1-purpose-scope-and-the-governing-constraint)
for how this plays out concretely; the rest of this document is kept as
originally written (including the now-superseded risk framing) as the
historical record of the idea set the decision was made against.

## Why this exists

Project-owner request, 2026-08-08: the core prototype and its CI/deployment
story are done and verified (see `HANDOFF.md`, `docs/CHANGELOG.md`), and
there's schedule room before the freeze to invest in a stretch goal —
making the frontend look and feel like a deliberately designed product
rather than a functional-but-bare scaffold, without pulling the project's
scope off-track. Explicit starting points from that request: an in-app
tutorial, better case naming, mobile responsiveness, and a more considered
visual style. This document expands those into a fuller idea set, organized
so the project owner can accept, edit, or veto at the level of an individual
idea rather than an all-or-nothing package.

## Constraints this brainstorm already respects

`docs/DEVELOPMENT_DOCUMENTATION.md` §7 records that the current bareness is
a *decision*, not an oversight: no client-side router (the workflow is a
strictly linear case → code → feedback path), no network-backed
autocomplete (the response domain is at most six codes per case), feedback
always pairs colour with text so colour is never the only signal, the
scope disclaimer is first-thing-visible, and the client never receives
enough information to reveal the answer key. Every idea below is written
to *add polish and orientation on top of* that reasoning, not to reverse
it. Ideas that would cross one of these lines are called out explicitly
in [Ideas that risk scope creep](#ideas-that-risk-scope-creep-flag-for-veto)
rather than folded in quietly.

The Selenium E2E suite (`TEST-E2E-01`/`02`, `app/tests/E2E/`) also encodes a
hard contract on today's markup — exact class names (`case-list`,
`code-list`, `result-heading`, `improvement`), an exact id (`code-search`),
exact submit-button text (`Submit code`), and the raw `case_id` string
appearing somewhere in each case's list entry. None of that is a reason to
avoid a redesign; it's a reason the specification must say, for each
component, whether the old selector survives or the test needs updating in
the same change. That mapping is deferred to the specification, not
repeated per-idea here.

## Current state, in one paragraph

Plain React 19 + Vite, zero UI dependencies (no component library, no icon
set, no custom fonts, no CSS framework — `system-ui` and hand-written
`App.css`). Three in-memory views (case list → case detail → result), a
disclaimer banner, a plain button list of cases labelled only by
`case_id` + setting + diagnosis role, a native `<input>` + radio-button
list for code search/selection, and a result heading coloured by class
with an explanation paragraph and an optional improvement-code line. No
onboarding, no icons, no responsive breakpoints (though the single-column
`max-width: 40rem` layout does not actively break on narrow screens —
"responsive" here mostly means "intentional," not "currently broken").
One fact worth surfacing immediately: the backend already returns a
`short_description` field per case (e.g. *"Documented COPD with acute
lower-respiratory infection; stable-phase FEV1 = 55% predicted"* for
CASE-001) that the frontend simply never renders. Better case naming may
be close to a free win — see below.

---

## Idea categories

Each idea is tagged `[effort]` (S/M/L, rough relative size) and `[risk]`
(none / low / needs-decision) as a triage aid, not a commitment.

### 1. Onboarding & tutorial

- **First-visit walkthrough.** A short (3-5 step) dismissible overlay/modal
  on first load, mirroring the existing `docs/USER_GUIDE.tex` "Using the
  prototype" narrative (choose a case → review the facts → search and
  submit a code → interpret the feedback) so the in-app tutorial and the
  PDF manual tell the same story rather than two different ones. `[M] [low]`
- **Persistent re-entry point.** A small "?"/"How this works" button
  always visible (e.g. in a lightweight header), so the tutorial isn't a
  one-shot a learner can't get back to. Gate the auto-show on
  `localStorage`, not a cookie/account — there's no auth in this prototype
  and shouldn't be. `[S] [none]`
- **Contextual hints over a full tour.** Alternative/complement to the
  modal: a one-line hint under each major control the first time it's
  seen ("Type to filter, then choose one code" above the search box).
  Lower-commitment than a tour; easy to combine with the above. `[S] [none]`
- **Empty-state guidance.** If the case list is empty or fails to load,
  say something more actionable than "Could not load cases" (e.g., suggest
  checking whether the backend/database containers are up) — mainly
  matters for the *demonstrator* audience (evaluators, thesis committee)
  running this for the first time. `[S] [none]`

### 2. Case presentation & naming

- **Surface `short_description` as the primary label.** Already-returned
  data, zero backend/API/baseline change. Replace "CASE-001 — inpatient,
  main diagnosis" with the clinical one-liner as the heading, keeping
  `case_id` as a small secondary badge (both for learner orientation *and*
  because `TEST-E2E-02` currently asserts on `case_id` substrings in the
  list). `[S] [none]`
- **Case cards instead of a plain button list.** Visually distinguish
  setting (inpatient/outpatient) and diagnosis role with small badges/tags
  (colour- or icon-coded) rather than plain comma-separated text. `[S] [low]`
- **Consistent case ordering.** Not currently guaranteed to be meaningful
  to a learner (`CASE-001, 002, 003, 005...` — note `004`/`008` are
  correctly absent, verification-only). Cosmetic only; no reordering of
  underlying data. `[S] [none]`
- **Richer per-case narrative text.** Going beyond `short_description` to
  a fuller "chart note" style vignette. This is a *content* change, not a
  presentation change — flagged separately below, because it would touch
  case data, not just the frontend.

### 3. Visual design system

- **A real colour palette.** Currently just three semantic colours
  (correct/suboptimal/incorrect) plus browser defaults everywhere else. A
  small, consistent palette (a primary/brand colour, neutral greys for
  chrome, the three semantic colours kept but harmonized with it) would
  make the whole app read as designed rather than unstyled. Needs to keep
  WCAG AA contrast in both light and dark (the app already declares
  `color-scheme: light dark`, so this is not a new commitment, just
  currently unfilled). `[M] [low]`
- **Typography scale.** One heading size and one body size today; a
  proper scale (H1/H2/body/small) with consistent spacing improves
  scannability without adding a webfont dependency (keep `system-ui`, or
  pick one variable font if the project owner wants a distinct voice —
  flagged as a decision, not assumed). `[S] [low]`
- **Spacing/layout tokens.** Replace ad hoc `rem` values with a consistent
  spacing scale (4/8/12/16/24/32px-equivalent) for visual rhythm. `[S] [none]`
- **Iconography.** Small inline SVGs (no icon-font/library dependency) for
  case settings, the three result classes, and the tutorial — reinforces
  the existing "colour is never the only signal" principle by adding a
  *third* channel (icon shape) alongside colour and text. `[M] [low]`
- **Card elevation / borders / radius.** Subtle shadows or borders to
  create visual hierarchy between the disclaimer, case cards, and the
  active case panel. `[S] [none]`

### 4. Layout & responsiveness

- **Deliberate breakpoints.** Define mobile (<640px) / tablet / desktop
  behaviour explicitly rather than relying on the single-column layout
  accidentally not breaking. Card grid on wider screens, single column on
  mobile. `[M] [none]`
- **Touch-target sizing.** Radio-button code list and buttons should meet
  a minimum touch target (≈44px) on mobile — currently likely marginal.
  `[S] [none]`
- **Sticky submit action on mobile.** With a scrollable code list, a
  sticky "Submit code" bar at the bottom of the viewport avoids a learner
  scrolling back up on a phone. `[S] [low]`
- **Viewport meta / PWA-lite touches.** Confirm the Vite `index.html` has
  a correct `viewport` meta tag; optionally a manifest + favicon/theme
  colour for a more "real app" feel on mobile home screens. `[S] [none]`

### 5. Feedback (result) screen

- **Icon + colour + text triple-redundancy.** A checkmark/warning/cross
  icon next to the heading, on top of the existing colour+text — makes the
  class legible at a glance and is *more* accessible than today, not less.
  `[S] [low]`
- **Visually distinct explanation vs. improvement.** Right now both are
  plain paragraphs; a callout/box style for the improvement suggestion
  would separate "why" from "what to do differently." `[S] [low]`
- **Clearer next-step affordances.** "Try another code" / "Back to cases"
  are currently plain buttons with no visual priority between them;
  consider a primary/secondary button distinction. `[S] [none]`
- **Transition/animation on reveal.** A subtle fade/slide when the result
  appears, gated behind `prefers-reduced-motion`. `[S] [low]`

### 6. Code search & selection

- **Visual polish of the existing list**, without changing its mechanism:
  better hover/focus/selected states, clearer grouping, larger click
  targets. Keeping it a plain filtered `<input>` + native radios (not a
  network-backed autocomplete) is explicitly *not* up for reconsideration
  here — see §7 in `DEVELOPMENT_DOCUMENTATION.md`. `[S] [none]`
- **"No results" state** for the search filter — currently just renders an
  empty list with no explanation. `[S] [none]`
- **Selected-code confirmation.** A small persistent summary ("You've
  selected J44.02") above the submit button, useful once the list is
  scrollable and the selection may be scrolled out of view. `[S] [low]`

### 7. Trust, tone, and branding

- **A (very light) identity.** Right now there's no visual identity beyond
  the text title. A simple wordmark/favicon and a consistent voice in
  copy (currently fine, just plain) would help it read as "a project,"
  which matters for a thesis artefact being demonstrated to evaluators.
  `[S] [none]`
- **Scope disclaimer stays first-visible but could be less legalistic in
  tone** while keeping the exact same meaning (`REQ-SCP-02` requires the
  content, not the phrasing). `[S] [none]`
- **Footer with a link to more information** (e.g., "About this prototype"
  linking to a short project description) — optional, low priority.
  `[S] [none]`

### 8. Accessibility

- **Keyboard operability audit.** Tutorial modal must trap focus and close
  on Escape; all interactive elements need visible `:focus-visible` states
  (currently relying on browser defaults, which is *acceptable* today but
  a custom visual design must not silently remove them). `[M] [low]`
- **`aria-live` region for async state changes** (result appearing,
  errors) so screen-reader users aren't left guessing after submitting.
  `[S] [low]`
- **Colour contrast audit** once a palette exists (WCAG AA at minimum,
  both light and dark). `[S] [none]` (folds into the palette work)
- **Semantic landmarks** (`<main>`, `<nav>` if a header is added, proper
  heading hierarchy) — currently mostly flat `<section>`s. `[S] [none]`

### 9. Loading / error / empty states

- **Skeleton or spinner for case list / case detail loading**, replacing
  the current plain "Loading cases…" text. `[S] [none]`
- **Distinguishable error states** — network failure vs. "not evaluated"
  vs. HTTP error currently all render similarly plain messages. `[S] [low]`

### 10. Dark mode

- The app already declares `color-scheme: light dark`, meaning native form
  controls already respect the OS preference; the *custom* palette and
  all new components would need to do the same explicitly (this is where
  most of the actual work is, not in "adding" dark mode but in not
  breaking the existing implicit support). `[M] [none]` — effort mostly
  lands wherever the palette work already is, not as a separate feature.

---

## Ideas that risk scope creep — flag for veto (superseded 2026-08-08)

Originally listed separately because the project owner asked ideas be
flagged if they risk pulling scope, not silently folded into "polish."
**Resolved 2026-08-08** — kept below verbatim as the record of what was
flagged and why, with the actual decision noted inline. Only the first
item remains an actual non-goal.

- **Richer per-case narrative content** (full clinical vignette text
  beyond `short_description`). This is new *case data*, not a frontend
  change — it would touch `prototype_baseline_0_1/data/cases_0_2.csv`,
  the baseline-versioning discipline (`CASEBASE-0.x`), and potentially
  `chapter3_*.md` control documents. **Decision: stays out of scope.**
  The one point the project owner explicitly agreed with unchanged.
- **Progress tracking / gamification** (e.g., "cases completed," a score,
  a completion badge). No backend session/identity concept exists (by
  design — no accounts, no persistence of a learner's identity across
  visits), so this needs a client-only mechanism to avoid touching the
  data layer. **Decision: in scope, and explicitly called "essential to
  the implementation succeeding" — deliberately not an elaborate concept.**
  Implementation constraint from that same instruction: `localStorage`
  only, no backend/session/data-layer change of any kind; see
  `UX_UI_SPECIFICATION.md` for the concrete mechanism.
- **Any UI library or CSS framework adoption** (Tailwind, MUI, etc.). A new
  build dependency and a real `DEVELOPMENT_DOCUMENTATION.md`-grade
  technology decision, not a styling detail. **Decision: permitted, not
  mandated** — the specification's plain-CSS recommendation stands on its
  own engineering merits (§2.1), not because adoption was vetoed.
- **Reconsidering the no-router / no-autocomplete decisions.** **Decision:
  permitted** — the project owner explicitly lifted this restriction,
  provided it stays frontend-only. Whether either is actually *adopted* is
  a separate call from whether it's *allowed*; see the specification for
  what was actually implemented and why.
- **Changing the evaluation/feedback *logic*** (thresholds, wording of
  explanations, which class something falls into). **Decision: stays out
  of scope** — the project owner's own framing ("the backend, the rule
  model, the rules, anything in the data layer... is our hard limit")
  covers this directly; nothing here touches `app/src/Rules/*` or
  explanation text content, only how it's visually framed.

---

## Suggested groupings for the specification

Not a phasing plan (that belongs in the specification once the project
owner has trimmed this list) — just a note on natural clusters:

1. **Design system foundation** (§3: palette, type, spacing, icons) — most
   other work depends on this existing first.
2. **Case list & naming** (§2's `short_description` surfacing + card
   redesign) — the most self-contained, lowest-risk, highest-visibility
   change; a natural first slice.
3. **Case detail & code selection** (§6 polish).
4. **Result screen** (§5).
5. **Onboarding/tutorial** (§1) — benefits from the design system and
   redesigned screens existing first, so its illustrations/references
   match what's actually on screen.
6. **Responsive/accessibility pass** (§4, §8) — cuts across everything
   above; best treated as a final QA pass over the whole app rather than
   a bolt-on at the end, but sequenced last here only because it's easiest
   to *verify* once the other visual changes have landed.

---

## Questions for the project owner (answered 2026-08-08)

Originally called out so they wouldn't be buried in the specification's
prose. Answers recorded here for the historical record; see
`UX_UI_SPECIFICATION.md §9` for how each became a concrete default.

1. `short_description` as-is vs. new case narrative text? →
   **`short_description` as-is; no case-data change of any kind.**
2. Plain CSS vs. a framework/library? → **No objection to either; the
   specification's zero-dependency recommendation stands on its own
   merits, not as a restriction.**
3. Dark mode: explicit attention or "don't break existing support"? →
   Not separately raised in the reply; treated as still open, specification
   default (explicit token-level support) stands unless overridden.
4. Tutorial shape? → Not separately raised in the reply; specification
   default (auto-shown modal + persistent re-entry point) stands unless
   overridden.
5. Gamification appetite? → **In scope, and explicitly "regarded as
   essential for the successful completion of the implementation" —
   deliberately not an elaborate concept.** The single biggest change from
   the original brainstorm framing.
