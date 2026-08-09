# Development documentation

**Scope:** the implementation phase of the Austrian ICD-10 educational
prototype (`PROTOBASE-0.2`, superseding `PROTOBASE-0.1`, and its downstream application layer).
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
| Problem identification & motivation | `ICD_PROTOTYPE_DEVELOPMENT_BRIEF.md` §1–2; the research question in that brief |
| Objectives of a solution | `chapter3_requirements_catalogue.md` (`REQ-*`) |
| Design & development | `chapter3_rule_catalogue.md`, `chapter3_data_model_and_interaction_baseline.md` (upstream design) **plus this document and `app/`** (the realized build) |
| Demonstration | The running Docker Compose stack (`prototype_stack/`) serving the learner workflow described in §7 below; the browser walkthrough recorded in [CHANGELOG.md](CHANGELOG.md) |
| Evaluation | The technical test suite (`app/tests/`, `prototype_baseline_0_1/tests/`) — see §9 |
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

The physical schema (`prototype_baseline_0_1/mysql_schema.sql`) is a direct,
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
determinism (`INT-SUP-03`). Concretely, that produced these choices rather
than an unstated "keep it simple" gesture:

- **No client-side router.** The learner workflow (`REQ-INT-01`) is a
  strictly linear case → code → feedback path with no requirement for
  bookmarkable URLs per case. `frontend/src/App.jsx` models this as three
  in-memory view states (`list`, `case`, `result`) rather than pulling in
  `react-router` for a workflow that never branches.
- **Feedback design follows the cited formative-feedback evidence, not
  intuition.** `EVID-FB-01` (Shute 2008) and `EVID-FB-02` (Hattie & Timperley
  2007) motivate elaborated, task-focused feedback over a bare right/wrong
  signal. `ResultView` (in `App.jsx`) always renders three things together:
  the class (`Correct`/`Suboptimal`/`Incorrect`), a task-focused explanation
  naming the concrete criterion that decided it, and — only when one exists
  — an explicit improvement target. Colour (green/amber/red heading) is a
  reinforcement of the text label, never the only signal, so the class is
  still legible without colour.
- **The intended-use boundary is stated on the landing view, not only in
  documentation.** `REQ-SCP-02` requires that the "not for diagnosis /
  clinical decision support / official coding" boundary be stated in
  UI/disclaimer text, not just asserted in the thesis. `CaseList` renders
  this as the first thing a learner sees, above the case list itself.
- **The searchable code list is deliberately a plain `<input>` + client-side
  filter over the case's already-fetched `supported_codes`, not a
  typeahead/autocomplete component.** The response domain per case tops out
  at six codes (`REQ-DAT-03`'s purposive, small subset), so a debounced
  network-backed autocomplete would be solving a scale problem this project
  does not have.
- **No accepted-set information ever reaches the client.** `CaseController`
  strips `is_acceptable` before responding; the frontend never has enough
  information to reveal the answer key through, e.g., inspecting the
  network tab. This is a direct, UI-side extension of the runtime/oracle
  separation principle in §5.5 — the *learner's browser* is treated as
  no more trusted than the verification harness.

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
`ICD_PROTOTYPE_DEVELOPMENT_BRIEF.md` §23.2). Exact counts and dates for each
run are in [CHANGELOG.md](CHANGELOG.md); this section describes the *shape*
of the testing approach.

| Layer | Tool | What it exercises | Needs a database? |
|---|---|---|---|
| Data/source structural checks | Python `unittest` (`prepare_subset.py --check-existing`, `validate_baseline.py`) | Frozen-source checksum, deterministic subset reproduction, oracle/runtime-model consistency | No |
| Persistence integration | Python `unittest` (`tests/test_mysql_persistence.py`) | Live schema shape, row counts, FK enforcement, oracle-column absence | Yes (live MySQL) |
| Rule-engine unit tests | PHPUnit (`app/tests/Unit/*`) | Every `RULE-*` predicate and `Precedence` in isolation, against hand-built `CaseFacts` fixtures | No |
| Backend integration tests | PHPUnit (`app/tests/Integration/*`) | Repositories + evaluator + API together, including all 18 `RC-*` rows, determinism, and oracle isolation | Yes (live MySQL) |
| End-to-end / browser | Selenium via `php-webdriver/webdriver` (`app/tests/E2E/*`; Playwright was used once for the initial pass, then retired — §10.4/§10.5) | The actual React → PHP → MySQL path a learner would exercise | Yes (full stack + Selenium) |
| Container/orchestration | `docker compose build` / `up` / the bootstrap service's own test invocation | That the images build, the services start in the right order, and the bootstrap pipeline behaves idempotently against a *freshly created* compose-managed database | Yes (via Compose) |

