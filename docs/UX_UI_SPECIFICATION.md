# UX/UI stretch-goal specification (draft)

**Status: draft, awaiting project-owner review — nothing here is
implemented or approved.** Derived from
[UX_UI_BRAINSTORM.md](UX_UI_BRAINSTORM.md); read that first for the full
idea set and the reasoning behind what got included or excluded here. This
document narrows that list to a concrete, implementable plan and makes an
explicit recommendation on every open question the brainstorm raised, so
the project owner can approve, amend, or veto at the level of a single
decision rather than rewriting from scratch. Once approved, this becomes
the basis for actual implementation, at which point it graduates into the
normal `CLAUDE.md` documentation-upkeep discipline (see
[§8](#8-documentation-impact-once-implemented)) rather than staying a
standalone file.

## 1. Purpose, scope, and the governing constraint

**Goal:** make the frontend look and function like a deliberately designed
product — oriented, styled, responsive — without reversing any of the
architectural decisions in `docs/DEVELOPMENT_DOCUMENTATION.md` §7 or
breaking the Selenium E2E contract (`app/tests/E2E/`).

**Non-goals** (excluded per the brainstorm's scope-creep flags): no
client-side router, no network-backed autocomplete, no new case narrative
content/data-model change, no progress-tracking or gamification, no change
to evaluation logic or explanation wording, no UI framework/component
library adoption (see [§2.1](#21-styling-approach-recommended-decision)
for the recommendation and why it stays dependency-free).

**Governing constraint:** every component spec below states explicitly
which existing E2E selector/text it must preserve, and which test (if any)
must change in the same commit. This is not optional cleanup — silently
changing a selector the Selenium suite depends on is exactly the kind of
regression that would only surface once someone runs the full suite (or
worse, only in CI), which is preventable by treating it as part of the
spec instead of an afterthought.

## 2. Design system foundation

### 2.1 Styling approach (recommended decision)

**Recommendation: plain CSS custom properties, no new dependency.**
Extend the existing hand-written `App.css` with a small `:root` token
block (colours, spacing, type scale, radii) rather than adopting a CSS
framework or component library. Rationale: zero new build dependencies
(keeps `app/frontend/package.json` exactly as-is), consistent with the
project's existing "add complexity only where the workflow needs it"
philosophy (§7), trivial for a thesis committee to read and audit, and
entirely sufficient for an app with three views and no design system reuse
outside itself. If the project owner would rather adopt something (e.g.
Tailwind for utility classes), that is a real option — it's flagged here
as a decision, not assumed, because it would need a
`DEVELOPMENT_DOCUMENTATION.md` rationale entry as a technology choice, not
just a styling tweak.

### 2.2 Colour tokens

A small palette layered on top of the three existing semantic colours
(kept, not replaced, to avoid re-litigating `RULE`/`EVID`-motivated
feedback-colour choices already documented in §7):

```css
:root {
  /* Brand / neutral */
  --color-brand: #2f5d8a;       /* primary actions, links, active states */
  --color-brand-contrast: #ffffff;
  --color-surface: #ffffff;
  --color-surface-alt: #f4f6f8; /* case cards, code-list background */
  --color-border: #d7dce1;
  --color-text: #1c1f23;
  --color-text-muted: #5a6472;

  /* Semantic (existing meaning, refined values) */
  --color-correct: #1e8449;
  --color-suboptimal: #b9770e;
  --color-incorrect: #c0392b;
}

@media (prefers-color-scheme: dark) {
  :root {
    --color-brand: #6ea8dd;
    --color-brand-contrast: #0b1220;
    --color-surface: #14181d;
    --color-surface-alt: #1c2127;
    --color-border: #2c333b;
    --color-text: #e8ebee;
    --color-text-muted: #a3adb8;

    --color-correct: #4fbf78;
    --color-suboptimal: #e0a13a;
    --color-incorrect: #e5695c;
  }
}
```

All pairs above meet WCAG AA (4.5:1) for text-on-surface at these values;
verify again once final values are picked (a contrast checker run against
the actual shipped values is part of [§7](#7-accessibility-requirements),
not assumed from this draft).

### 2.3 Typography scale

Keep `system-ui` (no webfont dependency, matches the existing
`font-family` choice and avoids a new network request):

```css
:root {
  --font-size-h1: 1.75rem;
  --font-size-h2: 1.375rem;
  --font-size-body: 1rem;
  --font-size-small: 0.875rem;
  --line-height-body: 1.5;
}
```

### 2.4 Spacing, radius, motion tokens

```css
:root {
  --space-1: 0.25rem;
  --space-2: 0.5rem;
  --space-3: 0.75rem;
  --space-4: 1rem;
  --space-6: 1.5rem;
  --space-8: 2rem;

  --radius-sm: 0.375rem;
  --radius-md: 0.75rem;

  --shadow-card: 0 1px 3px rgba(0, 0, 0, 0.08);

  --motion-duration: 150ms;
  --motion-easing: ease-out;
}

@media (prefers-reduced-motion: reduce) {
  :root {
    --motion-duration: 0ms;
  }
}
```

### 2.5 Iconography

Small inline SVGs, hand-authored or from a permissively-licensed set
(e.g. Lucide/Heroicons — MIT-licensed, copy only the specific `<svg>`
markup needed rather than adding the package as a dependency). Needed for:
setting badges (inpatient/outpatient), the three result classes
(check/warning/cross), and the tutorial trigger (`?`). No icon-font, no
new runtime dependency — icons are static markup, not a loaded asset.

## 3. Component specifications

For each component: what changes, what's preserved, and the exact E2E
impact.

### 3.1 App shell

New minimal header: app name/wordmark (plain text is fine — no logo
asset needed) and the tutorial re-entry button (`?`). Disclaimer text and
meaning stay exactly as-is (`REQ-SCP-02` requires the content, not
specific markup) but move into the header area so it's consistently
visible rather than only atop the case list.

**E2E impact:** none — no existing selector targets the disclaimer or a
header, since neither currently exists as a distinct element.

### 3.2 Tutorial / onboarding

A dismissible modal, auto-shown on first visit (`localStorage` flag,
e.g. `icd10_tutorial_seen`), re-openable via the header's `?` button.
Content: four steps mirroring `docs/USER_GUIDE.tex` §"Using the
prototype" verbatim in structure (choose a case → review the case facts →
search and submit a code → interpret the feedback), written in the app's
own voice rather than copied sentence-for-sentence.

Required behaviour, not optional polish:
- Traps focus while open; `Escape` closes it; closing returns focus to the
  element that opened it (the `?` button, or nothing on first auto-show).
- Rendered via a plain conditional in `App.jsx` state — no portal/dialog
  library dependency needed for a single modal.

**E2E impact:** none of the existing tests interact with a tutorial (it
doesn't exist yet), so no existing selector is at risk. But the *new*
modal must not intercept focus/clicks needed by `openCaseList()`'s wait
condition — on first visit in a fresh browser profile (which is what
Selenium provides), the modal will auto-show and must not block the
`.case-list` element from being present/interactable. Recommend either
(a) suppressing the auto-show via an env-detectable flag for the E2E
suite, or (b) having `SeleniumTestCase::openCaseList()` explicitly dismiss
the modal if present before proceeding. (b) is preferable — it exercises
the real first-visit path instead of special-casing test mode.

### 3.3 Case list → case cards

Replace the plain `<button>` list with a card grid. Each card must
contain, in this order of visual priority:
1. **Primary heading:** `case.short_description` (already returned by the
   API, no backend change).
2. **Secondary badges:** `case.case_id`, encounter setting (with icon),
   diagnosis role.

Grid: single column below 640px, 2 columns 640-1024px, up to 3 above
1024px (`grid-template-columns: repeat(auto-fill, minmax(18rem, 1fr))`
achieves this without hand-written breakpoints).

**E2E impact — this is the component with the real contract to honour:**
- `.case-list` class must remain on the containing element
  (`SeleniumTestCase::openCaseList()` waits on it).
- Each case's clickable element must remain a `<button>` (or the test's
  `WebDriverBy::cssSelector('.case-list button')` in
  `caseListButtonLabels()` must be updated to match whatever it becomes —
  recommend keeping `<button>` as the card's root interactive element
  rather than changing the test).
- The rendered text of each card's button must still contain the literal
  `case_id` string somewhere (`openCase()`'s
  `//button[contains(., '$caseId')]` xpath, and
  `VerificationOnlyCaseVisibilityTest`'s substring assertions on
  `CASE-004`/`CASE-008`). Putting `case_id` in a visible secondary badge
  (as specified above) satisfies this without any test change.

### 3.4 Case detail

- **Facts panel:** keep the existing `<dl>` semantics (already
  accessible), restyle as a small card with an icon per fact.
- **Code search + selection:** visual polish only, per the brainstorm —
  larger touch targets, visible hover/focus/selected states on the
  label/radio row, a "no results" message when the filter matches
  nothing, and a small persistent "Selected: `<code>` — `<designation>`"
  line above the submit button once a scrollable list can hide the
  selected row.
- **Submit action:** sticky to the bottom of the viewport on mobile
  (`position: sticky; bottom: 0`) so it's reachable without scrolling
  back up through a long code list.

**E2E impact:**
- `#code-search` id must remain exactly (`searchAndSubmitCode()` targets
  it directly).
- `.code-list` class must remain on the list container (waited on in
  `openCase()`).
- Radios must remain real `<input type="radio">` elements with
  `value="<code>"` (targeted via
  `input[type='radio'][value='$code']`) — the "selected code" summary
  line is additive UI, not a replacement for the radio semantics.
- The submit button's visible text must remain **exactly** `Submit code`
  (`//button[text()='Submit code']` is an exact-match xpath, not
  `contains`). An icon with no text content alongside the label is safe;
  changing the label text or appending anything (e.g. "Submit code →")
  is not, unless the test is updated in the same change.

### 3.5 Result view

- Heading keeps its exact text (`Correct`/`Suboptimal`/`Incorrect`) and
  colour-coding; add an icon (check/warning/cross) beside it as a third,
  redundant signal — strengthens, not weakens, the "colour is never the
  only signal" principle from §7.
- Explanation paragraph stays the first `<p>` inside the result
  `<section>` — **do not** insert any other `<p>` before it (icons, badges,
  etc. must be non-`<p>` elements, or the explanation must move to a more
  specific selector and the test updated alongside).
- Improvement suggestion becomes a distinct callout box, same underlying
  text content and same `.improvement` class name.
- Optional fade/slide-in on reveal, respecting
  `prefers-reduced-motion` via the `--motion-duration` token from
  [§2.4](#24-spacing-radius-motion-tokens).

**E2E impact:**
- `.result-heading` class and its exact text content must remain
  (`waitForResultHeading()`).
- `section p` (first paragraph in the section) must remain the
  explanation text (`resultExplanationText()`) — see constraint above.
- `.improvement` class and its text content must remain
  (`improvementText()`).

### 3.6 Loading / error / empty states

- Case list loading: skeleton cards (3-4 greyed placeholder cards) instead
  of "Loading cases…" text.
- Case list load failure: keep the existing `.error`-classed message
  (used by CSS, not asserted by any E2E test) but make the copy more
  actionable for a demonstrator audience (e.g. hint that the backend/DB
  containers may not be running).
- Code search with no matches: an explicit "No codes match “`<term>`”"
  message inside `.code-list` rather than silently rendering nothing.
- `not_evaluated` result state: unchanged in substance, restyled
  consistently with the rest of the result view.

**E2E impact:** none of these paths are currently asserted by name/class
in the E2E suite beyond what's already covered above.

## 4. Responsive breakpoints

| Range | Behaviour |
|---|---|
| `< 640px` | Single-column case grid; sticky submit button in case detail; header collapses disclaimer text to a shorter form if needed (same meaning, shorter phrasing is acceptable per `REQ-SCP-02`). |
| `640px – 1024px` | 2-column case grid; non-sticky submit button (viewport tall enough in practice, but sticky positioning is harmless to leave on — recommend leaving it on at all widths for consistency rather than conditionally removing it). |
| `> 1024px` | Up to 3-column case grid; otherwise same as tablet. |

Achieved via `grid-template-columns: repeat(auto-fill, minmax(18rem, 1fr))`
plus a max content width, not hand-authored breakpoint-by-breakpoint
column counts — simpler and naturally covers in-between widths.

## 5. Naming & data

Case naming uses `case.short_description`, already present in every
`/api/cases` and `/api/cases/{id}` response
(`app/src/Http/CaseController.php::summarize()`). **No backend, database,
or baseline change of any kind is required** for this specification — it
is a frontend-only consumption of already-returned data. Richer narrative
content beyond this field is explicitly out of scope here per the
brainstorm's scope-creep flag; revisit only as a separate, deliberately
scoped follow-on if the project owner wants it.

## 6. Implementation phasing

Sequenced so each phase leaves the app in a shippable, fully-tested state
— not a big-bang rewrite:

1. **Design tokens** (§2) — add the `:root` custom-property block to
   `App.css`; no visible change yet beyond whatever inherits the new
   tokens incidentally. Zero E2E risk.
2. **Case list & naming** (§3.3) — highest visibility, lowest risk, most
   self-contained. Update `VerificationOnlyCaseVisibilityTest` /
   `LearnerWorkflowTest` selectors only if the card's root element stops
   being a `<button>` (not recommended — keep it a button).
3. **Case detail & code selection** (§3.4).
4. **Result view** (§3.5).
5. **Tutorial/onboarding** (§3.2) — deliberately after the other screens
   exist in their new form, so its content matches what's actually on
   screen.
6. **Responsive + accessibility QA pass** (§4, §7) — a dedicated pass over
   everything above, not a bolt-on; run the full Selenium suite plus a
   manual mobile-viewport check as the exit criterion for this phase.

Each phase should end with `vendor/bin/phpunit --testsuite e2e` green
against the changed frontend (see `app/tests/E2E/README.md` for bringing
up Selenium locally) before moving to the next phase — catching a broken
selector one phase late is much cheaper than catching it after all six.

## 7. Accessibility requirements

Non-negotiable, not aspirational:

- Every interactive element has a visible `:focus-visible` style (the
  redesign must not rely on browser defaults disappearing silently under
  new CSS resets).
- The tutorial modal traps focus and closes on `Escape`
  ([§3.2](#32-tutorial--onboarding)).
- An `aria-live="polite"` region announces the result view appearing and
  list-load errors, so a screen-reader user isn't left waiting silently.
- Colour contrast at WCAG AA (4.5:1 body text, 3:1 large text/icons)
  verified against the *actual* shipped token values, both light and dark
  — not just the draft values in [§2.2](#22-colour-tokens).
- Heading hierarchy stays logical (`h1` app-level, `h2` per view) even
  with the new header — currently each view's own `<h1>`/`<h2>` would
  need adjusting once a persistent header exists.
- `prefers-reduced-motion` disables all new transitions/animations via the
  `--motion-duration` token, not per-animation special-casing.

## 8. Documentation impact (once implemented)

Per `CLAUDE.md`'s documentation-upkeep table, implementing this (in
whichever phased slices the project owner approves) will require, in the
same turn as each slice:

- `docs/CHANGELOG.md` — dated entry per phase (or per session, if a
  session covers multiple phases).
- `docs/DEVELOPMENT_DOCUMENTATION.md` §7 — **revised**, not just appended:
  the existing bullets about UI simplicity need updating to reflect what
  changed and, importantly, to keep stating *why* the underlying
  decisions (no router, no autocomplete, colour-is-not-the-only-signal)
  still hold despite the visual redesign.
- `docs/IMPLEMENTATION_SPECIFICATION.md` — frontend section needs the new
  component structure, design tokens, and any new frontend files.
- `docs/USER_GUIDE.tex`/`.pdf` — screenshots and any workflow-affecting
  text (e.g. mentioning the in-app tutorial) need refreshing; recompile
  per `docs/README.md`'s rebuild instructions.
- `app/tests/E2E/` — updated in lockstep with any component whose
  selector contract changes, per [§3](#3-component-specifications) above,
  not as a follow-up cleanup pass.
- This file and `UX_UI_BRAINSTORM.md` — once fully implemented, fold the
  "what we actually built and why" into §7 of
  `DEVELOPMENT_DOCUMENTATION.md` and consider these two files historical
  (kept for the record, like `chapter3_reference_case_coverage_plan.md`'s
  role for the case baseline, rather than deleted).

## 9. Open decisions requiring explicit sign-off

Carried from the brainstorm's question list, with this document's default
if not overridden:

| # | Decision | This spec's default |
|---|---|---|
| 1 | Case naming: `short_description` as-is vs. new richer narrative content | `short_description` as-is (§5) — no data/baseline change |
| 2 | Styling approach: plain CSS vs. framework/library | Plain CSS custom properties, no new dependency (§2.1) |
| 3 | Dark mode: explicit design attention vs. "don't break existing native support" | Explicit token-level support (§2.2), since it's nearly free once a palette exists anyway |
| 4 | Tutorial shape: auto-shown modal vs. contextual hints vs. both | Auto-shown modal + persistent re-entry button (§3.2); contextual hints not included by default |
| 5 | Progress/gamification appetite | Out of scope (per brainstorm's scope-creep flag) |

Everything else in this document is a default, not a locked decision —
the project owner's review is expected to add, amend, or strike items
freely before any implementation begins.
