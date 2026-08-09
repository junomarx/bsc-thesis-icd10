# Development documentation

**Scope:** the implementation phase of the Austrian ICD-10 educational
prototype, from the original one-case/one-question build
(`PROTOBASE-0.1`/`0.2`) through the 8-9 August 2026 forward redesign to a
patient/question model (`PROTOBASE-0.3`, current) and its downstream
application layer. Sections 5-6 and 9 describe the original implementation
and predate the redesign — each is marked; the current architecture is
`IMPLEMENTATION_SPECIFICATION.md` plus §13-17 below.
**Companion documents:** [IMPLEMENTATION_SPECIFICATION.md](IMPLEMENTATION_SPECIFICATION.md)
(what was built, precisely) and [CHANGELOG.md](CHANGELOG.md) (when it changed).
**Upstream authority:** the `chapter3_*.md` control artefacts at the
repository root. This document explains *decisions made while implementing*
those artefacts; it does not restate their content and must not be read as
superseding them.

## 1. How to read this document

Each section pairs a decision with its rationale, and — where applicable —
the requirement, rule, or piece of research evidence that motivated it,
using the project's existing identifier vocabulary (`REQ-*`, `RULE-*`,
`PAT-*`, `EVID-*`, `TEST-*`). A decision without a cited source is an
implementation judgement call, not an Austrian coding claim or an externally
imposed requirement; Section 3 of the requirements catalogue's authority
model applies here just as it does upstream.

## 2. Design Science Research framing

The project's evidence register already commits to a DSR framing
(`EVID-DSR-01` Hevner 2004, `EVID-DSR-02` Peffers 2007 DSRM,
`EVID-EVAL-01` Venable 2016 FEDS). This section makes explicit how the
implementation phase maps onto that framing, so the thesis's methods
chapter can cite concrete artefacts rather than assert the mapping abstractly.

### 2.1 DSRM phase mapping

| DSRM phase (Peffers 2007) | Concrete artefact in this repository |
|---|---|
| Problem identification & motivation | `archived/development_handoff/handoff/ICD_PROTOTYPE_DEVELOPMENT_BRIEF.md` §1–2; the research question in that brief |
| Objectives of a solution | `chapter3_requirements_catalogue.md` (`REQ-*`) |
| Design & development | `chapter3_rule_catalogue.md`, `chapter3_data_model_and_interaction_baseline.md` (upstream design) **plus this document and `app/`** (the realized build) |
| Demonstration | The running Docker Compose stack (`prototype_stack/`) serving the learner workflow described in §7 below; the browser walkthrough recorded in [CHANGELOG.md](CHANGELOG.md) |
| Evaluation | The technical test suite (`app/tests/`, `prototype_baseline/persistence_candidate/test_*_0_2.py`) — see §9 |
| Communication | The thesis document itself; this documentation set is written to be liftable into it |

### 2.2 Evaluation-strategy boundary