A deliberate methodological point carried over from the upstream test
catalogue (`chapter3_test_catalogue.md` §2): the eighteen `RC-*` reference
rows are not "just more unit tests." `ReferenceResponseTest.php` sends only
`case_id` and `submitted_code` through the real HTTP-shaped controller and
compares against an oracle it reads once at test-collection time — it
never becomes a runtime dependency of the application it is testing.

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

`ICD_PROTOTYPE_DEVELOPMENT_BRIEF.md` §23.2 left open whether the original
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

## 11. Current status and known gaps

- The reference-suite breadth question flagged in
  `ICD_PROTOTYPE_DEVELOPMENT_BRIEF.md` §23.2 is **resolved** (§10.3): all
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
  "definition of done" (`ICD_PROTOTYPE_DEVELOPMENT_BRIEF.md` §25).
- A full audit of all 31 `REQ-*` entries
  ([REQUIREMENTS_TRACEABILITY.md](REQUIREMENTS_TRACEABILITY.md)) found no
  undeclared gaps beyond the two items above that are genuinely
  freeze-phase work, not implementation-phase omissions.
- CI now exists (§10.6, `.github/workflows/ci.yml`) and a self-contained,
  publishable Docker bundle (§10.6, `docker-compose.yml`) has been built and
  verified end-to-end locally (85/85 tests, fully containerized, zero host
  PHP/Composer/Node/Python dependencies). **Not yet independently verified**:
  the `publish-images` CI job's actual push to GHCR, which can only be
  proven by running on real GitHub Actions infrastructure with the real
  `GITHUB_TOKEN` — that requires pushing this work to GitHub, which wasn't
  done as part of writing it (see [CHANGELOG.md](CHANGELOG.md)). GHCR
  packages are private by default under a personal account; making them
  public for anonymous `docker pull` is a one-time step in GitHub's package
  settings after the first successful publish.

## 12. Traceability quick-reference

| Architectural element | Upstream identifiers it realizes |
|---|---|
| `app/src/Rules/*.php` | `RULE-GATE-01`, `RULE-MAP-01`, `RULE-STATUS-01`, `RULE-DEPTH-01`, `RULE-EVID-01`, `RULE-SPEC-01`, `RULE-CORRECT-01` |
| `app/src/Rules/Precedence.php` + `Evaluation/Evaluator.php` | `RULE-PREC-01` |
| `app/src/Repository/*.php` + `mysql_schema.sql` | `MODELBASE-0.1` §6, `REQ-DAT-*`, `REQ-MOD-02` |
| `app/src/Http/*.php` | `MODELBASE-0.1` §7 (API boundary), `REQ-INT-01`, `REQ-RUL-05` |
| `app/frontend/src/App.jsx` | `REQ-INT-01`, `REQ-FBK-01`, `REQ-FBK-02`, `REQ-SCP-02` |
| `app/tests/Unit/*` | `TEST-MAP-01`, `TEST-GATE-01`, `TEST-STATUS-01`, `TEST-DEPTH-01`, `TEST-EVID-01`, `TEST-SPEC-01`, `TEST-CORRECT-01`, `TEST-PREC-01` |
| `app/tests/Integration/*` | `TEST-API-01`, `TEST-RC-01`, `TEST-DET-01`, `TEST-ARC-01` |
| `app/tests/E2E/*` | `TEST-E2E-01`, `TEST-E2E-02` |
| `Dockerfile` (repo root), `prototype_stack/compose.yaml` | `REQ-IMP-01`, brief §17, `TEST-CFG-01` (version pinning) |
| `docker-compose.yml` (repo root), `Dockerfile`'s `dev` target | `REQ-DOC-01`/reproducibility (self-contained publishable bundle, §10.6) |
| `.github/workflows/ci.yml` | `REQ-VER-04`/`REQ-VER-06` (automated regression execution, §10.6) |
