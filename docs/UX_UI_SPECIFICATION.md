# UX/UI stretch-goal specification

**Status: implemented and verified, 2026-08-08 — this file is now
historical.** Per §8 below, its conclusions have been folded into
`docs/DEVELOPMENT_DOCUMENTATION.md` §7 (current rationale) and
`docs/IMPLEMENTATION_SPECIFICATION.md` §5 (current structure); read those
for the living version. Kept on disk as the planning record, the same
role `chapter3_reference_case_coverage_plan.md` plays for the case
baseline. One item this redesign surfaced but deliberately did not
resolve, since fixing it would cross the case-data boundary this document
holds throughout: `CASE-003`/`005`/`006`/`007`'s single-code response
domains make for a trivial "choice" once the case list names each case via
`short_description` — see `HANDOFF.md` §8 for where that stands.

**Status when approved, 2026-08-08:** Derived from
[UX_UI_BRAINSTORM.md](UX_UI_BRAINSTORM.md); read that first for the full
idea set and the reasoning behind what got included or excluded. The
project owner reviewed both documents and made one scope call: **no new
case narrative content or data-model change**, full stop — everything else
originally proposed as a non-goal here (a client-side router,
autocomplete-style search, a UI framework, and — the one explicitly
upgraded from "flag for veto" to "essential" — a lightweight gamification
layer) is in scope, provided it stays frontend-only (plus test updates);
the backend, rule engine, and data layer are the hard limit. §1 and §9
below are updated to reflect that decision directly; the rest of this
document's per-component specifications already anticipated it (they were
always written to be additive on top of, not blocked by, that eventual
answer). This is now the basis for actual implementation, which graduates
it into the normal `CLAUDE.md` documentation-upkeep discipline (see
[§8](#8-documentation-impact-once-implemented)) rather than staying a
standalone planning file once each phase lands.

## 1. Purpose, scope, and the governing constraint

**Goal:** make the frontend look and function like a deliberately designed
product — oriented, styled, responsive, with a lightweight sense of
progress — without reversing the architectural decisions in
`docs/DEVELOPMENT_DOCUMENTATION.md` §7 that still hold (feedback keeps
colour+text+now-icon redundancy; the client never receives enough
information to reveal the answer key; the disclaimer stays first-visible)
or breaking the Selenium E2E contract (`app/tests/E2E/`).

**The one remaining non-goal:** no new case narrative content and no
data-model change of any kind — `short_description` (already returned by
the API) is the naming source, unchanged (see [§5](#5-naming--data)).

**Explicitly in scope, per the project owner's 2026-08-08 decision** (all
frontend-only, plus test updates where needed): a gamification layer,
deliberately kept small *by instruction*, not by default
([§3.7](#37-gamification-progress-layer));
a UI framework/library, if and where it earns its keep (still defaulting to
plain CSS on engineering merits, not because it's disallowed —
[§2.1](#21-styling-approach-recommended-decision)); autocomplete-style
search polish over the existing client-side filter
([§3.4](#34-case-detail)); and a client-side router, which
[§2.6](#26-client-side-routing-reconsidered-still-declined) explains was
reconsidered and still not adopted, on its own merits rather than because
it was off the table.

**Governing constraint:** every component spec below states explicitly
which existing E2E selector/text it must preserve, and which test (if any)
must change in the same commit. This is not optional cleanup — silently
changing a selector the Selenium suite depends on is exactly the kind of
regression that would only surface once someone runs the full suite (or
worse, only in CI), which is preventable by treating it as part of the
spec instead of an afterthought. The same discipline now also applies to
[§3.7](#37-gamification-progress-layer)'s new behaviour: it gets its own
E2E coverage, not a hope that manual testing catches it.

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
(check/warning/cross), the tutorial trigger (`?`), and the four
gamification progress states ([§3.7](#37-gamification-progress-layer)).
No icon-font, no new runtime dependency — icons are static markup, not a
loaded asset.

### 2.6 Client-side routing (reconsidered, still declined)

The project owner's 2026-08-08 decision explicitly lifted the "no router"
restriction — it's permitted, not just tolerated. Reconsidered on that
basis and still not adopted, for reasons independent of whether it was
allowed:

- The workflow stays exactly as linear as before (case list → detail →
  result); gamification ([§3.7](#37-gamification-progress-layer)) is shown
  entirely within the existing case list view and needs no route of its
  own — a "your progress" indicator is not a "your progress" *page*.
- Introducing `react-router` (or hand-rolled `history`/`popstate` handling)
  changes real browser behaviour — back-button semantics, URL structure —
  for no capability anyone has asked for (no deep-linking/bookmarking
  requirement exists anywhere in the requirements catalogue).
- It's a new runtime dependency and a real
  `DEVELOPMENT_DOCUMENTATION.md`-grade decision that would need its own
  rationale entry for zero concrete benefit identified so far.

**This is a call made on engineering merits, flagged here explicitly so it
reads as a considered decision rather than an oversight** — if the project
owner disagrees (e.g., wants bookmarkable case URLs for demonstration
purposes), that's a one-line override, not a rework of anything else in
this specification, since nothing else here depends on which way this
goes.

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

### 3.7 Gamification / progress layer

Per the project owner's instruction: "essential... but not an elaborate
concept." Concretely, that means tracking *attempt state per case* and
reflecting it back on the case list — nothing that needs a backend,
an account, or persistence beyond one browser.

**Mechanism — entirely client-side, zero data-layer change:**

- New module `app/frontend/src/lib/progress.js`. Reads/writes a single
  `localStorage` key (`icd10_progress_v1`) holding
  `{ [case_id]: { attempts: number, lastClassification: 'correct' |
  'suboptimal' | 'incorrect' } }`.
- Written once per submission, from the same place `App.jsx` already
  handles the `evaluate()` response — no new network call, no change to
  `api.js` or any backend endpoint.
- Versioned key name (`_v1`) so a future shape change doesn't need to
  migrate old data — it can just be ignored/reset, which is an acceptable
  cost for a prototype with no real user data at stake.

**What the learner sees:**

- **Case list:** each card gets a small badge reflecting
  `lastClassification` for that `case_id` — empty/neutral "Not attempted
  yet" state, or the same check/warning/cross icon + colour + text used on
  the result view ([§3.5](#35-result-view)), so the vocabulary is
  consistent across the whole app rather than a second iconography for
  "progress."
- **List header:** a one-line summary, "`<attempted>` of `<total>` cases
  attempted" (`total` = the learner-visible case count from `/api/cases`,
  currently 6).
- **Completion state:** when every learner-visible case has
  `lastClassification === 'correct'`, a small dismissible banner
  acknowledges it once (not a modal, not a repeated nag — reappears on
  next full page load rather than persisting a "dismissed forever" flag,
  since there's no cost to seeing it again on a fresh visit). No score, no
  streak counter, no leaderboard — explicitly excluded per "not an
  elaborate concept."
- Resetting progress is a side effect of clearing browser storage
  (private/incognito windows start fresh); no in-app "reset progress"
  control is included in this pass — trivial to add later if wanted, but
  not asked for.

**Markup contract (for both styling and the new E2E test below):** each
case card carries `data-case-id="<case_id>"`; its progress badge carries
`data-progress-status="not_attempted|correct|suboptimal|incorrect"` and
visible text matching one of `Not attempted` / `Correct` / `Suboptimal` /
`Incorrect` (same vocabulary as `CLASS_LABELS` in `App.jsx` today).

**E2E impact — this is new test surface, not a preserved contract:**
add a case to `LearnerWorkflowTest` (or a small new test class) that:
submits a code for `CASE-001`, navigates back to the case list, and
asserts `[data-case-id="CASE-001"] [data-progress-status]` now reads the
expected class — proving the localStorage round-trip actually reaches the
DOM, not just that the module's unit logic is correct. This is frontend
behaviour with no backend equivalent, so it has no `TEST-*` upstream
identifier; document it in `docs/IMPLEMENTATION_SPECIFICATION.md` §7 as
frontend-only supplementary coverage, not a new formal `TEST-*` catalogue
entry (that catalogue is upstream-controlled per `chapter3_test_catalogue.md`
and out of scope to extend for a presentation-layer feature).

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
2. **Case list, naming & gamification badges** (§3.3, §3.7) — highest
   visibility, lowest risk, most self-contained; bundled together because
   the progress badges live on the same cards. Update
   `VerificationOnlyCaseVisibilityTest` / `LearnerWorkflowTest` selectors
   only if the card's root element stops being a `<button>` (not
   recommended — keep it a button); add the new progress-badge E2E
   coverage from §3.7 here, not deferred to a later phase.
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

## 9. Decisions (resolved 2026-08-08)

Carried from the brainstorm's question list. All five are now resolved;
kept in table form for the record rather than deleted.

| # | Decision | Resolution |
|---|---|---|
| 1 | Case naming: `short_description` as-is vs. new richer narrative content | **Resolved: `short_description` as-is (§5) — no data/baseline change, the one hard non-goal.** |
| 2 | Styling approach: plain CSS vs. framework/library | **Resolved: permitted either way; plain CSS custom properties chosen on engineering merit (§2.1), not because a framework was disallowed.** |
| 3 | Dark mode: explicit design attention vs. "don't break existing native support" | Not separately addressed in the reply — this spec's default (explicit token-level support, §2.2) stands. |
| 4 | Tutorial shape: auto-shown modal vs. contextual hints vs. both | Not separately addressed in the reply — this spec's default (auto-shown modal + persistent re-entry button, §3.2) stands. |
| 5 | Progress/gamification appetite | **Resolved: in scope, and explicitly "regarded as essential for the successful completion of the implementation" — deliberately not an elaborate concept (§3.7).** |

Everything not listed above remains a default rather than a locked
decision — still open to amendment on request, but nothing here is
blocking implementation from proceeding.