Per `EVID-EVAL-01` (Venable's FEDS framework), evaluation strategies vary
along two axes: *artificial vs. naturalistic* and *formative vs. summative*.
Everything implemented and executed so far sits at the **artificial,
formative** end: unit tests against synthetic fixtures, integration tests
against a controlled database state, and reference-response conformance
against a predefined oracle. No naturalistic evaluation (real learners using
the tool) and no summative claim about learning outcomes has been attempted
or is in scope (`REQ-SCP-03`). This document and its companions describe
*what was built and how it was technically verified*; they make no claim
about pedagogical effectiveness.

## 3. Governing principles carried into the implementation

These are not new decisions — they restate, at the code level, principles
already fixed upstream, because they visibly shaped file/module boundaries
rather than staying abstract:

- **Authority boundary.** Austrian sources decide coding facts; research
  evidence decides design/feedback/testing method; internal/project
  decisions decide scope and software architecture. No PHP class is allowed
  to become a *de facto* fourth authority by inventing a rule the upstream
  catalogue doesn't state.
- **Traceability.** Every rule class, every test file, and every API field
  name that has an upstream identifier keeps it in a docblock or test name
  (`RULE-STATUS-01`, `TEST-MAP-01`, etc.), so `grep`-ing an identifier from
  the thesis text finds the implementing code.
- **Determinism first.** The rule engine is written as a set of pure(ish)
  functions over explicit value objects specifically so that "same input →
  same output" (`REQ-ARC-02`) is a property of the code's shape, not
  something bolted on with caching or locking.
- **No invented authority.** The implementation must never manufacture a
  classification the specification doesn't define. This shows up concretely
  as the `SpecificationGapException` described in §5.4 — an eligible
  relation that reaches no terminal rule throws loudly instead of quietly
  becoming `incorrect`.

## 4. Technology stack and rationale

`REQ-IMP-01` fixes the stack (React, PHP, MySQL, Python, Docker Compose) as
a project constraint, not a free choice. The decisions below are about *how*
to use that fixed stack, not *whether* to use it.

| Layer | Technology (pinned version) | Why this, specifically |
|---|---|---|
| Backend language/runtime | PHP 8.4, no framework | The rule engine and repositories are simple enough that a framework (Laravel/Symfony) would add routing/DI/ORM machinery the project doesn't need, while making the "evaluator must be testable without the UI and without hidden magic" requirement (`REQ-ARC-01`) harder to *see* in the code. Constructor-promoted readonly classes give the value-object immutability the rule engine wants without a library. |
| Persistence | MySQL, deliberately unpinned below the major version (`mysql:latest` in `compose.yaml`) | The exact minor/patch is not treated as a real dependency — only the CHECK-constraint floor (MySQL ≥ 8.0.16) matters for the schema in use. Pinning an exact patch during development would box the project into a version for no functional reason; `REQ-CFG-01` pins an exact resolved version only at the eventual evaluation freeze. See §10.1 for how this decision evolved. |
| DB access | Raw PDO, prepared statements, no ORM | An ORM would sit between the code and the exact SQL that `TEST-DAT-02`/`TEST-ARC-01` need to reason about (e.g. "does this schema contain an `expected_class` column anywhere"). Repositories hand-map rows to small value objects instead. |
| Frontend | React 19 + Vite 8 | React is the fixed constraint; Vite was chosen over Create React App / a meta-framework (Next.js) because the learner UI is a single view with no routing, no SSR, and no data-fetching-framework needs — Vite's dev server and static build are the smallest thing that satisfies "compile React, then serve the static output from Apache" (brief §17). |
| Frontend package management | npm | Comes with Node; no reason to add pnpm/yarn for a project this size. |
| Data preparation | Python (candidate scripts, adopted unchanged) | Already existed as `prototype_baseline_0_1/scripts/*.py` and passed its own structural checks against the frozen source; rewriting it would have added risk without benefit. See [CHANGELOG.md](CHANGELOG.md) for the adoption/verification step. |
| Containerisation | Docker Compose, multi-stage `Dockerfile` | Node is used only in a build stage that produces static assets; the runtime image runs Apache + PHP only, so there is no permanent Node service to secure, update, or explain in the thesis (brief §17). |
| Backend testing | PHPUnit 11.5, attribute-based data providers | The version in `composer.lock`; attributes (`#[DataProvider(...)]`) were used instead of the older `@dataProvider` doc-comment form because the doc-comment form is deprecated as of PHPUnit 11 and would print noise on every run. |
| Browser-driven testing | **Selenium**, going forward (see §10.3) | Initial end-to-end verification (documented in [CHANGELOG.md](CHANGELOG.md)) used Playwright; the project owner subsequently specified Selenium as the standing choice for all future system/integration/regression browser tests. Playwright's dev-dependency and browser cache were removed once that was clear, rather than left as unused, contradictory tooling. |

## 5. Architectural decisions

**Historical — describes the original one-case/one-question implementation
(`RULEBASE-0.1`, `SUBSET-0.1`, `RCBASE-0.2`, `CaseFacts`), predates the 8-9
August 2026 forward redesign (§13).** Kept verbatim because the *decisions*
below mostly still hold in the current codebase, only under different
names: `Precedence` is still its own class (now over an array of graded
matches, not a bool); explanations are still a flat keyed array; a
specification gap is still an exception, not a return value
(`SpecificationGapException` is unchanged); the oracle is still isolated at
all three levels described in §5.5, now against `RULEBASE-0.2`/`RCBASE-0.3`
and `mysql_schema_0_2.sql`. Concrete class names, counts, and file paths
below are the *original* ones and will not `grep`-match the current
`app/src/` — see `IMPLEMENTATION_SPECIFICATION.md` for what actually exists
today.

### 5.1 Layering

```text
React (frontend/) — single-page, no client router
        |
        v  fetch() JSON
public/index.php — front controller, ~15-line match() dispatcher, no router library
        |
        v
Http/*Controller — HTTP-shape concerns only (status codes, validation of the
        |           request envelope); no SQL, no rule logic
        v
Repository/*      — PDO queries; hydrates Model/* value objects; no rule logic
        |
        v
Evaluation/Evaluator — orchestrates Rules/* in RULEBASE-0.1's fixed order;
        |               consumes only Model/* value objects, never touches PDO
        v
Rules/Rule*.php   — one class per RULE-*, each a static predicate over
                     value objects; no side effects, no I/O
```

Each arrow is also a testability boundary: `tests/Unit/*` exercises
`Rules/*` and `Evaluation/*` with hand-built fixtures and no database;
`tests/Integration/*` exercises everything from `Http/*` down against a real
MySQL instance. This mirrors `REQ-ARC-01`'s requirement that evaluation
logic, data, and presentation stay logically separate — the layering isn't
decorative, it's what makes that requirement checkable.

### 5.2 Why the precedence policy is its own class

`RULE-PREC-01` requires demonstrating rule-order independence and a
no-terminal specification gap using rule-match *combinations* that need not
actually occur in `SUBSET-0.1` (`TEST-PREC-01`, vectors PREC-E/F: all three
hard rules matching at once, in every input order). Testing that directly
against `Evaluator::evaluate()` would require fabricating a `CaseFacts`
object that simultaneously violates status, depth, and evidence rules —
implying a clinically incoherent case. Extracting the priority/terminal
policy into `Rules/Precedence.php` as a pure function over already-computed
booleans (`primaryHardRule(array $hardMatches)`,
`terminalClass($hardMatches, $specMatches, $acceptMatches)`) lets
`tests/Unit/PrecedenceTest.php` assert the policy directly, with no
synthetic medical claim attached to the test data. `Evaluator` still
performs its own real predicate evaluation for its actual return value;
`Precedence` is reused inside it so there is exactly one implementation of
the ordering rule, not two that could drift apart.

### 5.3 Why the explanation payload is a flat keyed array

`RCBASE-0.2`'s `required_explanation_elements` column lists element names
per row (e.g. `submitted_code|fev1_stable_pct_predicted|expected_code`).
Rather than hand-writing a bespoke assertion per rule branch,
`tests/Integration/ReferenceResponseTest.php` parses that column and checks
*generically* that every named key exists and is non-empty in
`explanation_elements`. This only works because every rule branch in
`Evaluation/Evaluator.php` builds its explanation as a flat associative
array using exactly the element names the catalogue already promised
(`RULEBASE-0.1` §7's per-rule table). The array is deliberately not a typed
DTO per rule: the oracle's vocabulary is the contract, and keeping the
payload as a plain array keeps that contract visible in one place instead
of scattered across per-rule classes.

### 5.4 Why an exception, not a return value, for the specification gap

`RULEBASE-0.1`'s evaluation algorithm ends with "return
`specification_gap`" for an eligible relation that matches no terminal rule
— explicitly *not* `incorrect`. Modelling this as a third
`EvaluationResult` state would make it just another value the API has to
route around, inviting an eventual `default: return incorrect`-shaped bug.
Instead, `Evaluator::evaluate()` throws `SpecificationGapException`, and
`EvaluationController` turns it into an HTTP 500 with an explicit
`specification_gap` error body. Given the current eight cases and eighteen
domain relations (including the four added by the pre-freeze coverage
review, §10.3) this branch is unreachable in practice (verified implicitly
by every other test passing); the exception exists so that if a future case
addition makes it reachable, it fails loudly during development rather than
silently shipping a wrong classification.

### 5.5 Why the runtime never imports the verification oracle — structurally, not by convention

`REQ-ARC-01`/`TEST-ARC-01` require that `RCBASE-0.2` stay outside the
runtime classification path. This is enforced at three independent levels
rather than one:

1. **Data level:** the Python loader (`runtime_data.py`) hard-codes a
   four-file allowlist (`RUNTIME_FILES`) with no filesystem discovery; the
   verification CSV is not one of the four files.
2. **Schema level:** `mysql_schema.sql` has no `expected_*`/`determining_rule`
   column anywhere, which `ArchitectureIsolationTest::testRuntimeSchemaHasNoExpectedOutputColumnsOrTables()`
   asserts against `information_schema` directly, so a future migration
   that quietly adds such a column fails a test rather than passing review
   by accident.
3. **Source level:** the same test's
   `testEvaluatorSourceCodeNeverReferencesTheVerificationOracle()` walks
   every file under `app/src/` and asserts none of them contain the strings
   `verification/reference_responses` or `RCBASE`. Only the *test harness*
   (`ReferenceResponseTest.php`, under `tests/`, excluded from that walk)
   is allowed to read the oracle.

Three independent, automatically checked barriers were chosen over one,
because the property being protected — "the classifier is not secretly
graded against its own answer key" — is exactly the kind of thing that is
easy to violate accidentally during a later refactor and hard to notice by
inspection alone.

## 6. Data model implementation decisions

**Historical — describes the original `MODELBASE-0.1` schema and its
`CaseFacts`/`CaseRepository` realization, fully superseded by
`MODELBASE-0.2`'s 9-table normalized schema (§13, `IMPLEMENTATION_SPECIFICATION.md`
§2).** The underlying principle (one repository per table, returning
immutable value objects so a rule predicate cannot silently depend on an
unpromoted column) is unchanged in the current `Repository/*`/`Model/*`
classes; the specific table/class names below are not.

The physical schema (`archived/prototype_baseline_0_1/mysql_schema.sql`) is a direct,
1:1 realization of `MODELBASE-0.1`'s four logical entities — no additional
tables, no denormalization, no caching layer. PHP-side, each table has
exactly one repository (`BaselineRepository`, `CatalogueRepository`,
`CaseRepository`) and each repository returns immutable value objects from
`Model/*` rather than raw associative arrays, so a rule predicate consuming
a `CaseFacts` object cannot accidentally depend on a database column that
was never promoted to the model (which would silently violate `REQ-MOD-01`).
`CaseFacts::$responseDomain` is the one place the model deviates from a
naive column-for-column mapping: it is stored as a `code => is_acceptable`
map rather than a separate list, because every rule that needs to know
"is this code even a defined relation for this case" and every rule that
needs "is this code in the accepted set" both want *the same* map, just
queried differently (`hasDefinedRelation()` vs. `isAcceptable()`).

## 7. UI design principles

The brief is explicit that UI sophistication is secondary to clarity and
determinism (`INT-SUP-03`); the implementation was initially built plainly
on that basis. Once the core prototype and its CI/deployment story were
done and independently verified, the project owner requested a stretch
goal — deliberate visual/UX polish and a lightweight gamification layer
— explicitly *before* the freeze, with schedule room to spare. That
request revisited several of the choices below on their merits (see the
`reconsidered` markers); none of the underlying reasoning was overridden
by assumption, and one hard boundary was set and held throughout: no
change to case data/content or the data model. Full brainstorm/decision
record: `docs/UX_UI_BRAINSTORM.md` and `docs/UX_UI_SPECIFICATION.md`
(both historical now that this is implemented; this section is the living
version of their conclusions).

- **No client-side router — reconsidered, still declined.** The learner
  workflow (`REQ-INT-01`) is a strictly linear case → code → feedback path
  with no requirement for bookmarkable URLs per case.
  `frontend/src/App.jsx` models this as three in-memory view states
  (`list`, `case`, `result`) rather than pulling in `react-router` for a
  workflow that never branches. The stretch-goal request explicitly lifted
  this restriction (frontend-only changes were broadly permitted); it was
  reconsidered on that basis and declined again anyway — the gamification
  progress summary lives inside the existing case-list view rather than a
  route of its own, and no deep-linking/bookmarking requirement exists
  anywhere in the requirements catalogue. A new dependency and real
  browser-history behaviour change for no identified capability gain
  wasn't justified by "now allowed" alone.
- **Feedback design follows the cited formative-feedback evidence, not
  intuition — now with a third redundant channel.** `EVID-FB-01` (Shute
  2008) and `EVID-FB-02` (Hattie & Timperley 2007) motivate elaborated,
  task-focused feedback over a bare right/wrong signal. `ResultView`
  always renders three things together: the class
  (`Correct`/`Suboptimal`/`Incorrect`), a task-focused explanation naming
  the concrete criterion that decided it, and — only when one exists — an
  explicit improvement target. Colour (green/amber/red heading) is a
  reinforcement of the text label, never the only signal, so the class is
  still legible without colour. The stretch-goal redesign added a
  check/warning/cross icon alongside the same heading — a *third*
  redundant signal (shape, independent of colour perception), not a
  replacement for either existing one. The same three-channel vocabulary
  (icon + colour + text) is reused for the new gamification progress
  badges below, so "what does this case card's badge mean" and "what does
  the result heading mean" share one visual language instead of two.
- **The intended-use boundary is stated persistently, not only on the
  landing view.** `REQ-SCP-02` requires that the "not for diagnosis /
  clinical decision support / official coding" boundary be stated in
  UI/disclaimer text, not just asserted in the thesis. Originally rendered
  only atop the case list; the stretch-goal redesign moved it into a
  persistent header (`components/Header.jsx`) shown above every view, not
  only the first one a learner sees — a strengthening of the same
  requirement, not a reinterpretation of it.
- **The searchable code list is deliberately a plain `<input>` + client-side
  filter over the case's already-fetched `supported_codes`, not a
  typeahead/autocomplete component — reconsidered, still the case.** The
  response domain per case tops out at six codes (`REQ-DAT-03`'s
  purposive, small subset), so a debounced network-backed autocomplete
  would be solving a scale problem this project does not have. The
  stretch-goal request explicitly permitted autocomplete-style search
  polish; what was actually built is visual polish over the same
  mechanism (larger touch targets, hover/selected states, a "no matches"
  message) rather than a network-backed component, since the underlying
  reasoning (six codes, already fetched) didn't change.
- **No accepted-set information ever reaches the client.** `QuestionController::render()`
  builds its `options` array from `question_option` (the displayed set)
  only; it never touches `question_code_domain`'s `relation_kind` rows (the
  accepted/less-specific/conflict vocabulary a question is actually
  evaluated against), so there is no accepted-set field to strip in the
  first place — the frontend never has enough information to reveal the
  answer key through, e.g., inspecting the network tab. This is a direct,
  UI-side extension of the runtime/oracle
  separation principle in §5.5 — the *learner's browser* is treated as
  no more trusted than the verification harness. The gamification layer
  below reads only the already-received `evaluate()` response and writes
  only to the browser's own `localStorage`; it does not add a new
  network call or change what the API returns, so this boundary is
  untouched by it.
- **Design tokens over a CSS framework.** The stretch-goal redesign added
  a `:root` custom-property palette/type/spacing/motion scale to
  `App.css` (light defaults plus a dark token override selected by the
  root `data-theme` attribute) rather than adopting a UI
  framework/component library. The project owner's instruction explicitly
  permitted either; plain CSS custom properties were chosen on their own
  merits — zero new build dependency, consistent with this project's
  general "add complexity only where the workflow needs it" posture, and
  fully sufficient for an app with three views and no design-system reuse
  outside itself.
- **An explicit theme preference, with the OS preference only as the
  initial default.** Relying solely on `prefers-color-scheme` supplied no
  in-app way to choose a different appearance. The compact `ThemeSwitch`
  now lives in the existing header-action cluster and uses a single
  sun/moon icon, accessible toggle state, and action title rather than
  another permanent text control. `lib/theme.js` resolves the first visit
  from the OS, then saves an explicit `light`/`dark` choice under the
  browser-only `icd10-prototype:theme` key and applies it before React
  mounts. Keeping the preference beside the locale/tutorial browser state,
  rather than adding a backend field or user model, matches its purely
  presentational scope. Selenium fixes a deterministic light starting
  value and verifies the dark roster/tutorial palette, reload persistence,
  and switching back, so CI does not depend on the browser host's theme.
- **Gamification layer — client-side only, deliberately small.** The
  project owner's stretch-goal instruction called a lightweight sense of
  progress "essential... but not an elaborate concept." In the current
  patient/question model this is a `sessionStorage` set of completed
  patient IDs, surfaced as patient-card badges and an aggregate completion
  line — no score, streak, leaderboard, backend call, or schema change.
  The earlier case-centric `localStorage`/`lib/progress.js` implementation
  was deleted with that model; §14.3 records why the current session-local
  design satisfies both `REQ-UI-02` and `REQ-INT-05`.
- **First-visit tutorial, not a permanent roster panel.** The case-centric
  app once had a `Tutorial.jsx`, but that component was deleted during the
  patient/question migration. Step 7 initially satisfied `REQ-UI-01` with
  a default-expanded `Orientation.jsx` block on the roster; in practice it
  occupied substantial space on every visit and only toggled one large
  body of text. The current `components/Tutorial.jsx` is a new implementation:
  four focused Back/Next steps (patient → dossier → answer → feedback),
  shown automatically only when a versioned `localStorage` flag is absent
  and manually reopenable from the persistent header on every view. It
  traps focus, closes on Escape or an explicit click/tap outside the dialog,
  restores trigger focus, and is covered by Selenium against the real
  first-visit path. This keeps the orientation
  content available while following the same "state it once, make it
  findable, don't nag" reasoning applied elsewhere.

## 8. Backend/API design principles

- **One request, one code, checked before anything else runs.**
  `EvaluationController::validateSubmittedCode()` rejects a missing,
  blank, or array-typed `submitted_code` *before* any case or catalogue
  lookup is attempted, directly implementing `REQ-RUL-05`'s requirement
  that malformed input never reach the classifier. An array is rejected
  outright rather than "helpfully" evaluating its first element — silently
  picking one code out of a list would misrepresent what the learner
  actually submitted.
- **Technical trace fields ship in every classified response, hidden or
  not.** `determining_rule` and `matched_rules` are always present in the
  JSON body (`REQ-FBK-01`'s requirement that the determining rule "remain
  available in the technical trace even if not shown to the learner"); it
  is the frontend's choice, not the API's, whether to render them. This
  keeps `TEST-RC-01` able to assert against the same endpoint a learner's
  browser calls, rather than needing a separate "verbose" API mode.
- **The evaluator has no HTTP awareness and the controller has no rule
  awareness.** `Evaluator::evaluate()` returns a plain
  `Evaluation\EvaluationResult` value object; `EvaluationController::render()`
  is the only place that maps it to a JSON shape and HTTP status. This
  split is what let `tests/Unit/*` test the rules with zero HTTP
  machinery and `tests/Integration/*` test the HTTP contract using the same
  underlying evaluator, instead of duplicating fixtures for each concern.

## 9. Testing & verification methodology, as implemented

This is a development-time account of what has actually been run; it is not
the principal verification record described in `chapter3_test_catalogue.md`
§11, which requires a frozen baseline this project has not yet reached (see
`archived/development_handoff/handoff/ICD_PROTOTYPE_DEVELOPMENT_BRIEF.md` §23.2). Exact counts and dates for each
run are in [CHANGELOG.md](CHANGELOG.md); this section describes the *shape*
of the testing approach.

| Layer | Tool | What it exercises | Needs a database? |
|---|---|---|---|
| Data/source structural checks | Python `unittest` (`prototype_baseline/scripts/prepare_subset_0_2.py --check-existing`, `persistence_candidate/test_runtime_contract_0_2.py`) | Frozen-source checksum, deterministic subset reproduction, oracle/runtime-model consistency | No |
| Persistence integration | Python `unittest` (`prototype_baseline/persistence_candidate/test_mysql_persistence_0_2.py`) | Live schema shape, row counts, FK enforcement, oracle-column absence | Yes (live MySQL) |
| Rule-engine unit tests | PHPUnit (`app/tests/Unit/*`, 77 tests) | Every `RULE-*` predicate and `Precedence` in isolation, against hand-built `CodingQuestion`/`QuestionFacts` fixtures (`Fixtures.php`) | No |
| Backend integration tests | PHPUnit (`app/tests/Integration/*`, 160 tests) | Repositories + evaluator + API together, including all 143 `RC-*` rows, determinism, and oracle isolation | Yes (live MySQL) |
| End-to-end / browser | Selenium via `php-webdriver/webdriver` (`app/tests/E2E/*`, 9 tests; Playwright was used once for the initial pass, then retired — §10.4/§10.5) | The actual React → PHP → MySQL path a learner would exercise | Yes (full stack + Selenium) |
| Container/orchestration | `docker compose build` / `up` / the bootstrap service's own test invocation | That the images build, the services start in the right order, and the bootstrap pipeline behaves idempotently against a *freshly created* compose-managed database | Yes (via Compose) |

Exact pass counts as of the last verified run: `IMPLEMENTATION_SPECIFICATION.md`
§7; dates and the runs that produced them: `CHANGELOG.md`.

A deliberate methodological point carried over from the upstream test
catalogue (`chapter3_test_catalogue.md` §2): the 143 `RC-*` reference rows
(125 new forward-model expectations plus 18 historical regression rows,
`docs/CHANGELOG.md`'s "Step 8"/"Step 9" entries) are not "just more unit
tests." `ReferenceResponseTest.php` sends only `question_id` and a tagged
response through the real HTTP-shaped controller and compares against an
oracle it reads once at test-collection time — it never becomes a runtime
dependency of the application it is testing.

## 10. Deviations, and why they are safe

Every deviation below is a considered substitution within a fixed
constraint, not a scope change; each is also logged with its exact date in
[CHANGELOG.md](CHANGELOG.md).

### 10.1 MySQL version pinning: exact patch, then relaxed to unpinned-minor

**Initial decision (superseded):** the development host only had MySQL 9.1
available locally, and the persistence test suite asserted the server
version starts with `8.4.8` (matching the version pinned in
`prototype_stack/compose.yaml`), so an exact-version Docker container was
used instead of the host install to keep that assertion meaningful.

**Revised decision (current):** the project owner judged that pinning an
exact MySQL patch version this early is an unnecessary constraint — nothing
in the schema or the rule engine depends on anything more specific than
CHECK-constraint support (MySQL ≥ 8.0.16). `compose.yaml` now specifies
`mysql:latest`, and `test_mysql_persistence.py`'s version assertion was
relaxed from an exact-prefix match to a major-version floor
(`assertGreaterEqual(major_version, 8, ...)`). This is a considered
trade-off, not carelessness: an unpinned tag means the exact version running
today may differ from the version running next month, but `REQ-CFG-01`
already anticipates pinning an *exact* resolved version only once, at the
eventual evaluation freeze — pinning twice (once now, arbitrarily, and once
at freeze, deliberately) would have added a constraint with no benefit in
between.

### 10.2 `Dockerfile` moved to the repository root for `stack.sh --sync` compatibility

**Initial approach (superseded):** the application was first built with
`app/Dockerfile` as its own build root, and `docker compose` was invoked
directly with `APP_SOURCE_DIR=../app`, bypassing `stack.sh sync`/`up`
entirely — `stack.sh` explicitly refuses an `APP_SOURCE_DIR` containing `..`
(by design, to keep a git-synced checkout confined to `prototype_stack/`),
so a subdirectory-rooted Dockerfile was structurally incompatible with
`stack.sh`'s own sync workflow.

**Revised approach (current):** the project owner's stated intention is to
configure `prototype_stack/config/git-source.conf` with this same
repository as `APP_GIT_URL`, so that `stack.sh sync` clones *this whole
repository* into `prototype_stack/.runtime/app` and `stack.sh up` builds it
as-is. `stack.sh`'s `doctor`/`up` commands hard-require a `Dockerfile` at
the checkout's root (`[ -f "$SOURCE_PATH/Dockerfile" ]`) — since the
checkout would then be a full clone of this repository, that root is the
actual repository root, not `app/`. The `Dockerfile` (and its
`.dockerignore`) were therefore moved from `app/Dockerfile` to `/Dockerfile`,
with every `COPY` instruction rewritten to reference `app/...` paths
relative to the new repo-root build context. `compose.yaml` itself needed no
change: `dockerfile: Dockerfile` under `context: ${APP_SOURCE_DIR:-.runtime/app}`
already expected exactly this layout — it was the *location of the
Dockerfile*, not the compose file, that was incompatible. Local
verification (without a git-synced checkout) now uses
`APP_SOURCE_DIR=..` from `prototype_stack/` rather than `../app`, invoked
the same way as before (directly via `docker compose`, since `stack.sh`
still expects an actual git checkout at that path, not an arbitrary local
directory). `stack.sh` itself was not modified. Once the project owner
points `git-source.conf` at this repository's own remote and pushes the
current work, `stack.sh sync && stack.sh up` should work against it
unmodified.

### 10.3 Pre-freeze coverage review: adopted the brief's suggested expansion

`archived/development_handoff/handoff/ICD_PROTOTYPE_DEVELOPMENT_BRIEF.md` §23.2 left open whether the original
four-case/fourteen-`RC-*` suite's coverage was sufficient, or whether to add
verification-focused cases for the FEV1 suffix bands and status branches
that were, until this point, exercised only by `TEST-MAP-01`/`TEST-STATUS-01`
unit vectors rather than through the complete case-to-evaluator-to-API path.
The project owner decided the review was ready to conduct, and the brief's
own suggested expansion — FEV1 below 35%, at exactly 35%, at exactly 70%,
plus an inpatient `!` status fixture — was adopted essentially as proposed.
Four cases were added (`CASE-005`-`CASE-008`), each with a **minimal,
single-code response domain** containing only the correct/prohibited
response, rather than re-enumerating a full six-code family: the
`DEPTH`/`EVID`/`SPEC` branches a full family would re-trigger are already
proven by `CASE-001`/`CASE-002`, so a minimal domain closes exactly the
missing coverage cell without duplicating evidence already established
elsewhere — the same design already used for `CASE-003`. No new catalogue
codes were needed (`J44.00`, `J44.11`, `J44.03`, and `Z01.6` already existed
in `SUBSET-0.1`), so only `CASEBASE`/`RCBASE`/`PROTOBASE` were versioned
forward (`CASEBASE-0.2`, `RCBASE-0.2`, `PROTOBASE-0.2`); `SUBSET-0.1`,
`DOMBASE-0.1`, `RULEBASE-0.1`, and `MODELBASE-0.1` are untouched. Full
propagation (data files, manifest, Python loader/validator/tests, PHP test
oracle path, control-document coverage matrix) is recorded in
[CHANGELOG.md](CHANGELOG.md) and `chapter3_reference_case_coverage_plan.md`
§1.1.

### 10.4 Playwright → Selenium for browser-driven tests

The first browser-driven verification pass (`TEST-E2E-01`/`02`, recorded in
[CHANGELOG.md](CHANGELOG.md)) used Playwright because it was the
quickest path to a real, screenshot-backed browser check with no project
history to contradict. The project owner subsequently specified Selenium as
the standing tool for all future system/integration/regression browser
tests. The Playwright devDependency and its downloaded browser binary were
removed rather than left installed-but-unused, so the repository's actual
dependency list matches its actual testing tooling. `TEST-E2E-01`/`02` were
then implemented as a real, committed Selenium suite (§10.5) rather than
left as a one-off script.

### 10.5 Selenium E2E test architecture

Three decisions worth recording, each made concrete by actually running the
suite rather than assumed to work:

- **PHP + `php-webdriver/webdriver`, not a separate Node/Python test
  project.** The suite tests `app/`'s own frontend/backend; PHPUnit and
  Composer are already the app's test tooling (§4), so adding a third
  language purely for E2E would fragment "how do I run the tests" into
  three different answers for one application. `php-webdriver/webdriver` is
  the actively maintained continuation of Facebook's original bindings
  (same `Facebook\WebDriver\...` namespace) and slots into the existing
  `tests/{Unit,Integration,E2E}` structure as a third PHPUnit testsuite.
- **Selenium's browser container lives in its own `docker-compose.yml`
  under `app/tests/E2E/`, not in `prototype_stack/compose.yaml`.** Selenium
  is test tooling with no role in serving the learner-facing prototype;
  folding it into the deployment stack (even behind a Compose profile)
  would mean "does `stack.sh up` also start a browser automation grid" is a
  question anyone reading that compose file would have to answer. Keeping
  it separate means `stack.sh sync`/`up` (§10.2) stays exactly what it
  already is.
- **`host.docker.internal`, not a shared Docker network.** The browser runs
  inside the Selenium container; the application may be running via the
  Compose stack *or* the bare `php -S` dev server (§6.4 of the
  specification) — joining the Compose network by name would only work for
  the former. Routing through the app's already-published host port via
  `host.docker.internal` (with the `host-gateway` `extra_hosts` mapping for
  Linux, where that hostname isn't automatic the way it is on Docker
  Desktop) works identically regardless of how the app was started, at the
  cost of one more hostname to explain in `app/tests/E2E/README.md`.

One portability issue surfaced immediately on this development machine
(Apple Silicon): the official `selenium/standalone-chrome` image has no
`linux/arm64` build (Chrome for Testing isn't built for arm64 Linux), so it
cannot run under Docker Desktop's default arm64 VM there. The community
`seleniarm/standalone-chromium` image (Chromium instead of Chrome, same
WebDriver protocol) is used instead, with the official image documented as
the amd64 alternative directly in `docker-compose.yml`.

### 10.6 CI, and a self-contained publishable bundle

Two related but distinct decisions, both from the project owner:

**CI.** `HANDOFF.md` (8 August 2026) flagged "no CI" as the one remaining
gap from the implementation "definition of done." `.github/workflows/ci.yml`
closes it with four independent jobs mirroring this project's own test
taxonomy exactly (`IMPLEMENTATION_SPECIFICATION.md` §7) — `python-checks`,
`php-unit`, `backend-integration`, `e2e` — rather than one monolithic job,
so a failure immediately identifies which layer broke without reading a
combined log. `backend-integration` uses GitHub Actions' `services:` block
for MySQL specifically because that publishes the database to the runner's
`127.0.0.1` automatically — solving, for CI, the exact "compose `db` isn't
published to the host" limitation this project hit repeatedly in local
development (§10.5's networking discussion; `HANDOFF.md` §6). The `e2e` job
uses the *official* `selenium/standalone-chrome` image rather than the
arm64 workaround `seleniarm/standalone-chromium` used in local development,
because GitHub-hosted `ubuntu-latest` runners are amd64 — the arm64 problem
this project hit locally (§10.5) simply doesn't exist in CI.

**Self-contained publishable bundle.** The project owner separately asked
for the whole project to be pullable as a self-contained Docker
image/bundle — "dependencies, code, tests etc" — rather than requiring
anyone who wants to run or inspect it to clone the repository and install
PHP/Composer/Node/Python locally. Presented with the choice between one
literal all-in-one container (bundling MySQL + Selenium + PHP inside a
single image, fighting how those tools are normally operated) and a
Compose-orchestrated bundle of several images (each still using its
natural/official base), the project owner chose the latter. Concretely:

- The root `Dockerfile` gained a `dev` build target (`docker build --target dev`):
  the same runtime plus dev Composer dependencies (`phpunit/phpunit`,
  `php-webdriver/webdriver`) and the full `app/tests/` tree. It is reordered
  *before* the lean `runtime` target in the file specifically so a bare
  `docker build .` (no `--target`) still defaults to the deployable image —
  `prototype_stack/compose.yaml`'s `app` service pins `target: runtime`
  explicitly for the same reason, belt-and-suspenders.
- `docker-compose.yml` at the repository root (distinct from
  `prototype_stack/compose.yaml`, which stays the stack.sh-managed
  deployment scaffold with its own required-secrets `.env` workflow) bundles
  `db` + `bootstrap` + `app` as the default profile, plus `selenium` + `test`
  behind a `test` Compose profile — mirroring the same "test tooling never
  starts by default" principle §10.5 already established for the standalone
  Selenium container, now applied to the bundle too. Every service sets
  both `image:` (a `ghcr.io/...` tag, for `docker compose pull` once
  published) and `build:` (for `docker compose build` locally before the
  first publish) — the standard dual-mode idiom for exactly this situation.
- `app`'s and `test`'s `depends_on` use `condition: service_completed_successfully`
  on `bootstrap`, not just `service_healthy` on `db` — the original
  deployment stack relies on `stack.sh`/manual command *sequencing*
  (`up --wait db` → `run bootstrap` → `up --wait app`, three separate CLI
  invocations) to get this ordering right; a bundle meant to be brought up
  with one `docker compose up` command needs that ordering expressed in the
  compose file itself instead.
- `.github/workflows/ci.yml` gained a fifth job, `publish-images`, gated on
  the other four passing and on `push` to `main` — it builds exactly the
  three images `docker-compose.yml` references and pushes them to GHCR
  using the automatically-provided `GITHUB_TOKEN` (no extra secrets to
  configure). The image tags in `docker-compose.yml` are the source of
  truth; this job's tags are written to match them, not the other way
  around.

**`push`/`pull_request` triggers: disabled, then `push` re-enabled.**
Partway through the forward redesign (8 August 2026, commit `0287228`) the
project owner commented out both triggers, leaving only `workflow_dispatch`
— "to avoid CI spam on PRs and pushes to main" while the migration was
still landing in many small commits. This had a real side effect the
project owner hit directly on 9 August 2026: after pushing the step 8 test
fixes, they reported CI "still failing," pasting a log that turned out to
be from the *previous* `workflow_dispatch` run (against a commit before
the fix) — because pushing no longer triggered a run at all, there was no
way to tell a stale result from a current one without separately checking
run metadata (`head_sha`) against `git log`. Once step 8 was confirmed
stable, the project owner asked to re-enable `push` specifically (not
`pull_request`, which stays commented out) so this class of confusion
stops recurring automatically. `on:` now reads `push: branches: [main]`
plus `workflow_dispatch`, matching the original pre-`0287228` shape minus
`pull_request`.

One concrete bug this surfaced by actually running the bundle end-to-end
(not just writing it): `app/tests/Integration/ReferenceResponseTest.php`
locates the `RC-*` oracle CSV via a path relative to its own file, which
resolves correctly on the host checkout (`app/tests/Integration/../../../prototype_baseline_0_1/verification/...`)
but pointed at nothing inside the container, which has no sibling
`prototype_baseline_0_1/` directory. Fixed by copying only that one CSV
into the `dev` image at the equivalent path (`/var/www/prototype_baseline_0_1/verification/reference_responses_0_2.csv`)
rather than changing the test's path logic — the Python data pipeline
itself is deliberately still not part of any `app` image; only the single
file the PHP test harness actually reads travels with it. `.dockerignore`
needed a narrow `!`-negation to let that one file back into the build
context despite the blanket `prototype_baseline_0_1/` exclusion above it.

This exact fix was silently invalidated by step 8 rewiring the test to a
different oracle file (`reference_responses_0_3_candidate.csv`, a
different directory) without anyone re-checking the container path -
found and fixed again, correctly this time, by the housekeeping pass in
§17.

### 10.7 Native AMD64 and ARM64 image publication

The first real user-guide installation attempt on an Apple Silicon Mac found
a distribution defect that earlier local testing had not exercised:
`docker compose pull` downloaded `mysql:latest`, but both project-owned
normal-use tags failed with `no matching manifest for linux/arm64/v8`.
Registry inspection confirmed that `app`, `dev`, and `bootstrap` each
contained a `linux/amd64` image and a provenance attestation, but no ARM64
image. The Dockerfiles themselves were not the limitation: native ARM64
builds of `bootstrap` and `runtime` completed and ran successfully on the
same machine.

Two responses were considered:

1. Put `platform: linux/amd64` in the Compose services. This would make the
   existing tags pull through Docker Desktop's emulation, but permanently
   force Apple Silicon users onto the slower non-native image even after an
   ARM64 image became available.
2. Publish a proper multi-platform index and let Docker select the matching
   image. This keeps one stable tag per service while running natively on both
   common desktop/server architectures.

The second option is the implementation. Following Docker's
[multi-platform GitHub Actions pattern](https://docs.docker.com/build/ci/github-actions/multi-platform/),
`publish-images` sets up QEMU and Buildx, and every `build-push-action` call
targets `linux/amd64,linux/arm64`. A final registry check inspects each
published OCI index and fails the job unless both architectures are present.
The Compose file deliberately contains no forced `platform:` value.

Both verification levels are now executed:

- **Executed locally:** native ARM64 `bootstrap` and `runtime` builds, the
  native ARM64 `dev` image, ordered `db` → `bootstrap` → `app` startup,
  `/api/health`, `/api/cases`, and the full 85-test suite.
- **Executed externally:** GitHub Actions run
  [31257017708](https://github.com/junomarx/bsc-thesis-icd10/actions/runs/31257017708)
  passed all test and publication jobs. Its registry assertion passed for all
  three tags; independent manifest inspection and the exact
  `docker compose pull`/startup sequence on ARM64 confirmed that the live
  indexes now select native ARM64 images.

### 10.8 Pinning `--platform=$BUILDPLATFORM` on architecture-independent build stages

The first real `push`-triggered run after §0.5's trigger re-enablement
(`HANDOFF.md`) surfaced a problem §10.7's design didn't anticipate:
`publish-images`'s multi-arch build of the `runtime` image hung for over
1.5 hours with no output, rather than merely running slowly. Root cause:
none of `Dockerfile`'s stages had a `--platform` pin, so
`docker/build-push-action`'s `linux/amd64,linux/arm64` target caused
*every* stage to build twice - once native, once under QEMU emulation -
including `frontend-build`'s `npm run build` (Vite → esbuild, a native Go
binary). esbuild under QEMU user-mode emulation is a documented
hang/pathological-slowness case, not a merely-slower one; a 20-second
native build can simply never finish under emulation.

The fix is Docker's own documented pattern for this exact situation
(their [multi-platform build guide](https://docs.docker.com/build/building/multi-platform/)
explicitly calls out pinning build-only stages to `$BUILDPLATFORM`):
`frontend-build`, `vendor`, and `vendor-dev` produce architecture-
independent output - static JS/CSS/HTML, and a pure-PHP vendor tree with
no compiled extensions (`composer.json`'s runtime deps are none beyond
`ext-pdo`/`ext-json`, already provided by the base image; dev deps are
`phpunit/phpunit`/`php-webdriver/webdriver`, also pure PHP) - so there was
never a reason to build them per-target-arch. Pinning
`FROM --platform=$BUILDPLATFORM ...` on those three stages makes them
build natively on the runner exactly once, regardless of how many target
platforms the final image list requests; their output is still
`COPY --from=`'d into the real per-arch stages (`base`/`dev`/`runtime`)
exactly as before - `base` genuinely needs a per-arch build, since it
compiles the native `pdo_mysql` extension via `docker-php-ext-install`.

**Verified by reproducing the failure mode locally, not just reasoning
about it:** a `docker-container`-driver builder (matching what
`docker/setup-buildx-action` creates in CI, since the default `docker`
driver doesn't support multi-platform builds at all) running
`docker buildx build --platform linux/amd64,linux/arm64 --target runtime`
completed in 1m35s after the fix, against a host where one of the two
platforms is necessarily emulated either way (same underlying constraint
as CI, different emulated architecture). The one stage that legitimately
still runs under emulation - `base`'s `pdo_mysql` compile - accounted for
about 21 of those seconds, confirming the frontend build specifically was
the hang, not emulation in general. Full detail: `docs/CHANGELOG.md`'s
same-dated entry.

## 11. Current status and known gaps

**Superseded by §13/§14 below (9 August 2026).** Everything in this
section describes the one-case/one-question `CASEBASE-0.2` implementation,
which no longer exists in `app/src/`/`app/frontend/src/`. Kept verbatim as
a historical record of that phase's status rather than silently deleted —
§13 explains why it was replaced, §14 covers the visual/gameful polish
that followed, and §12's table has forward-model rows appended after the
historical ones. For the current implementation's actual status, read §13
and §14, plus [REQUIREMENTS_TRACEABILITY.md](REQUIREMENTS_TRACEABILITY.md)
and [IMPLEMENTATION_SPECIFICATION.md](IMPLEMENTATION_SPECIFICATION.md),
both fully rewritten for the forward model on the same date.

- The reference-suite breadth question flagged in
  `archived/development_handoff/handoff/ICD_PROTOTYPE_DEVELOPMENT_BRIEF.md` §23.2 is **resolved** (§10.3): all
  four `RULE-MAP-01` suffix bands and both `RULE-STATUS-01` prohibited
  branches are now exercised through complete case-to-evaluator-to-API
  integration, not only unit tests.
- No `1.0` baseline has been frozen anywhere (source register, rule
  catalogue, case/reference baseline, or software revision). Everything
  described here is development-time evidence against the working `0.2`
  (case/reference) and `0.1` (subset/domain/rule/model) identifiers, not the
  principal verification run.
- `TEST-E2E-01`/`02` are now a committed, repeatable Selenium suite
  (§10.5, `app/tests/E2E/*`) rather than the one-off manual script the
  earlier walkthrough evidence in [CHANGELOG.md](CHANGELOG.md) used. This
  closes the last outstanding item from the implementation
  "definition of done" (`archived/development_handoff/handoff/ICD_PROTOTYPE_DEVELOPMENT_BRIEF.md` §25).
- A full audit of all 31 `REQ-*` entries
  ([REQUIREMENTS_TRACEABILITY.md](REQUIREMENTS_TRACEABILITY.md)) found no
  undeclared gaps beyond the two items above that are genuinely
  freeze-phase work, not implementation-phase omissions.
- CI now exists (§10.6, `.github/workflows/ci.yml`) and a self-contained,
  publishable Docker bundle (§10.6, `docker-compose.yml`) has been built and
  verified end-to-end locally (85/85 tests, fully containerized, zero host
  PHP/Composer/Node/Python dependencies). The workflow has since completed
  far enough to publish all three GHCR tags, and anonymous registry
  inspection confirms that they are public. The Apple Silicon gap described
  in §10.7 is closed: every live project index contains AMD64 and ARM64, the
  CI assertion passed, and the documented pull-and-start path was executed
  successfully on ARM64.
- **UX/UI redesign** (§7, `docs/UX_UI_BRAINSTORM.md`/`UX_UI_SPECIFICATION.md`):
  design tokens, case-card naming (`short_description`), a first-visit
  tutorial, and a client-side gamification layer are implemented and
  verified — visually (Playwright screenshots across desktop and a 375px
  mobile viewport, including one real bug found and fixed: the sticky
  submit bar's resting position) and functionally (86/86 tests, 495
  assertions, via the same self-contained bundle used above, including a
  new frontend-only `ProgressBadgeTest`). No case data, backend, rule
  engine, or schema change — see §7 for the scope boundary this stayed
  within throughout.

## 12. Traceability quick-reference

| Architectural element | Upstream identifiers it realizes |
|---|---|
| `app/src/Rules/*.php` | `RULE-GATE-01`, `RULE-MAP-01`, `RULE-STATUS-01`, `RULE-DEPTH-01`, `RULE-EVID-01`, `RULE-SPEC-01`, `RULE-CORRECT-01` |
| `app/src/Rules/Precedence.php` + `Evaluation/Evaluator.php` | `RULE-PREC-01` |
| `app/src/Repository/*.php` + `mysql_schema.sql` | `MODELBASE-0.1` §6, `REQ-DAT-*`, `REQ-MOD-02` |
| `app/src/Http/*.php` | `MODELBASE-0.1` §7 (API boundary), `REQ-INT-01`, `REQ-RUL-05` |
| `app/frontend/src/App.jsx` + `components/*.jsx` | `REQ-INT-01`, `REQ-FBK-01`, `REQ-FBK-02`, `REQ-SCP-02` |
| `app/frontend/src/lib/progress.js` | Gamification layer (§7) — no upstream `REQ-*`/`TEST-*`, frontend-only |
| `app/tests/Unit/*` | `TEST-MAP-01`, `TEST-GATE-01`, `TEST-STATUS-01`, `TEST-DEPTH-01`, `TEST-EVID-01`, `TEST-SPEC-01`, `TEST-CORRECT-01`, `TEST-PREC-01` |
| `app/tests/Integration/*` | `TEST-API-01`, `TEST-RC-01`, `TEST-DET-01`, `TEST-ARC-01` |
| `app/tests/E2E/*` | `TEST-E2E-01`, `TEST-E2E-02` |
| `Dockerfile` (repo root), `prototype_stack/compose.yaml` | `REQ-IMP-01`, brief §17, `TEST-CFG-01` (version pinning) |
| `docker-compose.yml` (repo root), `Dockerfile`'s `dev` target | `REQ-DOC-01`/reproducibility (self-contained publishable bundle, §10.6) |
| `.github/workflows/ci.yml` | `REQ-VER-04`/`REQ-VER-06` (automated regression execution, §10.6) |

**Rows above this line describe the historical `CASEBASE-0.2` implementation
(§11's note). Rows below are current, forward-model (`MODELBASE-0.2`/
`RULEBASE-0.2`) elements, added 9 August 2026 — see §13/§14.**

| Architectural element | Upstream identifiers it realizes |
|---|---|
| `app/src/Rules/*.php` (10 classes) | `RULE-GATE-01`, `RULE-MAP-01`, `RULE-STATUS-01`, `RULE-DEPTH-01`, `RULE-EVID-01`, `RULE-SPEC-01`, `RULE-REL-HARD-01`, `RULE-REL-SPEC-01`, `RULE-NOA-01`, `RULE-CORRECT-01` |
| `app/src/Rules/Precedence.php` + `Evaluation/Evaluator.php` | `RULE-PREC-01` (extended: 4-slot hard priority, 2-slot graded priority) |
| `app/src/Repository/PatientRepository.php`/`QuestionRepository.php` + `mysql_schema_0_2.sql` | `MODELBASE-0.2` §6-7, `REQ-DAT-*`, `REQ-MOD-01`-`06` |
| `app/src/Http/PatientController.php`/`QuestionController.php`/`EvaluationController.php` | `MODELBASE-0.2` §7 (API boundary), `APIBASE-0.1`, `REQ-INT-01`-`05`, `REQ-RUL-05` |
| `app/frontend/src/App.jsx` + `components/*.jsx` | `REQ-INT-01`-`05`, `REQ-FBK-01`-`03`, `REQ-SCP-02`, `REQ-UI-01`-`03`, `REQ-GAM-01` |
| `app/frontend/src/lib/i18n.jsx`/`contentTranslations.js`/`catalogueTranslations.js` | §14.2 — no upstream `REQ-*`/`TEST-*`, presentation-layer only |
| `prototype_baseline/Dockerfile.bootstrap` + `persistence_candidate/*` | `MODELBASE-0.2`, `DATAMIG-0.2`, `PROTOBASE-0.3` — §13.3 |
| `app/tests/Unit`/`Integration`/`E2E` | Migrated in step 8; current counts 77/160/9 after the tutorial and theme regressions — see §13.4 and the latest changelog entry |

## 13. Forward redesign: patient/question model (8-9 August 2026)

### 13.1 Why the case-centric model was replaced, not extended

During the UX/UI redesign (§7), surfacing each case's `short_description`
on the case list exposed a pre-existing, previously invisible pedagogical
flaw: `CASE-003`/`005`/`006`/`007` had single-code response domains, so a
learner reading the description before answering could see the correct
family/depth without having actually applied any rule. This was not a
redesign regression — it was documented, intentional verification-coverage
design from before the redesign — but the project owner judged, correctly,
that it undermined the learner-facing point of a coding *exercise* once
that description was surfaced prominently. The candidate fix (add more
single-question cases with richer response domains) was rejected in favour
of a structural rethink: the one-case/one-question model itself was the
limiting factor, not any individual case's data. Commissioned externally
(a separate agent tasked with the specification-level revision) and
returned as a complete forward package: `PATIENTBASE-0.1` (6 synthetic
patients) / `QUESTIONBASE-0.1` (25 atomic learner questions, 3/3/3/5/5/6
per patient, plus the 8 historical fixtures retained as hidden
`verification_only` regression anchors) / `MODELBASE-0.2` (normalized
9-table schema) / `RULEBASE-0.2` (extended rule set) / `UXBASE-0.1`
(the visual/gameful polish concept, §14) / `APIBASE-0.1` (API/feedback
contract clarification).

This is a genuinely different unit of pedagogy, not a bigger version of
the old one: a *patient* is now the learner-facing session container (one
choice, one narrative, one dossier), and a *question* is the atomic,
independently-evaluable, independently-randomizable unit underneath it —
where before, case and question were the same object. `REQ-MOD-02`/`03`
formalize this split explicitly so it can't quietly collapse back.

### 13.2 The required implementation order was followed, and mattered

`chapter3_forward_implementation_instruction_0_5.md` fixed a 10-step order
(persistence → PHP/API → React functional lifecycle → UX polish → tests →
oracle audit → freeze) specifically so that no layer's "verified" claim
would rest on an unverified layer beneath it. This was followed
literally — and the one place it was *nearly* violated (§13.3) is exactly
where the cost of skipping ahead showed up.

### 13.3 The persistence-integration gap: a lesson worth keeping visible

Steps 2-3 (integrate and prove `MODELBASE-0.2` against MySQL) were
completed and verified — against a scratch `docker run` MySQL container on
a non-default port, torn down after each check. Step 6 (functional React
lifecycle) was likewise verified — against the Vite dev server. Both were
real, honest verifications of what they tested. What nobody checked until
the project owner opened a browser at the repository's own `docker compose
up` URL and saw `CASE-001`/COPD content: the *actual* deployment path
(`docker-compose.yml`'s `bootstrap` service) was still building from
`prototype_baseline_0_1/Dockerfile.bootstrap` — the historical
`CASEBASE-0.2` pipeline — and the already-published GHCR `app` image
predated the migration entirely. Every layer above persistence had been
built and verified correctly; the persistence layer itself had been
verified in isolation but never actually wired into the path a real user
would hit.

The fix (`prototype_baseline_0_2_design/Dockerfile.bootstrap`, wired into
both Compose files — `IMPLEMENTATION_SPECIFICATION.md` §6.3) was
mechanical. The lesson is the point of writing this down: **"verified in
isolation" and "verified in the path a user actually takes" are different
claims, and this project had, once, quietly conflated them across two
separate implementation steps.** Every "Verified" row added to
`REQUIREMENTS_TRACEABILITY.md` after this was discovered was checked
against the real running container, not a scratch substitute — and this
section exists so a future reader has the concrete cautionary example, not
just the abstract rule.

### 13.4 Test-suite migration was deliberately not attempted opportunistically

`app/tests/*` still targets the deleted `CaseFacts`/`CaseRepository`/
`CaseController` classes (`php vendor/bin/phpunit --testsuite unit`: 47 of
49 erroring). This was not an oversight discovered late — it is
implementation-order step 8, explicitly sequenced *after* the functional
lifecycle (step 6) and UX polish (step 7) in the instruction document, and
every verification performed during steps 4-7 used ad hoc `curl`/Selenium
checks against the real running stack instead, precisely because the
committed suite was known to be non-functional throughout. Migrating tests
opportunistically alongside feature work — rather than as its own
dedicated pass — was rejected: a rule-engine migration this size deserves
a test rewrite that is reviewed as its own unit of work, not scattered
across a dozen unrelated diffs and harder to audit as a result.

**Update, same day:** that dedicated pass happened - see §15. This section
is kept as written (describing why the gap existed and was deferred, not
edited to pretend it wasn't there) rather than rewritten to only describe
the resolved end state.

## 14. Forward redesign step 7: `UXBASE-0.1` visual/gameful polish

Full mechanic-by-mechanic verification: `docs/CHANGELOG.md`'s two
2026-08-09 "Step 7"/"German content translation" entries. This section
records the decisions that weren't obvious from the concept document
alone.

### 14.1 Scoped to `Must`-priority mechanics plus accessibility, not the full list

`chapter3_ux_ui_gamification_concept_0_1.md` §10 explicitly permits
reducing step 7 to "the `Must` mechanics and accessibility-critical
presentation requirements" under schedule pressure, without sacrificing
steps 1-3 or the verification boundary. That reduction was taken
deliberately, not by default: orientation/legend (`REQ-UI-01`), the visual
progress indicator, the technical-details disclosure, restrained
completion acknowledgment, and the aggregate "all completed" message were
built; code-option display-order permutation (an explicit "may", not a
"shall") and a literal separate "Home" screen (materially the same
requirement as satisfied by the roster-mounted orientation block, at the
cost of navigation-state complexity a second view would add) were not.

**Later refinement:** the roster-mounted orientation block described above
was the implementation at the end of step 7, not the final current UI. A
subsequent project-owner review found that its default-expanded, full-size
presentation behaved like permanent documentation rather than a tutorial.
It was therefore replaced by §7's new first-visit-only interactive modal;
this changes presentation and onboarding state only, not the learner
lifecycle, evaluator, or `REQ-UI-01` content.

### 14.2 Two-directional content translation, kept out of the database

The EN/DE switch (`lib/i18n.jsx`) started as UI-chrome-only, per the
architecture-separation principle already established for the case-centric
redesign (§7): translating interface text is a presentation concern,
never a data-model change. Two rounds of real use immediately exposed the
limits of that boundary as first drawn:

1. Switching to German left patient summaries, context items, and
   question prompts in English — chrome was translated, *content* wasn't.
2. Switching to English left ICD-10 code designations in German — the
   runtime catalogue (`SUBSET-0.2`) is authored in German only (the
   Austrian BMASGPK edition); there was never an English variant to fall
   back to.

Both were fixed the same way: an additive, frontend-only translation
lookup (`contentTranslations.js` for German content, `catalogueTranslations.js`
for English code titles) keyed by the same IDs the API already returns,
consulted only for the locale that needs it, falling back to the API's own
text on any miss. Deliberately **not** a database/schema change or a new
API field for either direction — this stays a `REQ-ARC-01` presentation
concern. Evaluator *explanations* were handled differently and do live in
the API response (`explanation_de`, `IMPLEMENTATION_SPECIFICATION.md`
§3.3): they contain rule-derived content assembled from live values at
evaluation time, which the frontend cannot safely reconstruct or
paraphrase from the English string alone without risking a subtly wrong
translation of what the rule actually decided.

### 14.3 Session-local completion: an existing requirement, not a new one

Adding a "this patient is done" marker to the roster looked at first like
it might need a `REQ-INT-05` amendment (`REQ-INT-05` explicitly prohibits
"a server-side learner account, longitudinal attempt history, or
analytics store"). It didn't: `REQ-UI-02`, already in the catalogue,
specifies exactly this feature — "show question-level and patient-level
progress/completion... completion status is session-local" — and
`REQ-INT-05`'s prohibition is scoped to *server-side* persistence. A
client-side, `sessionStorage`-backed marker (cleared at browser-session
end, unlike `localStorage`) satisfies both literally, with no requirements
change needed. The "reset progress" control added alongside it is not
separately specified anywhere — a direct project-owner request that
followed naturally once completion state existed to reset.

### 14.4 Fixing two raw-token leaks was worth doing beyond the one reported

The project owner reported one specific bug: the literal string
`none_of_above` appearing inside a "None of the above is not correct here"
sentence. The fix for that one call site (`Evaluator::buildNoaResult()`)
generalized into an audit of every other place an internal
snake_case identifier could reach learner-facing prose the same way —
found one more live path (`buildRelHardResult()`'s fallback when no cited
fact exists, currently unreachable by the present 25-question data but
real, reachable code) and one frontend-side instance (the `not_evaluated`
panel interpolating a raw gate `reason` enum). Both were fixed alongside
the reported one rather than left for "someone to notice later" — the
underlying defect class (internal identifiers leaking into learner-facing
text) is the same regardless of which specific string a user happens to
trip over first.

### 14.5 Austrian patient names: a naming override, deliberately narrow

The project owner asked for "common names one might encounter in an
Austrian setting, for realism when demoing." Three of six patient names
already qualified (`Anna Berger`, `Daniel Weiss`, `Peter Gruber`); three
read as identifiably non-Austrian-origin to a general audience
(`Michael Novak`, `Lea Horvat`, `Sofia Marin` — Slovak/Croatian/Romance
surnames, themselves a deliberate diversity-representation choice in the
original patient design, paired with a `self_described_background` field
that names the same heritage explicitly). Only the three flagged names
were changed (`Michael Bauer`/`Lea Wagner`/`Sophie Mayer`); the matching
`self_described_background` values were left untouched — a person whose
surname reads as mainstream-Austrian can still self-identify with a
different heritage without any actual inconsistency, and the instruction
named only "names," not backgrounds. Applied as a `PATIENTBASE-0.1`
content edit (not a version bump — same 6 patients/structure, an unfrozen
baseline, a pure value correction), with the runtime manifest's SHA-256
pin and the persistence candidate's own pinned canonical-digest test
updated to match — both are designed to fail-closed on exactly this kind
of change when it's *not* deliberate, and did, correctly, until updated.

## 15. Forward redesign step 8: the test-suite migration §13.4 deferred

Triggered directly: the project owner ran CI manually (`workflow_dispatch`
- push triggers are deliberately disabled, §10.4-adjacent decision) and
pasted the real failure log twice - the second time, unchanged, after a
narrower CI-infrastructure fix alone didn't resolve it. Read as
confirmation to do the full rewrite rather than stop at the smaller fix,
consistent with this project's general bias toward proceeding once a
signal is reasonably clear rather than re-asking.

### 15.1 Why fixtures needed a general-purpose builder, not just a renamed one

The old `Fixtures::copdCase()`/`statusCase()` took a flat
`array<string, bool>` "response domain" (accepted or not). `RULEBASE-0.2`
replaced that boolean with a five-value `relation_kind` enum, and two of
those values (`fact_conflict`/`temporal_context_conflict` for
`RULE-REL-HARD-01`, `less_specific_supported` for `RULE-REL-SPEC-01`)
carry additional required fields (`reason_key`, `improvement_code`) the
old boolean had no way to express at all. `Fixtures::question()` - a
general builder taking facts/domain/relation-facts/options directly -
was added alongside kept-shape `copdQuestion()`/`statusQuestion()`
convenience wrappers, so the existing `Rule{Status,Depth,Evid,Spec,Correct,
Gate}Test.php` files needed only mechanical call-site updates (class
names, argument order) while the three genuinely new rules
(`RuleRelHardTest`/`RuleRelSpecTest`/`RuleNoaTest` - zero prior coverage)
could construct exactly the relation shapes they need.

### 15.2 The reference-response oracle is wired in now, with its provenance stated honestly

`ReferenceResponseTest` was pointed at the full 143-row `RCBASE-0.3`
candidate file rather than waiting for step 9's human audit to finish
first. This was a real design choice, not the only reasonable one: an
alternative would have been to keep exercising only the 18 historical rows
(the ones `REQ-VER-09` unconditionally requires) and leave the 125 new
ones for step 9 to wire in once audited. Rejected because a failing
implementation/oracle mismatch is valuable signal *now*, and because the
oracle's own `provenance_status` column already carries the honest caveat
("derived from the specification, not yet human-audited") wherever this
test's result is read - nothing about running it early overclaims step 9
as done. `docs/REQUIREMENTS_TRACEABILITY.md`'s `REQ-VER-08`/`09` rows
state the exact resulting distinction (exercised vs. audited) - since
resolved by step 9 itself, §16 below.

### 15.3 Three bugs the rewrite only found because it was actually run

Writing PHP that type-checks and reading it carefully is not the same
verification as running it. All three of the following compiled cleanly
and looked correct on inspection; each was found only by running the
rewritten E2E suite against real Selenium/Chrome and reading what
actually failed:

- A roster helper waited for the `<ul class="patient-list">` container,
  which renders before `GET /api/patients` resolves, instead of an actual
  card - a timing bug that would have made any future E2E test relying on
  "the roster is open" flaky in exactly the way a fixed short `sleep()`
  is usually reached for to paper over, and wasn't.
- `LearnerWorkflowTest` assumed opening a patient always shows a specific
  question first. It doesn't, by design (`REQ-INT-03` shuffles order every
  playthrough) - the test itself was violating the same randomization
  invariant `REQ-INT-03` exists to guarantee stays true. Fixed with a
  helper that clicks through whatever question is on-screen until the
  target appears, treating the shuffle as a fact of the interface rather
  than working around it.
- A regex meant to sum "N questions" roster badges also matched the age
  badge ("68 yrs") on every card, inflating a real total of 25 to 409.
  Caught immediately because the assertion failed with that exact number
  printed, not silently.

None of the three would have been caught by a stricter type system or a
more careful read-through beforehand; they are the specific class of bug
that only running against real infrastructure surfaces. This is the same
lesson §13.3 recorded for the persistence-integration gap, recurring one
layer up the stack.

## 16. Forward redesign step 9: the oracle/source audit, and how it was actually done without the primary sources on hand

§15.2 above deliberately left `REQ-VER-08`/`09` an honest "exercised, not
yet human-audited" caveat rather than closing it prematurely. This section
records how that audit was actually carried out, because the obvious
approach - open `SRC-AT-ICD-SYS-2026`/`SRC-AT-DOC-2026` and check each row
against the printed page - wasn't available: neither PDF exists as a file
in this repository (only `archived/development_handoff/sources/core/DIAGLIST2026.xlsx`
does), and inventing page citations against a source not actually open
would be exactly the kind of fabricated verification this project's own
documentation discipline exists to prevent.

**Decision: treat `QSAUDIT-0.1` as the audited proxy for the 125 new rows,
not re-derive from the primary sources directly.** `chapter3_question_bank_source_audit.md`
already *is* a human source audit - the project owner and a separate agent
produced it by reading the two primary documents directly and recording a
page-number or DIAGLIST-row citation for every one of the 25 questions'
correct/suboptimal/incorrect/`none_of_above` calls, before any of
`RCBASE-0.3`, `RULEBASE-0.2`, or the patient/question database existed
(its own §1 says as much). What step 9 needed to establish was therefore
narrower than "is this clinically/administratively correct" (already
settled by `QSAUDIT-0.1`) - it was "does the *oracle CSV* faithfully
encode what `QSAUDIT-0.1` already established." That's a checkable
question without the PDFs open: read both documents and compare, question
by question, code by code. Done for all 25 questions / 125 rows; zero
discrepancies, including the three deliberate "unspecified ≠ suboptimal"
counterexamples (`F03`, `N40`, `R40.2`) and both `none_of_above = correct`
control questions (`Q-004-05`, `Q-005-05`) that exist specifically to
catch a lazier heuristic (e.g. "`.9` suffix = suboptimal") from slipping
through.

**The 4 reconstructed `VQ-005..008` rows needed a different method, because
`QSAUDIT-0.1` doesn't cover them at all** - it audits the 25 new learner
questions, not the 8 hidden legacy fixtures. Their `provenance_status`
already said `reconstructed_from_implementation_documentation`, meaning
they were rebuilt after the fact from the implementation's own case-fact
definitions rather than carried forward from an original `RCBASE-0.1` row
(unlike `VQ-001..004`, which are exact carry-forwards and were already
audited). For these, the audit ran the documented facts
(`fev1_stable_pct_predicted`, `encounter_setting`, `diagnosis_role`)
directly through the live `RuleMap::evaluate()`/`RuleStatus::matches()`
predicates and compared the result to what the CSV claims. This is a
stronger check than citation-matching, not a weaker fallback: it's a
mechanical, deterministic replay against the same rule the running
application actually uses, sourced from `SRC-AT-DOC-2026` printed p.34
(FEV1 boundaries) and pp.10-11/18 (status-marker restriction) via the
rules' own docblocks. Two of the three FEV1 values (`VQ-006`'s `35.00`,
`VQ-007`'s `70.00`) land exactly on a documented boundary, which
incidentally confirmed the boundary's inclusive/exclusive direction is
coded the way `RuleMap`'s docblock says, not just that *some* value in
each band works. `VQ-008`'s status-rule row was additionally cross-checked
against the already-audited `VQ-003`/`VQ-004` pair (the same rule's other
two branches) to confirm the predicate's boundary generally, not only this
one row's outcome.

**What this does and doesn't claim.** All 129 previously-unaudited rows
now carry a `provenance_status` value ending `..._human_oracle_audit_confirmed_against_qsaudit_0_1`
or (at the time this section was written) `..._human_oracle_audit_confirmed_via_rule_replay`
for the 4 legacy rows - **since superseded by a genuine diff against the
raw historical oracle, §18 below; the rule-replay confirmation was real
evidence, not weakened by what came after, but it's no longer the
strongest evidence available for those 4 rows.** This closed the specific
gap step 9 existed for. It is still not `REQ-VER-05`'s formal freeze-time
conformance report (step 10) - and if the two primary-source PDFs are ever
added to the repository, a direct page-level re-check against them would
be a strictly-stronger, purely additive confirmation for the 125 learner
rows, not a correction of anything this pass found.

## 18. The four "reconstructed" legacy rows were never actually unreconciled - the raw file was archived, not lost

Step 9 (§16 above) confirmed `VQ-005..008` by rule replay because
`QSAUDIT-0.1` doesn't cover the legacy fixtures and the raw `RCBASE-0.2`
file was believed unavailable - every design-phase document describing
these four rows (`prototype_baseline/README.md`, the `migration/` bridge
files, `chapter3_reference_case_coverage_plan_forward_0_3.md`) said so
explicitly, and step 9 didn't independently re-check that premise before
relying on it. It was wrong. The genuine, original 18-row `RCBASE-0.2`
file - not a reconstruction, no `provenance_status` column, predating the
concept - was sitting the whole time at
`archived/prototype_baseline_0_1/verification/reference_responses_0_2.csv`,
archived during the step-9-adjacent repository housekeeping pass (§17)
without anyone connecting it back to the "must be diffed... when it
becomes available" language written before that housekeeping happened.

**The check, once someone actually looked:** read the archived file
directly and compared its `RC-005-01` through `RC-008-01` rows against
the current oracle's `VQ-005..008` rows, field by field -
`submitted_code`, `expected_class`, `determining_rule`, `pattern_id`,
`criterion`, `improvement_code`, `required_explanation_elements`,
`source_locator`. Every field matches exactly, for all four rows. This is
a stronger claim than step 9's rule replay (which confirms the *rule
predicate* produces the claimed result from the documented facts, not
that the *facts and claimed result together* match an independent
historical record) - both are now true for all four rows, from two
different angles.

**Changed as a result**, all confirmed via direct inspection, not assumed
from this section's own claim:

- `prototype_baseline/verification/reference_responses_0_3_candidate.csv`:
  `provenance_status` for the 4 legacy rows →
  `exact_semantic_carry_forward_confirmed_against_rcbase_0_2`.
- `prototype_baseline/data/verification_questions_legacy_0_1.csv`: the
  four `prompt` fields were themselves drifted from the genuine historical
  `short_description` text (`archived/prototype_baseline_0_1/data/cases_0_2.csv`)
  - missing boundary-clause parentheticals for `VQ-005..007`, and `VQ-008`
  was substantively reworded, not merely trimmed. Replaced with the exact
  historical wording.
- `prototype_baseline/verification/oracle_manifest_0_3_candidate.json`:
  `raw_rcbase_0_2_diff_required_before_freeze` → `false`;
  `legacy_provenance`'s `reconstructed_from_implementation_documentation`
  key replaced with `exact_semantic_carry_forward_confirmed_against_rcbase_0_2`;
  `oracle_sha256` regenerated (the CSV content changed).
- `prototype_baseline/design_materialization_manifest.json`:
  `reconciliation_required_before_freeze` → `false`; the equivalent key
  renamed to match.
- `prototype_baseline/forward_verification_digests.json`: both affected
  file digests regenerated.
- `prototype_baseline/validate_forward_verification.py`: **was silently
  broken since step 9** - its hardcoded provenance assertion still checked
  for the bare string `reconstructed_from_implementation_documentation`,
  which step 9 had already replaced with a longer suffixed value; the
  assertion had been failing since step 9 and nobody had re-run this
  standalone validator to notice, since nothing in CI or the test suites
  invokes it. Fixed to match current values; confirmed passing.
- `chapter3_reference_case_coverage_plan_forward_0_3.md` (the coverage
  plan `CASEPLAN-0.3`): removed the "must be diffed... when available"
  language and the four-gates list's now-closed items.
- `prototype_baseline/README.md`, `docs/REQUIREMENTS_TRACEABILITY.md`
  (`REQ-VER-09`), `docs/IMPLEMENTATION_SPECIFICATION.md` §7,
  `ReferenceResponseTest.php`'s own docblock: updated to match.

**Verified, this same pass:** `validate_forward_verification.py` PASS;
`test_runtime_contract_0_2.py` and `test_mysql_persistence_0_2.py` against
a fresh throwaway MySQL, both green; `phpunit --testsuite integration`
160/160 including all 143 reference-response rows from the edited CSV.

**The lesson, stated plainly because it's the second time this exact
shape of mistake happened this project (§13.3's persistence-integration
gap is the first):** a design-phase document's own claim about what's
"unavailable" or "pending" has a shelf life. §17's housekeeping pass moved
files around in service of a completely different goal (repository
cleanliness) and, as a side effect, made a previously-unavailable source
file locally checkable - and nothing about that pass's own scope prompted
anyone to re-open the older, unrelated-seeming claim it happened to
resolve. The general form: before repeating a stale document's claim that
something can't be checked, check whether it can be checked *now*, not
whether the document says it could be checked *then*.

## 17. Repository housekeeping: rename, archive, and tracing every reference before touching anything

Project-owner request after step 9: audit the repository for stale files,
rename `prototype_baseline_0_2_design/` to `prototype_baseline/` (it's the
one live pipeline now, so the design-stage name no longer describes it),
and archive `prototype_baseline_0_1/` with every reference cleaned. The
method mattered more than the mechanics here: every `git mv` was preceded
by a full repository-wide grep for the old path, and every hit was read in
context and classified - a live functional reference (Dockerfile, CI,
Compose file, PHP/Python source: must fix, build-breaking if missed), a
living-document current-state claim (`HANDOFF.md`, `README.md`, this
document: must fix, misleading if left), or a frozen historical record
(`docs/CHANGELOG.md`'s past entries, `chapter3_*.md`, dated point-in-time
snapshot files: leave alone, rewriting history to match a later rename
would be worse than the staleness it "fixes"). Getting that classification
right, not the `git mv` itself, was the actual work.

**Decision: `chapter3_*.md` root files are out of scope, on purpose.**
Several (`chapter3_data_model_and_interaction_baseline.md`,
`chapter3_test_catalogue.md`, `chapter_3_methods_and_practical_work_specification.md`)
mention `prototype_baseline_0_1/` by name. These are the upstream,
versioned specification lineage `CLAUDE.md` already names as such - old
and new revisions deliberately kept side by side, not stale duplicates
someone forgot to delete. Editing them to match a later filesystem rename
would blur the line between "the upstream authority document" and "a
downstream implementation artefact," which is exactly the line this
project has been careful to keep intact all session. Left untouched.

**Decision: archive `development_handoff/` and `forward_package_0_6/`
alongside `prototype_baseline_0_1/`, not just the one directory named in
the request.** The request's literal scope was `prototype_baseline_0_1/`;
the audit surfaced two more directories that fit the same "stale, no
longer in use" description the request itself gave as the general
criterion. `development_handoff/` was already described in this project's
own prose (`README.md`'s repository-layout table, before this pass) as
"archived pre-implementation planning documents" - moving it under
`archived/` makes that description literally true instead of aspirational.
`forward_package_0_6/` (9.6MB) was a one-time delivery drop from a
separate collaborating agent; its useful content (`chapter3_api_and_feedback_contract_0_1.md`
and its sibling control documents, a `persistence_candidate/` sync) was
already extracted into the live tree weeks earlier (`docs/CHANGELOG.md`'s
`APIBASE-0.1` entry) - the copy left behind was pure leftover, not a
second copy anyone was relying on.

**Decision: also untrack `.venv/` and remove empty `latex/`, found by the
same audit, not requested by name.** `.venv/` (198MB, including
platform-specific compiled binaries like `_mysql_connector.cpython-311-darwin.so`)
was already `.gitignore`d but had been committed before that rule existed
- `git rm -r --cached` removes it from tracking without touching the
working copy anyone's actual Python tooling depends on. `latex/` was an
empty, never-tracked directory with no relationship to the real
`docs/USER_GUIDE.tex` toolchain - deleted outright, nothing to preserve.

**Three real bugs found by tracing every reference, not invented for this
exercise - the actual justification for doing a careful audit instead of a
blind find-and-replace:**

1. `.github/workflows/ci.yml`'s `publish-images` job was still building the
   published `bsc-thesis-icd10-bootstrap:latest` GHCR image from
   `prototype_baseline_0_1/Dockerfile.bootstrap` - the pre-migration
   pipeline - three separate times after `docker-compose.yml` and
   `prototype_stack/compose.yaml` had already been correctly repointed at
   the `_0_2` design (§13.3's persistence-integration gap, and again at
   the CI `backend-integration` job's own separate instance of the same
   bug class, `docs/CHANGELOG.md`'s "CI's `backend-integration` job fixed
   a second instance" entry). This would have been a *fourth* instance,
   caught only because `push` was just re-enabled (§0.5's account in
   `HANDOFF.md`) and the next push would have actually exercised it.
2. `.github/workflows/ci.yml`'s `python-checks` job was still running the
   superseded `_0_1` pipeline's own `prepare_subset.py`/`tests.test_runtime_contract`
   against `SUBSET-0.1` (13 records), not the active `_0_2` pipeline's
   `prepare_subset_0_2.py`/`test_runtime_contract_0_2` against `SUBSET-0.2`
   (99 records) - silently "passing" the entire time by testing a
   self-consistent but no-longer-relevant thing instead of failing loudly.
3. The root `Dockerfile`'s `dev` target `COPY`ed
   `prototype_baseline_0_1/verification/reference_responses_0_2.csv` -
   correct when originally written (§10.6 above), silently invalidated
   when step 8 rewired `ReferenceResponseTest.php` to a different oracle
   file in a different directory, and never re-checked since. The
   container's copy of `TEST-RC-01` had been unable to find its oracle
   file since step 8 landed, with nothing surfacing that fact because the
   containerized test path isn't part of the routine `phpunit` /
   `docker compose up` verification loop this project actually runs day to
   day - only a full `docker build --target dev` followed by running the
   suite *inside* the resulting container would have shown it.

None of these three would have been caught by grepping for the renamed
directory alone - each required actually reading what depended on the old
path and asking whether the dependency still made sense, not just whether
the string still matched. `.dockerignore` had a related, lower-severity
gap in the same family: it excluded `prototype_baseline_0_1/` (with a
narrow exception for the one CSV) but never excluded
`prototype_baseline_0_2_design/` at all, so the entire design-stage tree -
review spreadsheets, migration CSVs, none of it needed by any image - had
been part of the Docker build context the whole time, unnoticed because a
bloated build context fails silently (slower builds), not loudly (broken
ones).

**Verified, this same pass, against the actual renamed/archived tree, not
assumed from the diff:** both `Dockerfile` targets and the bootstrap image
build; a full `docker compose build bootstrap app && docker compose up -d
--wait app` with a live `GET /api/patients` check; `phpunit --testsuite
unit` (77/77) and `--testsuite integration` against a fresh throwaway
MySQL (160/160, all 143 reference-response rows from the renamed CSV
path); `python -m unittest test_runtime_contract_0_2` (8/8) and
`test_mysql_persistence_0_2` (6/6); `prepare_subset_0_2.py --check-existing`
against the new `archived/development_handoff/` source location. The `e2e`
suite was not re-run - no E2E test file references any moved path, and the
full-stack check above already exercises the application these tests
would drive - but a real GitHub-hosted CI run is still the first true
confirmation of the `ci.yml` fixes specifically, since `workflow_dispatch`
and `push` are the only ways to actually execute that file.
